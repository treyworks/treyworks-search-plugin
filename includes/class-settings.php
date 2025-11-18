<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Class for Treyworks Search
 */
class Treyworks_Search_Settings {
    /**
     * Initialize the settings
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    /**
     * Add settings page to the admin menu
     */
    public static function add_settings_page() {
        add_submenu_page(
            'treyworks-search',
            __('Settings', 'treyworks-search'),
            __('Settings', 'treyworks-search'),
            'manage_options',
            'treyworks-search-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting(
            'treyworks_search_settings',
            'qss_plugin_enable_logging',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            )
        );
        
        register_setting(
            'treyworks_search_settings',
            'treyworks_search_confirm_uninstall',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            )
        );
        
        add_settings_section(
            'treyworks_search_logging_section',
            __('Logging Settings', 'treyworks-search'),
            array(__CLASS__, 'render_logging_section'),
            'treyworks_search_settings'
        );
        
        add_settings_field(
            'qss_plugin_enable_logging',
            __('Enable Logging', 'treyworks-search'),
            array(__CLASS__, 'render_enable_logging_field'),
            'treyworks_search_settings',
            'treyworks_search_logging_section'
        );
        
        add_settings_field(
            'treyworks_search_confirm_uninstall',
            __('Confirm Database Cleanup on Uninstall', 'treyworks-search'),
            array(__CLASS__, 'render_confirm_uninstall_field'),
            'treyworks_search_settings',
            'treyworks_search_logging_section'
        );
    }
    
    /**
     * Render the logging settings section
     */
    public static function render_logging_section() {
        echo '<p>' . __('Configure logging settings for Treyworks Search plugin.', 'treyworks-search') . '</p>';
    }
    
    /**
     * Render enable logging field
     */
    public static function render_enable_logging_field() {
        $value = get_option('qss_plugin_enable_logging', false);
        ?>
        <label for="qss_plugin_enable_logging">
            <input type="checkbox" id="qss_plugin_enable_logging" name="qss_plugin_enable_logging" value="1" <?php checked($value, true); ?> />
            <?php _e('Enable logging for the Treyworks Search plugin', 'treyworks-search'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, the plugin will log important operations to the database.', 'treyworks-search'); ?>
        </p>
        <?php
    }
    
    /**
     * Render confirm uninstall field
     */
    public static function render_confirm_uninstall_field() {
        $value = get_option('treyworks_search_confirm_uninstall', false);
        ?>
        <label for="treyworks_search_confirm_uninstall">
            <input type="checkbox" id="treyworks_search_confirm_uninstall" name="treyworks_search_confirm_uninstall" value="1" <?php checked($value, true); ?> />
            <?php _e('Delete log database table when uninstalling the plugin', 'treyworks-search'); ?>
        </label>
        <p class="description">
            <?php _e('When checked, the plugin will remove all logs and the log database table when uninstalled. If unchecked, log data will be preserved.', 'treyworks-search'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the settings page
     */
    public static function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('treyworks_search_settings');
                do_settings_sections('treyworks_search_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
