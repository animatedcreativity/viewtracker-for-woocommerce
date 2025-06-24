<?php
/**
 * Core functionality for ViewTracker
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ViewTracker Core Class
 */
class ViewTracker_Core {
    /**
     * @var ViewTracker_Core The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main instance
     * 
     * @return ViewTracker_Core
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Track product views
        add_action('woocommerce_single_product_summary', array($this, 'track_product_view'), 5);
        
        // Register AJAX handlers
        add_action('wp_ajax_viewtracker_record_view', array($this, 'ajax_record_view'));
        add_action('wp_ajax_nopriv_viewtracker_record_view', array($this, 'ajax_record_view'));
        
        // Daily cleanup task via cron
        add_action('viewtracker_daily_cleanup', array($this, 'cleanup_old_data'));
        
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('viewtracker_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'viewtracker_daily_cleanup');
        }
    }

    /**
     * Track product view
     */
    public function track_product_view() {
        global $product;
        
        if (!is_object($product)) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // For AJAX tracking
        if ($this->is_ajax_tracking_enabled()) {
            $this->enqueue_tracking_script($product_id);
            return;
        }
        
        // Direct tracking
        $this->record_product_view($product_id);
    }
    
    /**
     * Enqueue tracking script for AJAX view recording
     *
     * @param int $product_id Product ID
     */
    private function enqueue_tracking_script($product_id) {
        wp_enqueue_script(
            'viewtracker-ajax',
            VIEWTRACKER_PLUGIN_URL . 'assets/js/viewtracker-ajax.js',
            array('jquery'),
            VIEWTRACKER_VERSION,
            true
        );
        
        wp_localize_script(
            'viewtracker-ajax',
            'viewtracker_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('viewtracker-ajax-nonce')
            )
        );
    }
    
    /**
     * AJAX handler for recording views
     */
    public function ajax_record_view() {
        check_ajax_referer('viewtracker-ajax-nonce', 'nonce');
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if ($product_id) {
            $this->record_product_view($product_id);
            wp_send_json_success();
        }
        
        wp_send_json_error();
        exit;
    }

    /**
     * Record product view
     *
     * @param int $product_id Product ID
     * @return bool Success or failure
     */
    public function record_product_view($product_id) {
        if (!$product_id || !$this->should_record_view($product_id)) {
            return false;
        }
        
        // Get current views
        $views = $this->get_product_views($product_id);
        
        // Increment view count
        $views += 1;
        
        // Update view count
        update_post_meta($product_id, '_viewtracker_views', $views);
        
        // Record view in detailed tracking table
        $this->record_detailed_view($product_id);
        
        // Mark this product as viewed for current user in this session
        $this->mark_product_viewed($product_id);
        
        return true;
    }
    
    /**
     * Check if view should be recorded
     * 
     * @param int $product_id Product ID
     * @return bool Whether to record the view
     */
    private function should_record_view($product_id) {
        // Don't count admin views if setting enabled
        if ($this->is_admin_excluded() && current_user_can('manage_woocommerce')) {
            return false;
        }
        
        // Check if already viewed in this session
        if ($this->is_duplicate_protection_enabled() && $this->is_product_viewed($product_id)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get product views
     * 
     * @param int $product_id Product ID
     * @return int Total views
     */
    public function get_product_views($product_id) {
        $views = get_post_meta($product_id, '_viewtracker_views', true);
        return empty($views) ? 0 : intval($views);
    }
    
    /**
     * Record detailed view information in custom table
     * 
     * @param int $product_id Product ID
     * @return bool Success or failure
     */
    private function record_detailed_view($product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'viewtracker_product_views';
        
        $data = array(
            'product_id' => $product_id,
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'ip_address' => $this->get_anonymized_ip(),
            'user_agent' => $this->get_user_agent(),
            'device_type' => $this->detect_device_type(),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
            'date_viewed' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        return $result !== false;
    }
    
    /**
     * Mark product as viewed in current session
     * 
     * @param int $product_id Product ID
     */
    private function mark_product_viewed($product_id) {
        $viewed_products = $this->get_viewed_products();
        
        if (!in_array($product_id, $viewed_products)) {
            $viewed_products[] = $product_id;
            WC()->session->set('viewtracker_viewed_products', $viewed_products);
        }
    }
    
    /**
     * Check if product has been viewed in current session
     * 
     * @param int $product_id Product ID
     * @return bool Whether product has been viewed
     */
    private function is_product_viewed($product_id) {
        $viewed_products = $this->get_viewed_products();
        return in_array($product_id, $viewed_products);
    }
    
    /**
     * Get viewed products in current session
     * 
     * @return array Product IDs
     */
    private function get_viewed_products() {
        if (!function_exists('WC') || !isset(WC()->session)) {
            return array();
        }
        
        $viewed_products = WC()->session->get('viewtracker_viewed_products', array());
        
        return is_array($viewed_products) ? $viewed_products : array();
    }
    
    /**
     * Get anonymized IP address
     * 
     * @return string Anonymized IP
     */
    private function get_anonymized_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Anonymize the IP address by removing the last octet
        return preg_replace('/(\d+\.\d+\.\d+)\.\d+/', '$1.0', $ip);
    }
    
    /**
     * Get session ID
     * 
     * @return string Session ID
     */
    private function get_session_id() {
        if (!function_exists('WC') || !isset(WC()->session)) {
            return '';
        }
        
        return WC()->session->get_customer_id();
    }
    
    /**
     * Get user agent
     * 
     * @return string User agent
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    }
    
    /**
     * Detect device type
     * 
     * @return string Device type (desktop, tablet, mobile)
     */
    private function detect_device_type() {
        $user_agent = $this->get_user_agent();
        
        if (empty($user_agent)) {
            return 'unknown';
        }
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $user_agent)) {
            return 'tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipod|android|blackberry)/i', $user_agent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Check if AJAX tracking is enabled
     * 
     * @return bool Whether AJAX tracking is enabled
     */
    private function is_ajax_tracking_enabled() {
        return get_option('viewtracker_ajax_tracking', 'yes') === 'yes';
    }
    
    /**
     * Check if admin view exclusion is enabled
     * 
     * @return bool Whether admin views are excluded
     */
    private function is_admin_excluded() {
        return get_option('viewtracker_exclude_admin', 'yes') === 'yes';
    }
    
    /**
     * Check if duplicate protection is enabled
     * 
     * @return bool Whether duplicate protection is enabled
     */
    private function is_duplicate_protection_enabled() {
        return get_option('viewtracker_duplicate_protection', 'yes') === 'yes';
    }
    
    /**
     * Cleanup old data based on retention settings
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        $retention_period = get_option('viewtracker_data_retention', '365');
        
        // Don't delete if set to keep forever
        if ($retention_period === '0') {
            return;
        }
        
        $table_name = $wpdb->prefix . 'viewtracker_product_views';
        
        $date = date('Y-m-d H:i:s', strtotime("-{$retention_period} days"));
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE date_viewed < %s",
                $date
            )
        );
    }
}
