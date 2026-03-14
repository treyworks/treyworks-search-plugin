<?php
/**
 * Admin Options Page Template
 * This file renders the HTML for the plugin settings/admin options page.
 */
?>
<div class="wrap">
    <?php require_once PLUGIN_DIR . 'templates/admin-header.php'; ?>
    
    <div class="treyworks-content">
        <form action="options.php" method="post">
            <?php
                settings_fields('qss_plugin_settings');
                do_settings_sections('qss-plugin-settings');
                submit_button('Save Settings');
            ?>
        </form>
    </div>
    <div id="qss-extract-system-prompt-modal" class="qss-admin-modal" hidden>
        <div class="qss-admin-modal__backdrop" data-qss-modal-close="qss-extract-system-prompt-modal"></div>
        <div class="qss-admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qss-extract-system-prompt-modal-title">
            <div class="qss-admin-modal__header">
                <h2 id="qss-extract-system-prompt-modal-title"><?php echo esc_html__('Built-in Extraction Prompt', 'qss-plugin'); ?></h2>
                <button type="button" class="button-link qss-admin-modal__close" data-qss-modal-close="qss-extract-system-prompt-modal" aria-label="<?php echo esc_attr__('Close modal', 'qss-plugin'); ?>">&times;</button>
            </div>
            <div class="qss-admin-modal__body">
                <pre><?php echo esc_html(QSS_Default_Prompts::EXTRACT_SEARCH_TERM); ?></pre>
            </div>
        </div>
    </div>
    <div id="qss-summary-system-prompt-modal" class="qss-admin-modal" hidden>
        <div class="qss-admin-modal__backdrop" data-qss-modal-close="qss-summary-system-prompt-modal"></div>
        <div class="qss-admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qss-summary-system-prompt-modal-title">
            <div class="qss-admin-modal__header">
                <h2 id="qss-summary-system-prompt-modal-title"><?php echo esc_html__('Built-in Search Summary System Prompt', 'qss-plugin'); ?></h2>
                <button type="button" class="button-link qss-admin-modal__close" data-qss-modal-close="qss-summary-system-prompt-modal" aria-label="<?php echo esc_attr__('Close modal', 'qss-plugin'); ?>">&times;</button>
            </div>
            <div class="qss-admin-modal__body">
                <pre><?php echo esc_html(QSS_Default_Prompts::CREATE_SUMMARY_SYSTEM); ?></pre>
            </div>
        </div>
    </div>
    <div id="qss-answer-system-prompt-modal" class="qss-admin-modal" hidden>
        <div class="qss-admin-modal__backdrop" data-qss-modal-close="qss-answer-system-prompt-modal"></div>
        <div class="qss-admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qss-answer-system-prompt-modal-title">
            <div class="qss-admin-modal__header">
                <h2 id="qss-answer-system-prompt-modal-title"><?php echo esc_html__('Built-in Answer Prompt', 'qss-plugin'); ?></h2>
                <button type="button" class="button-link qss-admin-modal__close" data-qss-modal-close="qss-answer-system-prompt-modal" aria-label="<?php echo esc_attr__('Close modal', 'qss-plugin'); ?>">&times;</button>
            </div>
            <div class="qss-admin-modal__body">
                <pre><?php echo esc_html(QSS_Default_Prompts::GET_ANSWER); ?></pre>
            </div>
        </div>
    </div>
    </div> <!-- Close treyworks-admin -->
</div>
