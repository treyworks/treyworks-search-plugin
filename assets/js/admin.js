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
});
