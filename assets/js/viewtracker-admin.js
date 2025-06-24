/**
 * ViewTracker Admin JavaScript
 */
(function($) {
    'use strict';
    
    /**
     * Reset product views from the meta box
     */
    $(document).on('click', '.reset-views', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var productId = button.data('product-id');
        var nonce = button.data('nonce');
        var message = button.closest('.inside').find('.viewtracker-message');
        
        if (!confirm(viewtracker_admin.i18n.reset_confirm)) {
            return;
        }
        
        button.prop('disabled', true);
        
        $.ajax({
            url: viewtracker_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'viewtracker_reset_product_views',
                product_id: productId,
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                
                if (response.success) {
                    message.removeClass('error').addClass('success').text(viewtracker_admin.i18n.reset_success).fadeIn();
                    $('.viewtracker-count').text('0');
                    
                    // Hide message after 3 seconds
                    setTimeout(function() {
                        message.fadeOut();
                    }, 3000);
                } else {
                    message.removeClass('success').addClass('error').text(viewtracker_admin.i18n.reset_error).fadeIn();
                }
            },
            error: function() {
                button.prop('disabled', false);
                message.removeClass('success').addClass('error').text(viewtracker_admin.i18n.reset_error).fadeIn();
            }
        });
    });
    
    /**
     * Export CSV from analytics page
     */
    $(document).on('click', '.viewtracker-export-csv', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var nonce = button.data('nonce');
        var startDate = $('input[name="start_date"]').val();
        var endDate = $('input[name="end_date"]').val();
        var productId = button.data('product-id') || 0;
        
        button.prop('disabled', true).text('Exporting...');
        
        $.ajax({
            url: viewtracker_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'viewtracker_export_csv',
                start_date: startDate,
                end_date: endDate,
                product_id: productId,
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false).text('Export CSV');
                
                if (response.success && response.data.download_url) {
                    // Create a temporary link element and trigger download
                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.download_url;
                    downloadLink.download = response.data.filename || 'viewtracker-export.csv';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                } else {
                    alert('Error exporting data. Please try again.');
                }
            },
            error: function() {
                button.prop('disabled', false).text('Export CSV');
                alert('Error exporting data. Please try again.');
            }
        });
    });
    
})(jQuery);
