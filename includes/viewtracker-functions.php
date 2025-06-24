<?php
/**
 * Helper functions for ViewTracker
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get product views count
 *
 * @param int $product_id Product ID
 * @return int View count
 */
function viewtracker_get_product_views($product_id) {
    $views = get_post_meta($product_id, '_viewtracker_views', true);
    return empty($views) ? 0 : intval($views);
}

/**
 * Get most viewed products
 *
 * @param int $limit Number of products to return
 * @param string $category Category slug (optional)
 * @param string $tags Tag slugs (optional, comma-separated)
 * @return array Products
 */
function viewtracker_get_most_viewed_products($limit = 10, $category = '', $tags = '') {
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => absint($limit),
        'meta_key' => '_viewtracker_views',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_viewtracker_views',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ),
        ),
    );
    
    // Add tax query if categories or tags are specified
    $tax_query = array();
    
    if (!empty($category)) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => explode(',', $category),
            'operator' => 'IN',
        );
    }
    
    if (!empty($tags)) {
        $tax_query[] = array(
            'taxonomy' => 'product_tag',
            'field' => 'slug',
            'terms' => explode(',', $tags),
            'operator' => 'IN',
        );
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    return get_posts($args);
}

/**
 * Get most viewed products in a specific date range
 *
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @param int $limit Number of products to return
 * @param string $category Category slug (optional)
 * @param string $tags Tag slugs (optional, comma-separated)
 * @return array Products
 */
function viewtracker_get_most_viewed_products_in_range($start_date, $end_date, $limit = 10, $category = '', $tags = '') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    // Start building SQL query
    $sql = "
        SELECT p.*, COUNT(v.id) as view_count
        FROM {$wpdb->posts} p
        INNER JOIN {$table_name} v ON p.ID = v.product_id
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND DATE(v.date_viewed) >= %s
        AND DATE(v.date_viewed) <= %s
    ";
    
    $sql_args = array($start_date, $end_date);
    
    // Add tax filters if needed
    if (!empty($category) || !empty($tags)) {
        $sql .= "AND p.ID IN (
            SELECT object_id FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE ";
        
        $tax_conditions = array();
        
        if (!empty($category)) {
            $cat_slugs = explode(',', $category);
            $placeholders = implode(',', array_fill(0, count($cat_slugs), '%s'));
            $tax_conditions[] = "(tt.taxonomy = 'product_cat' AND t.slug IN ({$placeholders}))";
            $sql_args = array_merge($sql_args, $cat_slugs);
        }
        
        if (!empty($tags)) {
            $tag_slugs = explode(',', $tags);
            $placeholders = implode(',', array_fill(0, count($tag_slugs), '%s'));
            $tax_conditions[] = "(tt.taxonomy = 'product_tag' AND t.slug IN ({$placeholders}))";
            $sql_args = array_merge($sql_args, $tag_slugs);
        }
        
        $sql .= implode(' OR ', $tax_conditions);
        $sql .= ")";
    }
    
    $sql .= "
        GROUP BY p.ID
        ORDER BY view_count DESC
        LIMIT %d
    ";
    
    $sql_args[] = absint($limit);
    
    $prepared_sql = $wpdb->prepare($sql, $sql_args);
    
    return $wpdb->get_results($prepared_sql);
}

/**
 * Get total views in a specific date range
 *
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return int Total views
 */
function viewtracker_get_total_views_in_range($start_date, $end_date) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    $query = $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_name} WHERE DATE(date_viewed) >= %s AND DATE(date_viewed) <= %s",
        $start_date,
        $end_date
    );
    
    return $wpdb->get_var($query);
}

/**
 * Get device statistics in a specific date range
 *
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return array Device stats
 */
function viewtracker_get_device_stats($start_date, $end_date) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    $query = $wpdb->prepare(
        "SELECT device_type, COUNT(id) as count 
         FROM {$table_name} 
         WHERE DATE(date_viewed) >= %s 
         AND DATE(date_viewed) <= %s 
         GROUP BY device_type",
        $start_date,
        $end_date
    );
    
    $results = $wpdb->get_results($query);
    
    $stats = array(
        'desktop' => 0,
        'mobile' => 0,
        'tablet' => 0,
        'unknown' => 0
    );
    
    if ($results) {
        foreach ($results as $result) {
            $stats[$result->device_type] = intval($result->count);
        }
    }
    
    return $stats;
}

/**
 * Get daily views in a specific date range
 *
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return array Daily views
 */
