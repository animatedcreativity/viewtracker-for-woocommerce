<?php
/**
 * ViewTracker Analytics Page
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current date filters
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
?>

<div class="wrap viewtracker-analytics-wrap">
    <h1><?php esc_html_e('ViewTracker Analytics', 'viewtracker-wc'); ?></h1>
    
    <div class="viewtracker-date-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="viewtracker-analytics">
            <?php if (isset($_GET['product'])): ?>
                <input type="hidden" name="product" value="<?php echo absint($_GET['product']); ?>">
            <?php endif; ?>
            
            <div class="viewtracker-date-inputs">
                <label>
                    <?php esc_html_e('Start Date:', 'viewtracker-wc'); ?>
                    <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                </label>
                
                <label>
                    <?php esc_html_e('End Date:', 'viewtracker-wc'); ?>
                    <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                </label>
                
                <button type="submit" class="button"><?php esc_html_e('Apply', 'viewtracker-wc'); ?></button>
            </div>
            
            <div class="viewtracker-date-presets">
                <a href="<?php echo esc_url(add_query_arg(array('start_date' => date('Y-m-d', strtotime('-7 days')), 'end_date' => date('Y-m-d')))); ?>" class="button button-secondary"><?php esc_html_e('Last 7 Days', 'viewtracker-wc'); ?></a>
                <a href="<?php echo esc_url(add_query_arg(array('start_date' => date('Y-m-d', strtotime('-30 days')), 'end_date' => date('Y-m-d')))); ?>" class="button button-secondary"><?php esc_html_e('Last 30 Days', 'viewtracker-wc'); ?></a>
                <a href="<?php echo esc_url(add_query_arg(array('start_date' => date('Y-m-d', strtotime('-90 days')), 'end_date' => date('Y-m-d')))); ?>" class="button button-secondary"><?php esc_html_e('Last 90 Days', 'viewtracker-wc'); ?></a>
            </div>
        </form>
    </div>
    
    <div class="viewtracker-dashboard">
        <?php if (isset($product) && $product): ?>
            <!-- Single Product Analytics -->
            <div class="viewtracker-page-header">
                <h2>
                    <?php echo esc_html(get_the_title($product->get_id())); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=viewtracker-analytics')); ?>" class="button"><?php esc_html_e('Back to Analytics', 'viewtracker-wc'); ?></a>
                </h2>
            </div>
            
            <div class="viewtracker-stats-boxes">
                <div class="viewtracker-stats-box">
                    <h3><?php esc_html_e('Total Views', 'viewtracker-wc'); ?></h3>
                    <div class="viewtracker-stat-number">
                        <?php echo esc_html(number_format_i18n($total_views)); ?>
                    </div>
                </div>
                
                <div class="viewtracker-stats-box">
                    <h3><?php esc_html_e('Views in Selected Period', 'viewtracker-wc'); ?></h3>
                    <div class="viewtracker-stat-number">
                        <?php echo esc_html(number_format_i18n($views_in_range)); ?>
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
                            labels: ['<?php esc_html_e('Desktop', 'viewtracker-wc'); ?>', '<?php esc_html_e('Mobile', 'viewtracker-wc'); ?>', '<?php esc_html_e('Tablet', 'viewtracker-wc'); ?>', '<?php esc_html_e('Unknown', 'viewtracker-wc'); ?>'],
                            datasets: [{
                                data: [
                                    <?php echo absint($device_stats['desktop']); ?>,
                                    <?php echo absint($device_stats['mobile']); ?>,
                                    <?php echo absint($device_stats['tablet']); ?>,
                                    <?php echo absint($device_stats['unknown']); ?>
                                ],
                                backgroundColor: [
                                    '#2271b1',
                                    '#3fc768',
                                    '#e6a23c',
                                    '#c45850'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    }
                );
            });
            </script>
            
        <?php else: ?>
            <!-- General Analytics -->
            <div class="viewtracker-stats-boxes">
                <div class="viewtracker-stats-box">
                    <h3><?php esc_html_e('Total Views in Period', 'viewtracker-wc'); ?></h3>
                    <div class="viewtracker-stat-number">
                        <?php echo esc_html(number_format_i18n($total_views)); ?>
                    </div>
                </div>
                
                <div class="viewtracker-stats-box">
                    <h3><?php esc_html_e('Device Breakdown', 'viewtracker-wc'); ?></h3>
                    <div class="viewtracker-stat-small">
                        <span><?php esc_html_e('Desktop', 'viewtracker-wc'); ?>: <?php echo esc_html(number_format_i18n($device_stats['desktop'])); ?></span>
                        <span><?php esc_html_e('Mobile', 'viewtracker-wc'); ?>: <?php echo esc_html(number_format_i18n($device_stats['mobile'])); ?></span>
                        <span><?php esc_html_e('Tablet', 'viewtracker-wc'); ?>: <?php echo esc_html(number_format_i18n($device_stats['tablet'])); ?></span>
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
            
            <div class="viewtracker-table-container">
                <h3><?php esc_html_e('Most Viewed Products', 'viewtracker-wc'); ?></h3>
                
                <?php if (!empty($products)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="60"><?php esc_html_e('Image', 'viewtracker-wc'); ?></th>
                                <th><?php esc_html_e('Product', 'viewtracker-wc'); ?></th>
                                <th width="120"><?php esc_html_e('SKU', 'viewtracker-wc'); ?></th>
                                <th width="100"><?php esc_html_e('Views', 'viewtracker-wc'); ?></th>
                                <th width="120"><?php esc_html_e('Product Link', 'viewtracker-wc'); ?></th>
                                <th width="120"><?php esc_html_e('Actions', 'viewtracker-wc'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): 
                                $wc_product = wc_get_product($product->ID);
                                $sku = $wc_product ? $wc_product->get_sku() : '';
                                $product_url = get_permalink($product->ID);
                                
                                // Always try get_the_post_thumbnail first as it's properly hooked by Featured Image with URL plugin
                                // The plugin uses post_thumbnail_html filter with priority 999
                                // Even when has_post_thumbnail() is false, this might still return an image with external URL
                                $thumbnail = get_the_post_thumbnail($product->ID, array(40, 40));
                                
                                // If no thumbnail found, try WooCommerce's method
                                if (empty($thumbnail) && $wc_product) {
                                    $thumbnail = $wc_product->get_image(array(40, 40));
                                }
                                
                                // Last resort - use placeholder
                                if (empty($thumbnail)) {
                                    $thumbnail = wc_placeholder_img(array(40, 40));
                                }
                            ?>
                                <tr>
                                    <td>
                                        <?php echo $thumbnail; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($product->ID)); ?>">
                                            <?php echo esc_html($product->post_title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo esc_html($sku); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($product->view_count); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($product_url); ?>" target="_blank" class="button button-small">
                                            <?php esc_html_e('View Page', 'viewtracker-wc'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=viewtracker-analytics&product=' . $product->ID)); ?>" class="button button-small">
                                            <?php esc_html_e('Analytics', 'viewtracker-wc'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php esc_html_e('No product views recorded in this period.', 'viewtracker-wc'); ?></p>
                <?php endif; ?>
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
                            labels: ['<?php esc_html_e('Desktop', 'viewtracker-wc'); ?>', '<?php esc_html_e('Mobile', 'viewtracker-wc'); ?>', '<?php esc_html_e('Tablet', 'viewtracker-wc'); ?>', '<?php esc_html_e('Unknown', 'viewtracker-wc'); ?>'],
                            datasets: [{
                                data: [
                                    <?php echo absint($device_stats['desktop']); ?>,
                                    <?php echo absint($device_stats['mobile']); ?>,
                                    <?php echo absint($device_stats['tablet']); ?>,
                                    <?php echo absint($device_stats['unknown']); ?>
                                ],
                                backgroundColor: [
                                    '#2271b1',
                                    '#3fc768',
                                    '#e6a23c',
                                    '#c45850'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    }
                );
            });
            </script>
        <?php endif; ?>
    </div>
</div>

<style>
.viewtracker-date-filter {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.viewtracker-date-inputs {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.viewtracker-date-inputs label {
    margin-right: 15px;
}

.viewtracker-stats-boxes {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.viewtracker-stats-box {
    flex: 1;
    min-width: 200px;
    margin: 0 10px 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    text-align: center;
}

.viewtracker-stat-number {
    font-size: 36px;
    font-weight: 600;
    color: #2271b1;
    margin-top: 10px;
}

.viewtracker-stat-small {
    display: flex;
    flex-direction: column;
    font-size: 16px;
    margin-top: 10px;
}

.viewtracker-stat-small span {
    margin-bottom: 5px;
}

.viewtracker-charts {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.viewtracker-chart-container {
    flex: 1;
    min-width: 300px;
    margin: 0 10px 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.viewtracker-chart {
    height: 300px;
    position: relative;
}

.viewtracker-table-container {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.viewtracker-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.viewtracker-page-header h2 {
    margin: 0;
}

@media screen and (max-width: 782px) {
    .viewtracker-date-inputs {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .viewtracker-date-inputs label {
        margin-bottom: 10px;
        margin-right: 0;
    }
}
</style>
