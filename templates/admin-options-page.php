<?php
/**
 * Admin Options Page Template
 * This file renders the HTML for the plugin settings/admin options page.
 */
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Settings Form -->
    <form action="options.php" method="post">
        <?php
            settings_fields('qss_plugin_settings');
            do_settings_sections('qss-plugin-settings');
            submit_button('Save Settings');
        ?>
    </form>

    <!-- Clear Log Form -->
    <?php if (get_option('qss_plugin_enable_logging')): ?>
        <div class="qss-log-management" style="margin-top: 2em; padding: 1em; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2><?php _e('Log Management', 'qss-plugin'); ?></h2>
            <form method="post" style="margin-top: 1em;">
                <?php wp_nonce_field('qss_clear_log'); ?>
                <input type="submit" name="clear_log" class="button button-secondary" value="<?php esc_attr_e('Clear Log File', 'qss-plugin'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear the log file?', 'qss-plugin'); ?>');" />
            </form>
        </div>
    <?php endif; ?>
</div>