function viewtracker_get_daily_views($start_date, $end_date) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    // Get actual views from database
    $query = $wpdb->prepare(
        "SELECT DATE(date_viewed) as date, COUNT(id) as count 
         FROM {$table_name} 
         WHERE DATE(date_viewed) >= %s 
         AND DATE(date_viewed) <= %s 
         GROUP BY DATE(date_viewed)
         ORDER BY date_viewed ASC",
        $start_date,
        $end_date
    );
    
    $results = $wpdb->get_results($query);
    
    // Convert results to associative array for quick lookup
    $views_by_date = array();
    foreach ($results as $row) {
        $views_by_date[$row->date] = $row->count;
    }
    
    // Create array with all dates in range, filling in zeros for missing dates
    $all_dates = array();
    $current = strtotime($start_date);
    $end = strtotime($end_date);
    
    while ($current <= $end) {
        $date = date('Y-m-d', $current);
        $all_dates[] = (object) array(
            'date' => $date,
            'count' => isset($views_by_date[$date]) ? $views_by_date[$date] : 0
        );
        $current = strtotime('+1 day', $current);
    }
    
    return $all_dates;
}

/**
 * Get product views in a specific date range
 *
 * @param int $product_id Product ID
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return int View count
 */
function viewtracker_get_product_views_in_range($product_id, $start_date, $end_date) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    $query = $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_name} 
         WHERE product_id = %d 
         AND DATE(date_viewed) >= %s 
         AND DATE(date_viewed) <= %s",
        $product_id,
        $start_date,
        $end_date
    );
    
    return $wpdb->get_var($query);
}

/**
 * Get product daily views in a specific date range
 *
 * @param int $product_id Product ID
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return array Daily views
 */
function viewtracker_get_product_daily_views($product_id, $start_date, $end_date) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    // Get actual views from database
    $query = $wpdb->prepare(
        "SELECT DATE(date_viewed) as date, COUNT(id) as count 
         FROM {$table_name} 
         WHERE product_id = %d 
         AND DATE(date_viewed) >= %s 
         AND DATE(date_viewed) <= %s 
         GROUP BY DATE(date_viewed)
         ORDER BY date_viewed ASC",
        $product_id,
        $start_date,
        $end_date
    );
    
    $results = $wpdb->get_results($query);
    
    // Convert results to associative array for quick lookup
    $views_by_date = array();
    foreach ($results as $row) {
        $views_by_date[$row->date] = $row->count;
    }
    
    // Create array with all dates in range, filling in zeros for missing dates
    $all_dates = array();
    $current = strtotime($start_date);
    $end = strtotime($end_date);
    
    while ($current <= $end) {
        $date = date('Y-m-d', $current);
        $all_dates[] = (object) array(
            'date' => $date,
            'count' => isset($views_by_date[$date]) ? $views_by_date[$date] : 0
        );
        $current = strtotime('+1 day', $current);
    }
    
    return $all_dates;
}

/**
 * Get product device statistics in a specific date range
 *
 * @param int $product_id Product ID
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return array Device stats
 */
function viewtracker_get_product_device_stats($product_id, $start_date, $end_date) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    $query = $wpdb->prepare(
        "SELECT device_type, COUNT(id) as count 
         FROM {$table_name} 
         WHERE product_id = %d 
         AND DATE(date_viewed) >= %s 
         AND DATE(date_viewed) <= %s 
         GROUP BY device_type",
        $product_id,
        $start_date,
        $end_date
    );
    
    $results = $wpdb->get_results($query);
    
    $stats = array(
        'desktop' => 0,
        'mobile' => 0,
        'tablet' => 0,
        'unknown' => 0
    );
    
    if ($results) {
        foreach ($results as $result) {
            $stats[$result->device_type] = intval($result->count);
        }
    }
    
    return $stats;
}

/**
 * Reset product views
 *
 * @param int $product_id Product ID
 * @return bool Success or failure
 */
function viewtracker_reset_product_views($product_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    // Delete detailed records
    $wpdb->delete(
        $table_name,
        array('product_id' => $product_id),
        array('%d')
    );
    
    // Reset post meta
    update_post_meta($product_id, '_viewtracker_views', 0);
    
    return true;
}

/**
 * Reset all product views
 *
 * @return bool Success or failure
 */
function viewtracker_reset_all_product_views() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'viewtracker_product_views';
    
    // Empty the table
    $wpdb->query("TRUNCATE TABLE {$table_name}");
    
    // Get all product IDs
    $product_ids = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    
    // Reset post meta for all products
    foreach ($product_ids as $product_id) {
        update_post_meta($product_id, '_viewtracker_views', 0);
    }
    
    return true;
}
