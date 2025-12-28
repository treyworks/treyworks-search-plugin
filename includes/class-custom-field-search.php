<?php
/**
 * Custom Field Search functionality
 *
 * @package TreyworksSearch
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QSS_Custom_Field_Search
 * 
 * Handles the integration of custom field values into the search queries
 */
class QSS_Custom_Field_Search {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Add filters to modify search queries
        add_filter('posts_join', array($this, 'custom_field_search_join'), 10, 2);
        add_filter('posts_where', array($this, 'custom_field_search_where'), 10, 2);
        add_filter('posts_distinct', array($this, 'custom_field_search_distinct'), 10, 2);

        // Also modify the content sent to AI for analysis
        add_filter('qss_pre_ai_content', array($this, 'add_custom_fields_to_ai_content'), 10, 2);
    }

    /**
     * Add JOIN clause for custom fields search
     *
     * @param string $join The JOIN clause of the query
     * @param WP_Query $wp_query The WP_Query instance
     * @return string Modified JOIN clause
     */
    public function custom_field_search_join($join, $wp_query) {
        global $wpdb;
        
        // Only modify search queries
        if (!$this->should_modify_query($wp_query)) {
            return $join;
        }
        
        // Check if custom fields search is enabled
        if (!$this->is_custom_field_search_enabled()) {
            return $join;
        }
        
        // Add JOIN to postmeta table
        $join .= " LEFT JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) ";
        
        return $join;
    }

    /**
     * Add WHERE clause for custom fields search
     *
     * @param string $where The WHERE clause of the query
     * @param WP_Query $wp_query The WP_Query instance
     * @return string Modified WHERE clause
     */
    public function custom_field_search_where($where, $wp_query) {
        global $wpdb;
        
        // Only modify search queries
        if (!$this->should_modify_query($wp_query)) {
            return $where;
        }
        
        // Check if custom fields search is enabled
        if (!$this->is_custom_field_search_enabled()) {
            return $where;
        }
        
        // Get search term
        $search_term = $wp_query->get('s');
        if (empty($search_term)) {
            return $where;
        }
        
        // Format search term for LIKE comparison
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Check if we should limit to specific meta keys
        $meta_keys = $this->get_meta_keys_to_search();
        
        if (!empty($meta_keys)) {
            // Search in specified meta keys only
            $meta_keys_clauses = array();
            foreach ($meta_keys as $meta_key) {
                $meta_keys_clauses[] = $wpdb->prepare(
                    "({$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value LIKE %s)",
                    $meta_key,
                    $like
                );
            }
            
            // Find the position of the closing parenthesis for the title/content search
            $pos = strpos($where, ')');
            if ($pos !== false) {
                // Insert our custom fields condition
                $where = substr($where, 0, $pos) . 
                         " OR (" . implode(' OR ', $meta_keys_clauses) . ")" .
                         substr($where, $pos);
            }
        } else {
            // Search in all meta values (excluding hidden fields)
            $pos = strpos($where, ')');
            if ($pos !== false) {
                $where = substr($where, 0, $pos) . 
                         $wpdb->prepare(
                             " OR ({$wpdb->postmeta}.meta_key NOT LIKE %s AND {$wpdb->postmeta}.meta_value LIKE %s)",
                             '\_%',
                             $like
                         ) .
                         substr($where, $pos);
            }
        }
        
        return $where;
    }

    /**
     * Add DISTINCT to avoid duplicate results
     *
     * @param string $distinct The DISTINCT clause of the query
     * @param WP_Query $wp_query The WP_Query instance
     * @return string Modified DISTINCT clause
     */
    public function custom_field_search_distinct($distinct, $wp_query) {
        // Only modify search queries
        if (!$this->should_modify_query($wp_query)) {
            return $distinct;
        }
        
        // Check if custom fields search is enabled
        if (!$this->is_custom_field_search_enabled()) {
            return $distinct;
        }
        
        return "DISTINCT";
    }

    /**
     * Add custom fields data to content for AI analysis
     *
     * @param string $content The post content
     * @param int $post_id The post ID
     * @return string Modified content including custom fields
     */
    public function add_custom_fields_to_ai_content($content, $post_id) {
        // Check if custom fields search is enabled
        if (!$this->is_custom_field_search_enabled()) {
            return $content;
        }
        
        $meta_content = '';
        
        // Use ACF specific logic if available and configured
        if (function_exists('acf_get_field_groups')) {
            $selected_groups = get_option('qss_plugin_search_acf_groups', array());
            
            if (!empty($selected_groups)) {
                $meta_content = $this->get_acf_content($post_id, $selected_groups);
            }
        }
        
        // Fallback to raw meta if no ACF content found or ACF not active
        if (empty($meta_content)) {
            $meta_content = $this->get_raw_meta_content($post_id);
        }
        
        // Append meta content
        if (!empty($meta_content)) {
            $content .= "\n\nCustom Field Content:\n" . $meta_content;
        }
        
        return $content;
    }

    /**
     * Get content from ACF fields
     */
    private function get_acf_content($post_id, $selected_groups) {
        $content = '';
        
        // Get all field groups for this post
        $post_groups = acf_get_field_groups(array('post_id' => $post_id));
        
        foreach ($post_groups as $group) {
            // Skip if this group is not in our selected list
            if (!in_array($group['key'], $selected_groups)) {
                continue;
            }
            
            // Get fields for this group
            $fields = acf_get_fields($group['key']);
            
            if ($fields) {
                foreach ($fields as $field) {
                    $value = get_field($field['name'], $post_id);
                    
                    if ($value) {
                        // Handle different field types
                        $formatted_value = $this->format_acf_value($value, $field);
                        
                        if ($formatted_value) {
                            $content .= "\n" . $field['label'] . ": " . $formatted_value;
                        }
                    }
                }
            }
        }
        
        return $content;
    }

    /**
     * Format ACF value based on type
     */
    private function format_acf_value($value, $field) {
        if (is_string($value)) {
            return strip_tags($value);
        }
        
        if (is_array($value)) {
            // Handle array values (like checkboxes, selects, or relationships)
            // Flatten simplified arrays
            $flat_values = array();
            array_walk_recursive($value, function($a) use (&$flat_values) { 
                if (is_string($a) || is_numeric($a)) {
                    $flat_values[] = $a; 
                }
            });
            return implode(', ', $flat_values);
        }
        
        if (is_object($value)) {
            // Try to convert object to string if possible, or skip
            return method_exists($value, '__toString') ? (string) $value : '';
        }
        
        return (string) $value;
    }

    /**
     * Get content from raw post meta (fallback)
     */
    private function get_raw_meta_content($post_id) {
        $post_meta = get_post_meta($post_id);
        if (empty($post_meta)) {
            return '';
        }
        
        $meta_keys = $this->get_meta_keys_to_search();
        $meta_content = '';
        
        foreach ($post_meta as $key => $values) {
            // Skip if we're only searching specific meta keys and this key isn't in the list
            if (!empty($meta_keys) && !in_array($key, $meta_keys)) {
                continue;
            }
            
            // Skip internal WordPress meta keys (starting with _)
            if (strpos($key, '_') === 0) {
                continue;
            }
            
            foreach ($values as $value) {
                // Only include string values, not serialized data
                if (is_string($value) && !is_serialized($value)) {
                    $meta_content .= "\n" . $key . ": " . $value;
                }
            }
        }
        
        return $meta_content;
    }

    /**
     * Check if we should modify this query
     *
     * @param WP_Query $wp_query The WP_Query instance
     * @return bool Whether to modify this query
     */
    private function should_modify_query($wp_query) {
        // Only modify search queries
        if (!$wp_query->is_search()) {
            return false;
        }
        
        // Don't modify admin queries
        if (is_admin()) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if custom fields search is enabled
     *
     * @return bool Whether custom fields search is enabled
     */
    private function is_custom_field_search_enabled() {
        return get_option('qss_plugin_search_custom_fields', false);
    }

    /**
     * Get meta keys to search
     *
     * @return array Meta keys to search, empty array for all
     */
    private function get_meta_keys_to_search() {
        $meta_keys = array();
        
        // Get selected ACF groups
        if (function_exists('acf_get_fields')) {
            $selected_groups = get_option('qss_plugin_search_acf_groups', array());
            
            if (!empty($selected_groups)) {
                foreach ($selected_groups as $group_key) {
                    $fields = acf_get_fields($group_key);
                    if ($fields) {
                        foreach ($fields as $field) {
                            $meta_keys[] = $field['name'];
                        }
                    }
                }
            }
        }
        
        return $meta_keys;
    }
}
