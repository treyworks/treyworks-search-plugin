<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Logs Class
 * 
 * Handles the admin interface for viewing and managing logs
 */
class Admin_Logs {
    /**
     * Initialize the admin logs functionality
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_treyworks_search_delete_logs', array(__CLASS__, 'ajax_delete_logs'));
        add_action('wp_ajax_treyworks_search_delete_all_logs', array(__CLASS__, 'ajax_delete_all_logs'));
    }
    
    /**
     * Add the logs page to the admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('Treyworks Search', 'treyworks-search'),
            __('Treyworks Search', 'treyworks-search'),
            'manage_options',
            'treyworks-search',
            array(__CLASS__, 'render_dashboard_page'),
            'dashicons-search',
            30
        );
        
        add_submenu_page(
            'treyworks-search',
            __('Logs', 'treyworks-search'),
            __('Logs', 'treyworks-search'),
            'manage_options',
            'treyworks-search-logs',
            array(__CLASS__, 'render_logs_page')
        );
    }
    
    /**
     * Enqueue scripts and styles for the logs page
     */
    public static function enqueue_scripts($hook) {
        // Load shared admin styles on all plugin pages
        if (strpos($hook, 'treyworks-search') !== false || $hook === 'toplevel_page_treyworks-search') {
            wp_enqueue_style('treyworks-search-admin', PLUGIN_URL . 'assets/css/admin-logs.css', array(), PLUGIN_VERSION);
        }
        
        // Load logs-specific scripts only on the logs page
        if ('treyworks-search_page_treyworks-search-logs' === $hook) {
            wp_enqueue_script('treyworks-search-admin-logs', PLUGIN_URL . 'assets/js/admin-logs.js', array('jquery'), PLUGIN_VERSION, true);
            
            wp_localize_script('treyworks-search-admin-logs', 'treyworksSearchLogs', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('treyworks_search_logs_nonce'),
                'confirmDelete' => __('Are you sure you want to delete the selected logs?', 'treyworks-search'),
                'confirmDeleteAll' => __('Are you sure you want to delete ALL logs? This cannot be undone.', 'treyworks-search')
            ));
        }
    }
    
    /**
     * Render the logs admin page
     */
    public static function render_logs_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current filters and pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10;
        $level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Get logs with filters
        $logs_data = DB_Logger::get_logs(array(
            'page' => $page,
            'per_page' => $per_page,
            'level' => $level,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'search' => $search
        ));
        
        // Get available log levels
        $log_levels = DB_Logger::get_log_levels();
        
        // Include template
        include PLUGIN_DIR . 'templates/admin-logs.php';
    }
    
    /**
     * AJAX handler for deleting specific logs
     */
    public static function ajax_delete_logs() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'treyworks_search_logs_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'treyworks-search')));
            exit;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'treyworks-search')));
            exit;
        }
        
        // Get and validate log IDs
        $log_ids = isset($_POST['log_ids']) ? array_map('intval', (array) $_POST['log_ids']) : array();
        
        if (empty($log_ids)) {
            wp_send_json_error(array('message' => __('No logs selected', 'treyworks-search')));
            exit;
        }
        
        // Delete logs
        $deleted = DB_Logger::delete_logs($log_ids);
        
        if ($deleted !== false) {
            wp_send_json_success(array(
                'message' => sprintf(__('%d logs deleted successfully', 'treyworks-search'), $deleted)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete logs', 'treyworks-search')));
        }
        
        exit;
    }
    
    /**
     * AJAX handler for deleting all logs
     */
    public static function ajax_delete_all_logs() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'treyworks_search_logs_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'treyworks-search')));
            exit;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this', 'treyworks-search')));
            exit;
        }
        
        // Delete all logs
        $deleted = DB_Logger::delete_all_logs();
        
        if ($deleted !== false) {
            wp_send_json_success(array(
                'message' => __('All logs deleted successfully', 'treyworks-search')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete logs', 'treyworks-search')));
        }
        
        exit;
    }
    
    /**
     * Render the dashboard page
     * This serves as the main plugin admin page
     */
    public static function render_dashboard_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Include dashboard template
        include PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
}
