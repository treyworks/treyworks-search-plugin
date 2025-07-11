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
</div>
