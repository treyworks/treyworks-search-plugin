<?php
/**
 * Admin Header Template
 */
$current_tab = isset($_GET['page']) ? $_GET['page'] : 'treyworks-search';

$header_subtitles = array(
    'treyworks-search'      => __('AI-Powered Search & Summarization Overview', 'treyworks-search'),
    'qss-plugin-settings'   => __('Configure search behavior, AI models, and integrations', 'treyworks-search'),
    'treyworks-search-logs' => __('Monitor search activity and system events', 'treyworks-search')
);

$current_subtitle = isset($header_subtitles[$current_tab]) ? $header_subtitles[$current_tab] : __('AI-Powered Search & Summarization', 'treyworks-search');
?>
<div class="treyworks-admin">
    <div class="treyworks-header">
        <div class="treyworks-header-title">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="subtitle"><?php echo esc_html($current_subtitle); ?></div>
        </div>
        <div class="treyworks-header-logo">
            <img src="<?php echo PLUGIN_URL . 'assets/images/logo.png'; ?>" alt="Treyworks">
        </div>
    </div>

    <div class="treyworks-nav">
        <a href="<?php echo admin_url('admin.php?page=treyworks-search'); ?>" class="treyworks-nav-item <?php echo $current_tab === 'treyworks-search' ? 'active' : ''; ?>">
            <?php _e('Dashboard', 'treyworks-search'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=qss-plugin-settings'); ?>" class="treyworks-nav-item <?php echo $current_tab === 'qss-plugin-settings' ? 'active' : ''; ?>">
            <?php _e('Settings', 'treyworks-search'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=treyworks-search-logs'); ?>" class="treyworks-nav-item <?php echo $current_tab === 'treyworks-search-logs' ? 'active' : ''; ?>">
            <?php _e('Logs', 'treyworks-search'); ?>
        </a>
    </div>
