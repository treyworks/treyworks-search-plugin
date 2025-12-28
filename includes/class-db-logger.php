<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Logger Class
 * 
 * Handles all logging operations using a custom database table
 * instead of a flat text file.
 */
class DB_Logger {
    // Table name without prefix
    private static $table_name = 'treyworks_search_logs';
    
    // Log levels
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_DEBUG = 'debug';
    
    /**
     * Initialize the logger
     * Creates the database table if it doesn't exist
     */
    public static function initialize() {
        // Check if we need to create the table
        if (get_option('treyworks_search_db_version') != '1.0') {
            self::create_table();
        }
    }
    
    /**
     * Create the database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            log_level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY log_level (log_level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Save the database version
        update_option('treyworks_search_db_version', '1.0');
    }
    
    /**
     * Drop the database table
     */
    public static function drop_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Delete the database version option
        delete_option('treyworks_search_db_version');
    }
    
    /**
     * Logs a message to the database
     * 
     * @param string $message The log message
     * @param string $level The log level (info, warning, error, debug)
     * @param array $context Additional context data (will be stored as JSON)
     * @return bool Whether the log was successfully added
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = []) {
        // Check if logging is enabled
        if (!get_option('qss_plugin_enable_logging', false)) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Convert array or object message to a readable string format
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        
        // Format context as JSON if it's not empty
        $context_json = !empty($context) ? wp_json_encode($context) : null;
        
        // Insert log into the database
        $result = $wpdb->insert(
            $table_name,
            [
                'log_level' => $level,
                'message' => $message,
                'context' => $context_json,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Get logs with filtering and pagination
     * 
     * @param array $args Query arguments
     * @return array Logs and pagination data
     */
    public static function get_logs($args = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Default arguments
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'level' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $where = [];
        $values = [];
        
        // Filter by level
        if (!empty($args['level'])) {
            $where[] = 'log_level = %s';
            $values[] = $args['level'];
        }
        
        // Filter by date range
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Filter by search term
        if (!empty($args['search'])) {
            $where[] = '(message LIKE %s OR context LIKE %s)';
            $values[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }
        
        // Build the WHERE clause
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Count total items
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        if (!empty($values)) {
            $count_query = $wpdb->prepare($count_query, $values);
        }
        $total_items = $wpdb->get_var($count_query);
        
        // Pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Sanitize order and orderby
        $valid_columns = ['id', 'log_level', 'message', 'created_at'];
        $args['orderby'] = in_array($args['orderby'], $valid_columns) ? $args['orderby'] : 'created_at';
        $args['order'] = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Get items
        $query = "SELECT * FROM $table_name $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $query_values = array_merge($values, [$args['per_page'], $offset]);
        $prepared_query = $wpdb->prepare($query, $query_values);
        $items = $wpdb->get_results($prepared_query);
        
        // Process items to convert JSON context back to arrays
        foreach ($items as &$item) {
            if (!empty($item->context)) {
                $item->context = json_decode($item->context, true);
            } else {
                $item->context = [];
            }
        }
        
        return [
            'items' => $items,
            'total_items' => (int) $total_items,
            'total_pages' => ceil($total_items / $args['per_page']),
            'current_page' => (int) $args['page'],
            'per_page' => (int) $args['per_page'],
        ];
    }
    
    /**
     * Delete log entries by ID
     * 
     * @param int|array $ids Log ID or array of IDs to delete
     * @return int|false Number of rows deleted or false on error
     */
    public static function delete_logs($ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        if (empty($ids)) {
            return false;
        }
        
        // If single ID provided, convert to array
        $ids = (array) $ids;
        
        // Prepare placeholders for each ID
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        // Build and execute delete query
        $query = $wpdb->prepare(
            "DELETE FROM $table_name WHERE id IN ($placeholders)",
            $ids
        );
        
        return $wpdb->query($query);
    }
    
    /**
     * Delete all log entries
     * 
     * @return int|false Number of rows deleted or false on error
     */
    public static function delete_all_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->query("TRUNCATE TABLE $table_name");
    }
    
    /**
     * Get available log levels
     * 
     * @return array Log levels
     */
    public static function get_log_levels() {
        return [
            self::LEVEL_INFO => __('Info', 'treyworks-search'),
            self::LEVEL_WARNING => __('Warning', 'treyworks-search'),
            self::LEVEL_ERROR => __('Error', 'treyworks-search'),
            self::LEVEL_DEBUG => __('Debug', 'treyworks-search'),
        ];
    }
    
    /**
     * Get 30-day activity statistics
     * 
     * @return array Array of daily activity data
     */
    public static function get_30_day_activity() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Get data for the last 30 days
        $query = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_requests,
                SUM(CASE WHEN log_level = 'info' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN log_level = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN log_level = 'warning' THEN 1 ELSE 0 END) as blocked
            FROM $table_name
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Create array with all 30 days (fill in missing days with zeros)
        $activity_data = [];
        $end_date = new DateTime();
        $start_date = new DateTime('-29 days');
        
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $end_date->add($interval));
        
        // Initialize all dates with zero values
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $activity_data[$date_str] = [
                'date' => $date_str,
                'total_requests' => 0,
                'success' => 0,
                'errors' => 0,
                'blocked' => 0
            ];
        }
        
        // Fill in actual data
        foreach ($results as $row) {
            if (isset($activity_data[$row['date']])) {
                $activity_data[$row['date']] = [
                    'date' => $row['date'],
                    'total_requests' => (int) $row['total_requests'],
                    'success' => (int) $row['success'],
                    'errors' => (int) $row['errors'],
                    'blocked' => (int) $row['blocked']
                ];
            }
        }
        
        return array_values($activity_data);
    }
}
