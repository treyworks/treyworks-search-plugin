jQuery(document).ready(function($) {
    // Handle conditional fields visibility
    function updateConditionalFields() {
        const llmProvider = $('#qss_plugin_llm_provider').val();
        
        $('.qss-conditional-field').each(function() {
            const $field = $(this);
            const dependsOn = $field.data('depends-on');
            const dependsValue = $field.data('depends-value');
            
            if (dependsOn === 'llm_provider') {
                $field.toggle(llmProvider === dependsValue);
            }
        });
    }

    // Update fields on load and when provider changes
    $('#qss_plugin_llm_provider').on('change', updateConditionalFields);
    updateConditionalFields();

    // Handle reveal button functionality
    $('.qss-reveal-api-key').on('click', function() {
        var inputField = $(this).siblings('.qss-api-key-field');
        var inputType = inputField.attr('type');
        if (inputType === 'password') {
            inputField.attr('type', 'text');
            $(this).text('Hide');
        } else {
            inputField.attr('type', 'password');
            $(this).text('Reveal');
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
