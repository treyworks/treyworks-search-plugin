<?php
if (!defined('ABSPATH')) exit;

/**
 * Plugin Settings Class
 */
class QSS_Plugin_Settings {
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_qss-plugin-settings' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'qss-admin',
            QSS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            QSS_VERSION,
            true
        );
    }

    /**
     * Get OpenAI API key from settings
     */
    public function get_openai_key() {
        return get_option('qss_plugin_openai_api_key');
    }

    /**
     * Get Gemini API key from settings
     */
    public function get_gemini_key() {
        return get_option('qss_plugin_gemini_api_key');
    }

    /**
     * Get settings fields
     */
    private function get_settings_fields() {
        return array(
            'modal_title' => array(
                'label' => __('Modal Title', 'qss-plugin'),
                'type' => 'text',
                'description' => __('The title displayed at the top of the search modal. Leave empty to use default: "Search the site"', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => __('Search the site', 'qss-plugin')
            ),
            'replace_wp_search' => array(
                'label' => __('Replace WordPress Search', 'qss-plugin'),
                'type' => 'checkbox',
                'description' => __('Use Quick Search Summarizer as the primary WordPress search interface. When enabled, the default WordPress search will open the AI-powered search modal.', 'qss-plugin'),
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ),
            'llm_provider' => array(
                'label' => __('AI Model Provider', 'qss-plugin'),
                'type' => 'select',
                'options' => array(
                    'openai' => __('OpenAI GPT-4', 'qss-plugin'),
                    'gemini' => __('Google Gemini', 'qss-plugin')
                ),
                'description' => __('Select which AI model provider to use for search and summarization.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai'
            ),
            'openai_api_key' => array(
                'label' => __('OpenAI API Key', 'qss-plugin'),
                'type' => 'text',
                'description' => __('Enter your OpenAI API key to enable AI-powered search functionality.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'condition' => array('llm_provider', 'openai')
            ),
            'gemini_api_key' => array(
                'label' => __('Google Gemini API Key', 'qss-plugin'),
                'type' => 'text',
                'description' => __('Enter your Google Gemini API key.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'condition' => array('llm_provider', 'gemini')
            ),
            'enable_logging' => array(
                'label' => __('Enable Logging', 'qss-plugin'),
                'type' => 'checkbox',
                'description' => __('Enable debug logging for troubleshooting purposes.', 'qss-plugin'),
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ),
            'extract_search_term_prompt' => array(
                'label' => __('Extract Search Term Prompt', 'qss-plugin'),
                'type' => 'textarea',
                'description' => __('System prompt for extracting search terms from user queries.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => QSS_Default_Prompts::EXTRACT_SEARCH_TERM
            ),
            'create_summary_prompt' => array(
                'label' => __('Create Summary Prompt', 'qss-plugin'),
                'type' => 'textarea',
                'description' => __('System prompt for creating summaries of search results.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => QSS_Default_Prompts::CREATE_SUMMARY
            )
        );
    }

    /**
     * Add plugin options page
     */
    public function add_options_page() {
        add_options_page(
            __('Quick Search Summarizer Settings', 'qss-plugin'),
            __('Quick Search Summarizer Settings', 'qss-plugin'),
            'manage_options',
            'qss-plugin-settings',
            array($this, 'render_options_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        foreach ($this->get_settings_fields() as $key => $field) {
            register_setting(
                'qss_plugin_settings',
                'qss_plugin_' . $key,
                array(
                    'sanitize_callback' => $field['sanitize_callback']
                )
            );
        }

        // API Settings Section
        add_settings_section(
            'qss_plugin_settings_section',
            __('API Settings', 'qss-plugin'),
            array($this, 'settings_section_callback'),
            'qss-plugin-settings'
        );

        // System Prompts Section
        add_settings_section(
            'qss_plugin_prompts_section',
            __('System Prompts', 'qss-plugin'),
            array($this, 'prompts_section_callback'),
            'qss-plugin-settings'
        );

        // Add fields
        foreach ($this->get_settings_fields() as $key => $field) {
            $section = strpos($key, 'prompt') !== false ? 'qss_plugin_prompts_section' : 'qss_plugin_settings_section';
            
            add_settings_field(
                'qss_plugin_' . $key,
                $field['label'],
                array($this, 'render_field'),
                'qss-plugin-settings',
                $section,
                array(
                    'key' => $key,
                    'field' => $field
                )
            );
        }
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure your API settings below.', 'qss-plugin') . '</p>';
    }

    /**
     * Prompts section callback
     */
    public function prompts_section_callback() {
        echo '<p>' . __('Configure the system prompts used for AI operations. Leave blank to use defaults.', 'qss-plugin') . '</p>';
    }

    /**
     * Render field
     */
    public function render_field($args) {
        $key = $args['key'];
        $field = $args['field'];
        $value = get_option('qss_plugin_' . $key, $field['default'] ?? '');
        
        // Check if field should be shown based on condition
        if (!empty($field['condition'])) {
            list($dependent_field, $dependent_value) = $field['condition'];
            $current_dependent_value = get_option('qss_plugin_' . $dependent_field);
            $display_style = $current_dependent_value === $dependent_value ? '' : 'display: none;';
            printf('<div class="qss-conditional-field" data-depends-on="%s" data-depends-value="%s" style="%s">', 
                esc_attr($dependent_field), 
                esc_attr($dependent_value),
                esc_attr($display_style)
            );
        }
        
        switch ($field['type']) {
            case 'select':
                printf('<select id="qss_plugin_%s" name="qss_plugin_%s">', esc_attr($key), esc_attr($key));
                foreach ($field['options'] as $option_value => $option_label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        selected($option_value, $value, false),
                        esc_html($option_label)
                    );
                }
                echo '</select>';
                break;

            case 'checkbox':
                printf(
                    '<input type="checkbox" id="qss_plugin_%s" name="qss_plugin_%s" value="1" %s />',
                    esc_attr($key),
                    esc_attr($key),
                    checked(1, $value, false)
                );
                break;
                
            case 'textarea':
                printf(
                    '<textarea class="large-text code" rows="8" id="qss_plugin_%s" name="qss_plugin_%s">%s</textarea>',
                    esc_attr($key),
                    esc_attr($key),
                    esc_textarea($value)
                );
                break;
            
            default:
                printf(
                    '<input type="text" class="regular-text" id="qss_plugin_%s" name="qss_plugin_%s" value="%s" />',
                    esc_attr($key),
                    esc_attr($key),
                    esc_attr($value)
                );
                break;
        }
        
        if (!empty($field['description'])) {
            printf('<p class="description">%s</p>', esc_html($field['description']));
        }

        if (!empty($field['condition'])) {
            echo '</div>';
        }
    }

    /**
     * Render options page
     */
    public function render_options_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error('qss_plugin_messages', 'qss_plugin_message', __('Settings Saved', 'qss-plugin'), 'updated');
        }

        // Handle clear log action
        if (isset($_POST['clear_log']) && check_admin_referer('qss_clear_log')) {
            if (Plugin_Logger::clear_log()) {
                add_settings_error('qss_plugin_messages', 'qss_plugin_message', __('Log file cleared successfully', 'qss-plugin'), 'updated');
            } else {
                add_settings_error('qss_plugin_messages', 'qss_plugin_message', __('Failed to clear log file', 'qss-plugin'), 'error');
            }
        }

        settings_errors('qss_plugin_messages');
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
        <?php
    }
}
