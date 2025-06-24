<?php
/**
 * ViewTracker Settings Page
 *
 * @package ViewTracker_For_WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('ViewTracker for WooCommerce Settings', 'viewtracker-wc'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('viewtracker_settings'); ?>
        <?php do_settings_sections('viewtracker_settings'); ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('AJAX Tracking', 'viewtracker-wc'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="viewtracker_ajax_tracking" value="yes" <?php checked('yes', get_option('viewtracker_ajax_tracking')); ?> />
                        <?php esc_html_e('Use AJAX for tracking views (recommended for compatibility with cache plugins)', 'viewtracker-wc'); ?>
                    </label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Exclude Admin Views', 'viewtracker-wc'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="viewtracker_exclude_admin" value="yes" <?php checked('yes', get_option('viewtracker_exclude_admin')); ?> />
                        <?php esc_html_e('Do not count views from administrators', 'viewtracker-wc'); ?>
                    </label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Duplicate Protection', 'viewtracker-wc'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="viewtracker_duplicate_protection" value="yes" <?php checked('yes', get_option('viewtracker_duplicate_protection')); ?> />
                        <?php esc_html_e('Prevent counting multiple views from the same user in a session', 'viewtracker-wc'); ?>
                    </label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Thumbnail Size', 'viewtracker-wc'); ?></th>
                <td>
                    <input type="text" name="viewtracker_thumbnail_size" value="<?php echo esc_attr(get_option('viewtracker_thumbnail_size', '')); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Custom size for product thumbnails in shortcodes and widgets. Leave empty to use theme default.', 'viewtracker-wc'); ?><br>
                        <?php esc_html_e('Examples: 100px, 80%, 10rem, etc.', 'viewtracker-wc'); ?>
                    </p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Data Retention', 'viewtracker-wc'); ?></th>
                <td>
                    <select name="viewtracker_data_retention">
                        <option value="30" <?php selected('30', get_option('viewtracker_data_retention')); ?>><?php esc_html_e('30 days', 'viewtracker-wc'); ?></option>
                        <option value="90" <?php selected('90', get_option('viewtracker_data_retention')); ?>><?php esc_html_e('90 days', 'viewtracker-wc'); ?></option>
                        <option value="180" <?php selected('180', get_option('viewtracker_data_retention')); ?>><?php esc_html_e('180 days', 'viewtracker-wc'); ?></option>
                        <option value="365" <?php selected('365', get_option('viewtracker_data_retention')); ?>><?php esc_html_e('1 year', 'viewtracker-wc'); ?></option>
                        <option value="0" <?php selected('0', get_option('viewtracker_data_retention')); ?>><?php esc_html_e('Forever', 'viewtracker-wc'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('How long to keep detailed view data in the database', 'viewtracker-wc'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Dashboard Widget Count', 'viewtracker-wc'); ?></th>
                <td>
                    <input type="number" name="viewtracker_widget_count" value="<?php echo esc_attr(get_option('viewtracker_widget_count', 5)); ?>" min="1" max="20" />
                    <p class="description"><?php esc_html_e('Number of products to show in the dashboard widget', 'viewtracker-wc'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Reset on Product Update', 'viewtracker-wc'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="viewtracker_reset_on_update" value="yes" <?php checked('yes', get_option('viewtracker_reset_on_update')); ?> />
                        <?php esc_html_e('Reset view count when a product is updated', 'viewtracker-wc'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php esc_html_e('Reset View Data', 'viewtracker-wc'); ?></h2>
    <p><?php esc_html_e('Use these buttons to reset view data. This action cannot be undone.', 'viewtracker-wc'); ?></p>
    
    <div class="viewtracker-reset-buttons">
        <button type="button" id="viewtracker-reset-all" class="button button-secondary" data-nonce="<?php echo wp_create_nonce('viewtracker-reset-all'); ?>">
            <?php esc_html_e('Reset All View Data', 'viewtracker-wc'); ?>
        </button>
    </div>
    
    <div id="viewtracker-reset-message" class="notice" style="display:none;"></div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#viewtracker-reset-all').on('click', function() {
            if (!confirm('<?php esc_html_e('Are you sure you want to reset all view data? This action cannot be undone.', 'viewtracker-wc'); ?>')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'viewtracker_reset_all',
                    nonce: button.data('nonce')
                },
                success: function(response) {
                    button.prop('disabled', false);
                    
                    if (response.success) {
                        $('#viewtracker-reset-message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    } else {
                        $('#viewtracker-reset-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function() {
                    button.prop('disabled', false);
                    
                    $('#viewtracker-reset-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p><?php esc_html_e('An error occurred while processing your request.', 'viewtracker-wc'); ?></p>')
                        .show();
                }
            });
        });
    });
    </script>
</div>
