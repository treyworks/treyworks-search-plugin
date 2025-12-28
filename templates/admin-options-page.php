<?php
/**
 * Admin Options Page Template
 * This file renders the HTML for the plugin settings/admin options page.
 */
?>
<div class="wrap">
    <?php require_once PLUGIN_DIR . 'templates/admin-header.php'; ?>
    
    <div class="treyworks-content">
        <!-- Settings Form -->
        <form action="options.php" method="post">
            <?php
                settings_fields('qss_plugin_settings');
                do_settings_sections('qss-plugin-settings');
                submit_button('Save Settings');
            ?>
        </form>
    </div>
    </div> <!-- Close treyworks-admin -->
</div>
