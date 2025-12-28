<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get 30-day activity data
$activity_data = DB_Logger::get_30_day_activity();
?>

<div class="wrap treyworks-search-dashboard">
    <?php require_once PLUGIN_DIR . 'templates/admin-header.php'; ?>
    
    <div class="treyworks-content">
        <!-- 30-Day Activity Chart -->
        <div class="treyworks-admin-card treyworks-chart-card">
            <div class="treyworks-admin-card-header">
                <h2><span class="dashicons dashicons-chart-line"></span> <?php _e('Request Activity (Past 30 Days)', 'treyworks-search'); ?></h2>
            </div>
            <div class="treyworks-admin-card-body">
                <div class="treyworks-chart-legend">
                    <span class="legend-item"><span class="legend-color" style="background-color: #4A90E2;"></span> <?php _e('Total Requests', 'treyworks-search'); ?></span>
                    <span class="legend-item"><span class="legend-color" style="background-color: #275c4d;"></span> <?php _e('Success', 'treyworks-search'); ?></span>
                    <span class="legend-item"><span class="legend-color" style="background-color: #E74C3C;"></span> <?php _e('Errors', 'treyworks-search'); ?></span>
                    <span class="legend-item"><span class="legend-color" style="background-color: #F39C12;"></span> <?php _e('Blocked', 'treyworks-search'); ?></span>
                </div>
                <div class="treyworks-chart-container">
                    <canvas id="treyworks-activity-chart"></canvas>
                </div>
            </div>
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
    </div> <!-- Close treyworks-admin -->
</div>

