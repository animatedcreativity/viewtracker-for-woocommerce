<?php
/**
 * Admin functionality for ViewTracker
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ViewTracker Admin Class
 */
class ViewTracker_Admin {
    /**
     * @var ViewTracker_Admin The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main instance
     * 
     * @return ViewTracker_Admin
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add views column to products list
        add_filter('manage_edit-product_columns', array($this, 'add_views_column'));
        add_action('manage_product_posts_custom_column', array($this, 'views_column_content'), 10, 2);
        add_filter('manage_edit-product_sortable_columns', array($this, 'make_views_column_sortable'));
        add_action('pre_get_posts', array($this, 'sort_by_views'));
        
        // Add product views meta box
        add_action('add_meta_boxes', array($this, 'add_views_meta_box'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Create a top-level menu for Product Views
        add_menu_page(
            __('Product Views', 'viewtracker-wc'),   // Page title
            __('Product Views', 'viewtracker-wc'),   // Menu title
            'manage_woocommerce',                   // Capability
            'viewtracker-analytics',                // Menu slug
            array($this, 'render_analytics_page'),  // Function
            'dashicons-visibility',                 // Icon
            58                                      // Position after WooCommerce
        );
        
        // Add Analytics as submenu
        add_submenu_page(
            'viewtracker-analytics',                // Parent slug
            __('Analytics', 'viewtracker-wc'),      // Page title
            __('Analytics', 'viewtracker-wc'),      // Menu title
            'manage_woocommerce',                   // Capability
            'viewtracker-analytics',                // Menu slug
            array($this, 'render_analytics_page')   // Function
        );
        
        // Add Settings as submenu
        add_submenu_page(
            'viewtracker-analytics',                // Parent slug
            __('Settings', 'viewtracker-wc'),       // Page title
            __('Settings', 'viewtracker-wc'),       // Menu title
            'manage_woocommerce',                   // Capability
            'viewtracker-settings',                 // Menu slug
            array($this, 'render_settings_page')    // Function
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('viewtracker_settings', 'viewtracker_ajax_tracking');
        register_setting('viewtracker_settings', 'viewtracker_exclude_admin');
        register_setting('viewtracker_settings', 'viewtracker_duplicate_protection');
        register_setting('viewtracker_settings', 'viewtracker_data_retention');
        register_setting('viewtracker_settings', 'viewtracker_widget_count');
        register_setting('viewtracker_settings', 'viewtracker_reset_on_update');
        register_setting('viewtracker_settings', 'viewtracker_thumbnail_size');
    }
    
    /**
     * Add views column to products list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_views_column($columns) {
        $columns['product_views'] = __('Views', 'viewtracker-wc');
        return $columns;
    }
    
    /**
     * Views column content
     * 
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function views_column_content($column, $post_id) {
        if ($column === 'product_views') {
            $views = viewtracker_get_product_views($post_id);
            echo '<span class="viewtracker-count">' . esc_html($views) . '</span>';
        }
    }
    
    /**
     * Make views column sortable
     * 
     * @param array $columns Sortable columns
     * @return array Modified columns
     */
    public function make_views_column_sortable($columns) {
        $columns['product_views'] = 'product_views';
        return $columns;
    }
    
    /**
     * Sort by views
     * 
     * @param WP_Query $query The query object
     */
    public function sort_by_views($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'product') {
            return;
        }
        
