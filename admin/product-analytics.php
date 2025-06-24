<?php
/**
 * ViewTracker Product Analytics Page
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap viewtracker-analytics-wrap">
    <h1>
        <?php echo esc_html__('Product Analytics', 'viewtracker-wc') . ': ' . esc_html($product->get_name()); ?>
    </h1>
    
    <div class="viewtracker-date-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="viewtracker-analytics">
            <input type="hidden" name="product" value="<?php echo absint($product_id); ?>">
            
            <div class="viewtracker-date-inputs">
                <label>
                    <?php esc_html_e('Start Date:', 'viewtracker-wc'); ?>
                    <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                </label>
                
                <label>
                    <?php esc_html_e('End Date:', 'viewtracker-wc'); ?>
                    <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                </label>
                
                <button type="submit" class="button button-primary"><?php esc_html_e('Filter', 'viewtracker-wc'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="viewtracker-dashboard">
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=viewtracker-analytics')); ?>" class="button">
                <?php esc_html_e('← Back to Analytics', 'viewtracker-wc'); ?>
            </a>
            <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="button" target="_blank">
                <?php esc_html_e('View Product Page', 'viewtracker-wc'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit')); ?>" class="button">
                <?php esc_html_e('Edit Product', 'viewtracker-wc'); ?>
            </a>
        </p>
        
        <div class="viewtracker-stats-boxes">
            <div class="viewtracker-stats-box">
                <h3><?php esc_html_e('Total Product Views', 'viewtracker-wc'); ?></h3>
                <div class="viewtracker-stats-value">
                    <?php echo intval($total_views); ?>
                </div>
            </div>
            
            <div class="viewtracker-stats-box">
                <h3><?php esc_html_e('Views in Selected Period', 'viewtracker-wc'); ?></h3>
                <div class="viewtracker-stats-value">
                    <?php echo intval($views_in_range); ?>
                </div>
            </div>
            
            <div class="viewtracker-stats-box">
                <h3><?php esc_html_e('Product Details', 'viewtracker-wc'); ?></h3>
                <div class="viewtracker-product-details">
                    <div class="viewtracker-product-image">
                        <?php 
                        // Try to get the product thumbnail first (works with external image plugins too)
                        $thumbnail = get_the_post_thumbnail($product_id, array(60, 60));
                        
                        // If no thumbnail is returned, use the placeholder
                        if (!$thumbnail || empty(trim($thumbnail))) {
                            echo wc_placeholder_img(array(60, 60));
                        } else {
                            echo $thumbnail;
                        }
                        ?>
                    </div>
                    <div class="viewtracker-product-info">
                        <p><strong><?php esc_html_e('SKU:', 'viewtracker-wc'); ?></strong> <?php echo $product->get_sku() ? esc_html($product->get_sku()) : '-'; ?></p>
                        <p><strong><?php esc_html_e('Price:', 'viewtracker-wc'); ?></strong> <?php echo $product->get_price_html(); ?></p>
                        <p><strong><?php esc_html_e('Stock:', 'viewtracker-wc'); ?></strong> <?php echo $product->get_stock_status(); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="viewtracker-charts">
            <div class="viewtracker-chart-container">
                <h3><?php esc_html_e('Daily Views', 'viewtracker-wc'); ?></h3>
                <div class="viewtracker-chart">
                    <canvas id="viewtracker-daily-chart"></canvas>
                </div>
            </div>
            
            <div class="viewtracker-chart-container">
                <h3><?php esc_html_e('Device Breakdown', 'viewtracker-wc'); ?></h3>
                <div class="viewtracker-chart">
                    <canvas id="viewtracker-device-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Daily views chart
    var dailyChart = new Chart(
        document.getElementById('viewtracker-daily-chart').getContext('2d'),
        {
            type: 'line',
            data: {
                labels: <?php echo json_encode(wp_list_pluck($daily_views, 'date')); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Views', 'viewtracker-wc'); ?>',
                    data: <?php echo json_encode(wp_list_pluck($daily_views, 'count')); ?>,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
    
    // Device breakdown chart
    var deviceChart = new Chart(
        document.getElementById('viewtracker-device-chart').getContext('2d'),
        {
            type: 'pie',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    data: [
                        <?php echo isset($device_stats['desktop']) ? intval($device_stats['desktop']) : 0; ?>,
                        <?php echo isset($device_stats['mobile']) ? intval($device_stats['mobile']) : 0; ?>,
                        <?php echo isset($device_stats['tablet']) ? intval($device_stats['tablet']) : 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        }
    );
});
</script>

<style>
.viewtracker-product-details {
    display: flex;
    align-items: center;
}
.viewtracker-product-image {
    margin-right: 15px;
}
.viewtracker-product-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border: 1px solid #ddd;
    padding: 3px;
}
.viewtracker-product-info p {
    margin: 5px 0;
}
</style>
