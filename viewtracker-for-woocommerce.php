<?php
/**
 * Plugin Name: ViewTracker for WooCommerce
 * Plugin URI: https://animatedcreativity.com/viewtracker
 * Description: Track and display product view counts in your WooCommerce store with advanced analytics and reporting.
 * Version: 1.0.0
 * Author: Rehmat Ullah (Animated Creativity)
 * Author URI: https://animatedcreativity.com/
 * Text Domain: viewtracker-wc
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 6.8.1
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VIEWTRACKER_VERSION', '1.0.0');
define('VIEWTRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIEWTRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIEWTRACKER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Add settings link on plugin page
 */
function viewtracker_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=viewtracker-settings') . '">' . __('Settings', 'viewtracker-wc') . '</a>';
    $analytics_link = '<a href="' . admin_url('admin.php?page=viewtracker-analytics') . '">' . __('Analytics', 'viewtracker-wc') . '</a>';
    array_unshift($links, $settings_link);
    array_unshift($links, $analytics_link);
    return $links;
}

// Add the settings link to the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'viewtracker_settings_link');

/**
 * Check if WooCommerce is active
 */
function viewtracker_wc_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * Main plugin class
 */
final class ViewTracker_For_WooCommerce {
    /**
     * @var ViewTracker_For_WooCommerce The single instance of the class
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main instance
     *
     * @since 1.0.0
     * @return ViewTracker_For_WooCommerce
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core functions
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/class-viewtracker-core.php';
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/class-viewtracker-admin.php';
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/class-viewtracker-public.php';
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/viewtracker-functions.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Actions
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
    }

    /**
     * Activate plugin
     */
    public function activate() {
        // Create tables and options
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/class-viewtracker-activator.php';
        ViewTracker_Activator::activate();
    }

    /**
     * Deactivate plugin
     */
    public function deactivate() {
        // Clean up if needed
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/class-viewtracker-deactivator.php';
        ViewTracker_Deactivator::deactivate();
    }

    /**
     * On plugins loaded
     */
    public function on_plugins_loaded() {
        // Check if WooCommerce is active
        if (!viewtracker_wc_is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize core, admin, and public classes
        ViewTracker_Core::instance();
        
        if (is_admin()) {
            ViewTracker_Admin::instance();
        } else {
            ViewTracker_Public::instance();
        }
        
        // Load text domain
        load_plugin_textdomain('viewtracker-wc', false, dirname(VIEWTRACKER_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('ViewTracker for WooCommerce requires WooCommerce to be installed and active.', 'viewtracker-wc'); ?></p>
        </div>
        <?php
    }
}

// Start the plugin
function viewtracker_wc() {
    return ViewTracker_For_WooCommerce::instance();
}

// Run the plugin
viewtracker_wc();
