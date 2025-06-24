/**
 * ViewTracker AJAX JavaScript
 * Handles AJAX-based view tracking for products
 */
(function($) {
    'use strict';
    
    /**
     * Record product view via AJAX
     */
    $(document).ready(function() {
        // Only execute if we have the viewtracker_ajax object
        if (typeof viewtracker_ajax === 'undefined') {
            return;
        }
        
        // Don't track if we don't have a product ID
        if (!viewtracker_ajax.product_id) {
            return;
        }
        
        // Delay tracking to give page a chance to fully load
        setTimeout(function() {
            $.ajax({
                url: viewtracker_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'viewtracker_record_view',
                    product_id: viewtracker_ajax.product_id,
                    nonce: viewtracker_ajax.nonce
                },
                success: function(response) {
                    // Optional: console.log('View recorded for product #' + viewtracker_ajax.product_id);
                }
            });
        }, 1000);
    });
    
})(jQuery);
