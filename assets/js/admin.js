jQuery(document).ready(function($) {
    // Handle conditional fields visibility
    function updateConditionalFields() {
        $('.qss-conditional-field').each(function() {
            const $field = $(this);
            const dependsOn = $field.data('depends-on');
            const dependsValue = $field.data('depends-value');
            
            // Find the dependency input
            // Try ID first (standard settings API)
            let $dependency = $('#qss_plugin_' + dependsOn);
            
            // If not found by ID, it might be a checkbox without that specific ID or handled differently
            if ($dependency.length === 0) {
                $dependency = $('[name="qss_plugin_' + dependsOn + '"]');
            }
            
            if ($dependency.length > 0) {
                let currentValue;
                
                if ($dependency.is(':checkbox')) {
                    currentValue = $dependency.is(':checked') ? '1' : '0';
                } else {
                    currentValue = $dependency.val();
                }
                
                // Compare values (convert both to strings for loose comparison)
                $field.toggle(String(currentValue) === String(dependsValue));
            }
        });
    }

    // Update fields on load
    updateConditionalFields();
    
    // Bind change events to all potential dependencies
    // We bind to any input that matches the pattern qss_plugin_*
    $('select[id^="qss_plugin_"], input[id^="qss_plugin_"]').on('change', updateConditionalFields);

    // Handle reveal button functionality
    $('.qss-reveal-api-key').on('click', function() {
        var inputField = $(this).siblings('.qss-api-key-field');
        var inputType = inputField.attr('type');
        var icon = $(this).find('.dashicons');
        
        if (inputType === 'password') {
            inputField.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            inputField.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // Generate random token
    $('.qss-generate-token').on('click', function() {
        // Generate a random token (32 characters)
        const generateRandomToken = () => {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let token = '';
            for (let i = 0; i < 32; i++) {
                token += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return token;
        };
        
        // Get the input field and update its value
        const inputField = $(this).siblings('.qss-api-key-field');
        const newToken = generateRandomToken();
        inputField.val(newToken);
        
        // Briefly show the token
        const originalType = inputField.attr('type');
        inputField.attr('type', 'text');
        setTimeout(() => {
            inputField.attr('type', originalType);
        }, 3000);
        
        // Show success message
        const $message = $('<div class="notice notice-success is-dismissible"><p>New token generated successfully! Save settings to apply changes.</p></div>');
        $(this).closest('form').before($message);
        
        // Auto-dismiss the message after 5 seconds
        setTimeout(() => {
            $message.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    });
});
