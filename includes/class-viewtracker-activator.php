<?php
/**
 * ViewTracker Activator
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ViewTracker Activator Class
 */
class ViewTracker_Activator {
    /**
     * Activate the plugin
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::schedule_events();
    }
    
    /**
     * Create tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'viewtracker_product_views';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            session_id varchar(255) NOT NULL DEFAULT '',
            ip_address varchar(45) NOT NULL DEFAULT '',
            user_agent varchar(255) NOT NULL DEFAULT '',
            device_type varchar(20) NOT NULL DEFAULT 'desktop',
            referer varchar(255) NOT NULL DEFAULT '',
            date_viewed datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY date_viewed (date_viewed)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        // Set default options if they don't exist
        if (get_option('viewtracker_ajax_tracking') === false) {
            add_option('viewtracker_ajax_tracking', 'yes');
        }
        
        if (get_option('viewtracker_exclude_admin') === false) {
            add_option('viewtracker_exclude_admin', 'yes');
        }
        
        if (get_option('viewtracker_duplicate_protection') === false) {
            add_option('viewtracker_duplicate_protection', 'yes');
        }
        
        if (get_option('viewtracker_data_retention') === false) {
            add_option('viewtracker_data_retention', '365'); // 365 days default retention
        }
        
        if (get_option('viewtracker_widget_count') === false) {
            add_option('viewtracker_widget_count', '5');
        }
        
        if (get_option('viewtracker_reset_on_update') === false) {
            add_option('viewtracker_reset_on_update', 'no');
        }
    }
    
    /**
     * Schedule events
     */
    private static function schedule_events() {
        if (!wp_next_scheduled('viewtracker_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'viewtracker_daily_cleanup');
        }
    }
}
