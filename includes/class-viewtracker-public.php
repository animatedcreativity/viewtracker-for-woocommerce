<?php
/**
 * Public-facing functionality for ViewTracker
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ViewTracker Public Class
 */
class ViewTracker_Public {
    /**
     * @var ViewTracker_Public The single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Current thumbnail size for shortcode
     * 
     * @var string
     */
    public $current_thumbnail_size = '';

    /**
     * Main instance
     * 
     * @return ViewTracker_Public
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register shortcode
        add_shortcode('viewtracker_popular_products', array($this, 'popular_products_shortcode'));
        
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Add sort options to shop
        add_filter('woocommerce_catalog_orderby', array($this, 'add_views_sorting_option'));
        add_filter('woocommerce_get_catalog_ordering_args', array($this, 'views_sorting_query'));
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        require_once VIEWTRACKER_PLUGIN_DIR . 'includes/widgets/class-viewtracker-popular-products-widget.php';
        register_widget('ViewTracker_Popular_Products_Widget');
    }

    /**
     * Popular products shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function popular_products_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'limit' => 5,
                'columns' => 4,
                'days' => 0, // 0 means all time
                'category' => '',
                'tags' => '',
                'thumbnail_size' => '', // Custom thumbnail size (overrides global setting)
            ),
            $atts,
            'viewtracker_popular_products'
        );
        
        $limit = absint($atts['limit']);
        $columns = absint($atts['columns']);
        $days = absint($atts['days']);
        $category = sanitize_text_field($atts['category']);
        $tags = sanitize_text_field($atts['tags']);
        $thumbnail_size = sanitize_text_field($atts['thumbnail_size']);
        
        // Set up query args
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => $limit,
            'meta_key' => '_viewtracker_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
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
        
        // For date filtered data
        if ($days > 0) {
            // We would need detailed views for date filtering
            // This is a simplified version for the shortcode
            $start_date = date('Y-m-d', strtotime("-{$days} days"));
            $products = viewtracker_get_most_viewed_products_in_range($start_date, date('Y-m-d'), $limit, $category, $tags);
            
            if (!empty($products)) {
                $product_ids = wp_list_pluck($products, 'ID');
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'ignore_sticky_posts' => 1,
                    'posts_per_page' => $limit,
                    'post__in' => $product_ids,
                    'orderby' => 'post__in',
                );
                
                // We don't need tax query here as the IDs are already filtered
                unset($args['tax_query']);
            }
        }
        
        // Buffer output
        ob_start();
        
        // Set up loop
        $products_loop = new WP_Query($args);
        
        if ($products_loop->have_posts()) {
            // Get thumbnail size from settings or shortcode parameter
            $thumb_size = !empty($thumbnail_size) ? $thumbnail_size : get_option('viewtracker_thumbnail_size', '');
            
            // Create inline style for thumbnails if size is specified
            $inline_style = '';
            if (!empty($thumb_size)) {
                // Prepare the size value
                $size_attr = is_numeric($thumb_size) ? $thumb_size . 'px' : $thumb_size;
                
                // Create CSS for product images
                $inline_style = ' style="--vt-thumb-size:' . esc_attr($size_attr) . ';"';
            }
            
            // Add viewtracker-specific wrapper classes for custom styling with inline styles
            echo '<div class="viewtracker-popular-products-wrapper"' . $inline_style . '>';
            
            // Add hidden style tag to handle the thumbnails directly
            if (!empty($thumb_size)) {
                echo '<style>
                    .viewtracker-popular-products-wrapper img.attachment-woocommerce_thumbnail,
                    .viewtracker-popular-products-wrapper img.wp-post-image,
                    .viewtracker-popular-products-wrapper .woocommerce-placeholder {
                        width: ' . esc_attr($size_attr) . ' !important;
                        height: ' . esc_attr($size_attr) . ' !important;
                        object-fit: cover !important;
                    }
                </style>';
            }
            
            // Set the columns for WooCommerce's loop
            $original_columns = wc_get_loop_prop('columns');
            wc_set_loop_prop('columns', $columns);
            
            // Start the WooCommerce product loop
            woocommerce_product_loop_start();
            
            while ($products_loop->have_posts()) {
                $products_loop->the_post();
                wc_get_template_part('content', 'product');
            }
            
            woocommerce_product_loop_end();
            
            // Restore original columns setting
            wc_set_loop_prop('columns', $original_columns);
            
            echo '</div><!-- .viewtracker-popular-products-wrapper -->';
            
            wp_reset_postdata();
        } else {
            echo '<p class="viewtracker-no-products">' . esc_html__('No products found', 'viewtracker-wc') . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Add views sorting option to shop
     * 
     * @param array $options Sorting options
     * @return array Modified options
     */
    public function add_views_sorting_option($options) {
        $options['popularity_views'] = __('Sort by popularity (views)', 'viewtracker-wc');
        return $options;
    }
    
    /**
     * Sort products by views
     * 
     * @param array $args Query args
     * @return array Modified args
     */
    public function views_sorting_query($args) {
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        
        if ($orderby === 'popularity_views') {
            $args['meta_key'] = '_viewtracker_views';
            $args['orderby'] = 'meta_value_num';
        }
        
        return $args;
    }
    
    /**
     * This space intentionally left empty.
     * Old thumbnail size modifier method has been replaced with inline CSS approach.
     */
}
