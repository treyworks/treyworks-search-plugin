/**
 * Admin API JavaScript
 */

(function($) {
    'use strict';

    // DOM ready
    $(function() {
        // Copy endpoint URL to clipboard
        $('.copy-endpoint').on('click', function() {
            const endpoint = $(this).data('endpoint');
            const $button = $(this);
            const originalText = $button.text();
            
            // Create temporary input element
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(endpoint).select();
            
            try {
                // Copy to clipboard
                document.execCommand('copy');
                
                // Show success feedback
                $button.text('Copied!');
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            
            // Remove temporary input
            $temp.remove();
        });
    });
})(jQuery);