        if ($query->get('orderby') === 'product_views') {
            $query->set('meta_key', '_viewtracker_views');
            $query->set('orderby', 'meta_value_num');
        }
    }
    
    /**
     * Add views meta box
     */
    public function add_views_meta_box() {
        add_meta_box(
            'viewtracker_product_views',
            __('Product Views', 'viewtracker-wc'),
            array($this, 'render_views_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render views meta box
     * 
     * @param WP_Post $post Post object
     */
    public function render_views_meta_box($post) {
        $views = viewtracker_get_product_views($post->ID);
        $detailed_url = admin_url('admin.php?page=viewtracker-analytics&product=' . $post->ID);
        
        echo '<p>';
        echo '<strong>' . esc_html__('Total Views:', 'viewtracker-wc') . '</strong> ';
        echo esc_html($views);
        echo '</p>';
        
        echo '<a href="' . esc_url($detailed_url) . '" class="button">';
        echo esc_html__('View Details', 'viewtracker-wc');
        echo '</a> ';
        
        echo '<button type="button" class="button reset-views" data-product-id="' . esc_attr($post->ID) . '" data-nonce="' . wp_create_nonce('viewtracker-reset-' . $post->ID) . '">';
        echo esc_html__('Reset Views', 'viewtracker-wc');
        echo '</button>';
        
        echo '<div class="viewtracker-message" style="margin-top: 10px;"></div>';
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page
     */
    public function enqueue_admin_assets($hook) {
        wp_enqueue_style(
            'viewtracker-admin',
            VIEWTRACKER_PLUGIN_URL . 'assets/css/viewtracker-admin.css',
            array(),
            VIEWTRACKER_VERSION
        );
        
        wp_enqueue_script(
            'viewtracker-admin',
            VIEWTRACKER_PLUGIN_URL . 'assets/js/viewtracker-admin.js',
            array('jquery', 'wp-util'),
            VIEWTRACKER_VERSION,
            true
        );
        
        wp_localize_script(
            'viewtracker-admin',
            'viewtracker_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('viewtracker-admin-nonce'),
                'i18n' => array(
                    'reset_confirm' => __('Are you sure you want to reset view count for this product?', 'viewtracker-wc'),
                    'reset_success' => __('Views reset successfully', 'viewtracker-wc'),
                    'reset_error' => __('Error resetting views', 'viewtracker-wc')
                )
            )
        );
        
        // Chart.js for analytics page
        // The hook changed when we moved to top-level menu from WooCommerce submenu
        if ($hook === 'toplevel_page_viewtracker-analytics' || strpos($hook, 'viewtracker-analytics') !== false) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
                array('jquery'),
                '3.7.0',
                true
            );
            
            // Add dependency on Chart.js for the admin script on analytics pages
            if (wp_script_is('viewtracker-admin', 'enqueued')) {
                global $wp_scripts;
                $wp_scripts->registered['viewtracker-admin']->deps[] = 'chartjs';
            }
        }
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'viewtracker_dashboard_widget',
            __('Most Viewed Products', 'viewtracker-wc'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $widget_count = get_option('viewtracker_widget_count', 5);
        $products = viewtracker_get_most_viewed_products($widget_count);
        
        if (empty($products)) {
            echo '<p>' . esc_html__('No product views recorded yet.', 'viewtracker-wc') . '</p>';
            return;
        }
        
        echo '<ul class="viewtracker-popular-products">';
        
        foreach ($products as $product) {
            $edit_link = get_edit_post_link($product->ID);
            $title = $product->post_title;
            $views = get_post_meta($product->ID, '_viewtracker_views', true);
            
            echo '<li>';
            echo '<a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a> ';
            echo '<span class="viewtracker-count">' . esc_html($views) . ' ' . esc_html(_n('view', 'views', $views, 'viewtracker-wc')) . '</span>';
            echo '</li>';
        }
        
        echo '</ul>';
        
        echo '<p class="viewtracker-more-link">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=viewtracker-analytics')) . '">';
        echo esc_html__('View all analytics', 'viewtracker-wc');
        echo '</a>';
        echo '</p>';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        // Check for specific product view
        $product_id = isset($_GET['product']) ? absint($_GET['product']) : 0;
        
        if ($product_id) {
            $this->render_product_analytics($product_id);
            return;
        }
        
        // Get date filters
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        // Get most viewed products in date range
        $products = viewtracker_get_most_viewed_products_in_range($start_date, $end_date, 20);
        
        // Get total views in date range
        $total_views = viewtracker_get_total_views_in_range($start_date, $end_date);
        
        // Get device breakdown
        $device_stats = viewtracker_get_device_stats($start_date, $end_date);
        
        // Get daily views for chart
        $daily_views = viewtracker_get_daily_views($start_date, $end_date);
        
        include VIEWTRACKER_PLUGIN_DIR . 'admin/analytics.php';
    }
    
    /**
     * Render product analytics
     * 
     * @param int $product_id Product ID
     */
    private function render_product_analytics($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_die(__('Invalid product ID', 'viewtracker-wc'));
        }
        
        // Get date filters
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        // Get total views
        $total_views = get_post_meta($product_id, '_viewtracker_views', true);
        
        // Get views in date range
        $views_in_range = viewtracker_get_product_views_in_range($product_id, $start_date, $end_date);
        
        // Get daily views for chart
        $daily_views = viewtracker_get_product_daily_views($product_id, $start_date, $end_date);
        
        // Get device breakdown
        $device_stats = viewtracker_get_product_device_stats($product_id, $start_date, $end_date);
        
        include VIEWTRACKER_PLUGIN_DIR . 'admin/product-analytics.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include VIEWTRACKER_PLUGIN_DIR . 'admin/settings.php';
    }
}
