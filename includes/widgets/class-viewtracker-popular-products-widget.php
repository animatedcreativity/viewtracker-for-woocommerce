<?php
/**
 * Popular Products Widget
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ViewTracker Popular Products Widget Class
 */
class ViewTracker_Popular_Products_Widget extends WP_Widget {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'viewtracker_popular_products',
            __('Most Viewed Products', 'viewtracker-wc'),
            array(
                'description' => __('Displays your most viewed products', 'viewtracker-wc'),
            )
        );
    }

    /**
     * Widget output
     *
     * @param array $args Widget arguments
     * @param array $instance Widget instance
     */
    public function widget($args, $instance) {
        $title = ! empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : __('Most Viewed Products', 'viewtracker-wc');
        $number = ! empty($instance['number']) ? absint($instance['number']) : 5;
        $days = ! empty($instance['days']) ? absint($instance['days']) : 0;
        $show_views = isset($instance['show_views']) ? (bool)$instance['show_views'] : true;
        $show_thumbnail = isset($instance['show_thumbnail']) ? (bool)$instance['show_thumbnail'] : true;
        $show_price = isset($instance['show_price']) ? (bool)$instance['show_price'] : true;
        
        // Get products
        if ($days > 0) {
            $start_date = date('Y-m-d', strtotime("-{$days} days"));
            $products = viewtracker_get_most_viewed_products_in_range($start_date, date('Y-m-d'), $number);
        } else {
            $products = viewtracker_get_most_viewed_products($number);
        }
        
        // Start output buffering
        ob_start();
        
        // Widget output
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        if ($products) {
            echo '<ul class="viewtracker-popular-products-widget">';
            
            foreach ($products as $product) {
                $product_obj = wc_get_product($product->ID);
                
                if (!$product_obj) {
                    continue;
                }
                
                $views = viewtracker_get_product_views($product->ID);
                
                echo '<li class="viewtracker-product">';
                
                if ($show_thumbnail) {
                    echo '<a href="' . esc_url(get_permalink($product->ID)) . '" class="viewtracker-thumbnail">';
                    echo $product_obj->get_image('thumbnail');
                    echo '</a>';
                }
                
                echo '<div class="viewtracker-product-info">';
                echo '<a href="' . esc_url(get_permalink($product->ID)) . '">' . esc_html(get_the_title($product->ID)) . '</a>';
                
                if ($show_views) {
                    echo '<span class="viewtracker-views">' . sprintf(_n('%d view', '%d views', $views, 'viewtracker-wc'), $views) . '</span>';
                }
                
                if ($show_price) {
                    echo '<span class="viewtracker-price">' . $product_obj->get_price_html() . '</span>';
                }
                
                echo '</div>';
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__('No products found', 'viewtracker-wc') . '</p>';
        }
        
        echo $args['after_widget'];
        
        // Output
        echo ob_get_clean();
    }

    /**
     * Widget form
     *
     * @param array $instance Widget instance
     */
    public function form($instance) {
        $title = ! empty($instance['title']) ? $instance['title'] : __('Most Viewed Products', 'viewtracker-wc');
        $number = ! empty($instance['number']) ? absint($instance['number']) : 5;
        $days = ! empty($instance['days']) ? absint($instance['days']) : 0;
        $show_views = isset($instance['show_views']) ? (bool)$instance['show_views'] : true;
        $show_thumbnail = isset($instance['show_thumbnail']) ? (bool)$instance['show_thumbnail'] : true;
        $show_price = isset($instance['show_price']) ? (bool)$instance['show_price'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'viewtracker-wc'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php esc_html_e('Number of products to show:', 'viewtracker-wc'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>" name="<?php echo esc_attr($this->get_field_name('number')); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($number); ?>" size="3">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('days')); ?>"><?php esc_html_e('Days (0 for all time):', 'viewtracker-wc'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('days')); ?>" name="<?php echo esc_attr($this->get_field_name('days')); ?>" type="number" step="1" min="0" value="<?php echo esc_attr($days); ?>" size="3">
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_views); ?> id="<?php echo esc_attr($this->get_field_id('show_views')); ?>" name="<?php echo esc_attr($this->get_field_name('show_views')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_views')); ?>"><?php esc_html_e('Display view count', 'viewtracker-wc'); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_thumbnail); ?> id="<?php echo esc_attr($this->get_field_id('show_thumbnail')); ?>" name="<?php echo esc_attr($this->get_field_name('show_thumbnail')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_thumbnail')); ?>"><?php esc_html_e('Display product thumbnail', 'viewtracker-wc'); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_price); ?> id="<?php echo esc_attr($this->get_field_id('show_price')); ?>" name="<?php echo esc_attr($this->get_field_name('show_price')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_price')); ?>"><?php esc_html_e('Display product price', 'viewtracker-wc'); ?></label>
        </p>
        <?php
    }

    /**
     * Update widget form
     *
     * @param array $new_instance New instance
     * @param array $old_instance Old instance
     * @return array Updated instance
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        $instance['days'] = (!empty($new_instance['days'])) ? absint($new_instance['days']) : 0;
        $instance['show_views'] = (!empty($new_instance['show_views'])) ? 1 : 0;
        $instance['show_thumbnail'] = (!empty($new_instance['show_thumbnail'])) ? 1 : 0;
        $instance['show_price'] = (!empty($new_instance['show_price'])) ? 1 : 0;
        
        return $instance;
    }
}
