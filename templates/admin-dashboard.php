<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap treyworks-search-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Welcome to Treyworks Search for WordPress! Use the tabs below to configure the plugin and manage logs.', 'treyworks-search'); ?></p>
    </div>
    
    <div class="treyworks-dashboard-wrapper">
        <div class="treyworks-admin-card">
            <div class="treyworks-admin-card-header">
                <h2><span class="dashicons dashicons-admin-settings"></span> <?php _e('Plugin Settings', 'treyworks-search'); ?></h2>
            </div>
            <div class="treyworks-admin-card-body">
                <p><?php _e('Configure the core settings of the plugin including API keys, search options, and prompts.', 'treyworks-search'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=qss-plugin-settings'); ?>" class="button button-primary">
                    <?php _e('Manage Settings', 'treyworks-search'); ?>
                </a>
            </div>
        </div>
        
        <div class="treyworks-admin-card">
            <div class="treyworks-admin-card-header">
                <h2><span class="dashicons dashicons-list-view"></span> <?php _e('View Logs', 'treyworks-search'); ?></h2>
            </div>
            <div class="treyworks-admin-card-body">
                <p><?php _e('Review and manage operation logs for the plugin to monitor activity and troubleshoot issues.', 'treyworks-search'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=treyworks-search-logs'); ?>" class="button button-primary">
                    <?php _e('View Logs', 'treyworks-search'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="treyworks-dashboard-stats">
        <h2><?php _e('Plugin Information', 'treyworks-search'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Plugin Version', 'treyworks-search'); ?></th>
                    <td><?php echo PLUGIN_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Log Database', 'treyworks-search'); ?></th>
                    <td>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'treyworks_search_logs';
                        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                        
                        if ($table_exists) {
                            $log_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                            echo '<span class="dashicons dashicons-yes" style="color:green;"></span> ';
                            echo sprintf(__('Table exists with %s log entries', 'treyworks-search'), number_format_i18n($log_count));
                        } else {
                            echo '<span class="dashicons dashicons-no" style="color:red;"></span> ';
                            echo __('Table not found. Please deactivate and reactivate the plugin.', 'treyworks-search');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Logging Status', 'treyworks-search'); ?></th>
                    <td>
                        <?php if (get_option('qss_plugin_enable_logging', false)): ?>
                            <span class="dashicons dashicons-yes" style="color:green;"></span> <?php _e('Enabled', 'treyworks-search'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color:orange;"></span> <?php _e('Disabled', 'treyworks-search'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.treyworks-dashboard-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.treyworks-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    width: calc(50% - 10px);
    min-width: 300px;
}

.treyworks-admin-card-header {
    border-bottom: 1px solid #ccd0d4;
    padding: 15px;
    background: #f8f9fa;
}

.treyworks-admin-card-header h2 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
}

.treyworks-admin-card-header h2 .dashicons {
    margin-right: 10px;
}

.treyworks-admin-card-body {
    padding: 15px;
}

.treyworks-dashboard-stats {
    margin-top: 30px;
}

@media screen and (max-width: 782px) {
    .treyworks-admin-card {
        width: 100%;
    }
}
</style>
