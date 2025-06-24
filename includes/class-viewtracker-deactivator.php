<?php
/**
 * ViewTracker Deactivator
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ViewTracker Deactivator Class
 */
class ViewTracker_Deactivator {
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        self::unschedule_events();
    }
    
    /**
     * Unschedule events
     */
    private static function unschedule_events() {
        $timestamp = wp_next_scheduled('viewtracker_daily_cleanup');
        
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'viewtracker_daily_cleanup');
        }
    }
}
