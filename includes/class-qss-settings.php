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
        if ('treyworks-search_page_qss-plugin-settings' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'qss-admin',
            PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PLUGIN_VERSION,
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
     * Get Integration Token from settings
     */
    public function get_integration_token() {
        return get_option('qss_plugin_integration_token');
    }

    /**
     * Get LLM Model name from settings
     */
    public function get_llm_model() {
        return get_option('qss_plugin_llm_model');
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
            'search_input_placeholder' => array(
                'label' => __('Search Input Placeholder', 'qss-plugin'),
                'type' => 'text',
                'description' => __('The placeholder text for the search input field.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => __('Refine your search...', 'qss-plugin')
            ),
            'common_questions' => array(
                'label' => __('Common Questions', 'qss-plugin'),
                'type' => 'textarea',
                'rows' => 5,
                'description' => __('Enter common questions to display below the search input (one per line). These will be shown as clickable suggestions and will disappear after a search is submitted.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => ''
            ),
            'replace_wp_search' => array(
                'label' => __('Replace WordPress Search', 'qss-plugin'),
                'type' => 'checkbox',
                'description' => __('Use Treyworks Search as the primary WordPress search interface. When enabled, the default WordPress search will open the AI-powered search modal.', 'qss-plugin'),
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ),
            'enable_logging' => array(
                'label' => __('Enable Logging', 'qss-plugin'),
                'type' => 'checkbox',
                'description' => __('Enable debug logging for troubleshooting purposes.', 'qss-plugin'),
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ),
            'searchable_post_types' => array(
                'label' => __('Searchable Post Types', 'qss-plugin'),
                'type' => 'checkboxes',
                'options' => $this->get_post_types(),
                'description' => __('Select the post types to include in the search query.', 'qss-plugin'),
                'sanitize_callback' => array($this, 'sanitize_post_types'),
                'default' => array('post', 'page')
            ),
            'llm_provider' => array(
                'label' => __('AI Model Provider', 'qss-plugin'),
                'type' => 'select',
                'options' => array(
                    'openai' => __('OpenAI', 'qss-plugin'),
                    'gemini' => __('Google Gemini', 'qss-plugin')
                ),
                'description' => __('Select which AI model provider to use for search and summarization.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai'
            ),
            'llm_model' => array(
                'label' => __('LLM Model Name', 'qss-plugin'),
                'type' => 'text',
                'description' => __('Name of the model to use with the selected provider.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4'
            ),
            'openai_api_key' => array(
                'label' => __('OpenAI API Key', 'qss-plugin'),
                'type' => 'text',
                'description' => '<div class="password-field"><input type="password" class="qss-api-key-field" name="qss_plugin_openai_api_key" value="' . esc_attr(get_option('qss_plugin_openai_api_key')) . '" /><button type="button" class="button qss-reveal-api-key">Reveal</button></div>',
                'sanitize_callback' => 'sanitize_text_field',
                'condition' => array('llm_provider', 'openai')
            ),
            'gemini_api_key' => array(
                'label' => __('Google Gemini API Key', 'qss-plugin'),
                'type' => 'text',
                'description' => '<div class="password-field"><input type="password" class="qss-api-key-field" name="qss_plugin_gemini_api_key" value="' . esc_attr(get_option('qss_plugin_gemini_api_key')) . '" /><button type="button" class="button qss-reveal-api-key">Reveal</button></div>',
                'sanitize_callback' => 'sanitize_text_field',
                'condition' => array('llm_provider', 'gemini')
            ),
            'integration_token' => array(
                'label' => __('REST API Integration Token', 'qss-plugin'),
                'type' => 'text',
                'description' => '<div class="password-field"><input type="password" class="qss-api-key-field" name="qss_plugin_integration_token" value="' . esc_attr(get_option('qss_plugin_integration_token')) . '" /><button type="button" class="button qss-reveal-api-key">Reveal</button><button type="button" class="button button-secondary qss-generate-token">Generate New Token</button></div>',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            ),
            'extract_search_term_prompt' => array(
                'label' => __('Extract Search Term Prompt', 'qss-plugin'),
                'type' => 'textarea',
                'rows' => 8,
                'description' => __('System prompt for extracting search terms from user queries.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => QSS_Default_Prompts::EXTRACT_SEARCH_TERM
            ),
            'create_summary_prompt' => array(
                'label' => __('Create Summary Prompt', 'qss-plugin'),
                'type' => 'textarea',
                'rows' => 15,
                'description' => __('System prompt for creating summaries of search results.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => QSS_Default_Prompts::CREATE_SUMMARY
            ),
            'get_answer_prompt' => array(
                'label' => __('Answer Prompt', 'qss-plugin'),
                'type' => 'textarea',
                'rows' => 15,
                'description' => __('System prompt for generating answers to user questions based on search results.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => QSS_Default_Prompts::GET_ANSWER
            )
        );
    }

    /**
     * Get all registered post types
     */
    private function get_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $options = array();
        foreach ($post_types as $post_type) {
            $options[$post_type->name] = $post_type->label;
        }
        return $options;
    }

    /**
     * Sanitize post types
     */
    public function sanitize_post_types( $values ) {
        $valid_post_types = array_keys( $this->get_post_types() );
        $sanitized_values = array();

        foreach ( $valid_post_types as $post_type ) {
            if ( isset( $values[ $post_type ] ) && $values[ $post_type ] === '1' ) {
                $sanitized_values[] = sanitize_text_field( $post_type );
            }
        }

        return $sanitized_values;
    }

    /**
     * Add plugin options page
     */
    public function add_options_page() {
        add_submenu_page(
            'treyworks-search',
            __('Settings', 'qss-plugin'),
            __('Settings', 'qss-plugin'),
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
            'qss_plugin_api_section',
            __('API Settings', 'qss-plugin'),
            function() { echo '<p>' . __('Configure API keys and select AI provider.', 'qss-plugin') . '</p>'; },
            'qss-plugin-settings'
        );

        // General Settings Section
        add_settings_section(
            'qss_plugin_general_section',
            __('General Settings', 'qss-plugin'),
            function() { echo '<p>' . __('General plugin settings.', 'qss-plugin') . '</p>'; },
            'qss-plugin-settings'
        );

        // Search Settings Section
        add_settings_section(
            'qss_plugin_search_section',
            __('Search Settings', 'qss-plugin'),
            function() { echo '<p>' . __('Configure search behavior.', 'qss-plugin') . '</p>'; },
            'qss-plugin-settings'
        );

        // System Prompts Section
        add_settings_section(
            'qss_plugin_prompts_section',
            __('System Prompts', 'qss-plugin'),
            function() { echo '<p>' . __('Customize system prompts for search and summarization.', 'qss-plugin') . '</p>'; },
            'qss-plugin-settings'
        );

        // Add fields
        foreach ($this->get_settings_fields() as $key => $field) {
            if (in_array($key, ['modal_title', 'replace_wp_search', 'enable_logging', 'search_input_placeholder', 'common_questions'])) {
                $section = 'qss_plugin_general_section';
            } elseif ($key === 'searchable_post_types') {
                $section = 'qss_plugin_search_section';
            } elseif (in_array($key, ['llm_provider', 'integration_token', 'openai_api_key', 'gemini_api_key', 'llm_model'])) {
                $section = 'qss_plugin_api_section';
            } elseif (in_array($key, ['extract_search_term_prompt', 'create_summary_prompt', 'get_answer_prompt'])) {
                $section = 'qss_plugin_prompts_section';
            } else {
                $section = 'qss_plugin_settings_section';
            }

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
     * Post types section callback
     */
    public function post_types_section_callback() {
        echo '<p>' . __('Select the post types to include in the search query.', 'qss-plugin') . '</p>';
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
                $rows = isset($field['rows']) ? intval($field['rows']) : 5;
                printf(
                    '<textarea class="large-text" rows="%d" id="qss_plugin_%s" name="qss_plugin_%s">%s</textarea>',
                    esc_attr($rows),
                    esc_attr($key),
                    esc_attr($key),
                    esc_textarea($value)
                );
                break;

            case 'multiselect':
                printf('<select id="qss_plugin_%s" name="qss_plugin_%s[]" multiple>', esc_attr($key), esc_attr($key));
                foreach ($field['options'] as $option_value => $option_label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        in_array($option_value, $value) ? 'selected' : '',
                        esc_html($option_label)
                    );
                }
                echo '</select>';
                break;

            case 'checkboxes':
                echo '<div class="qss-checkbox-group">';
                foreach ($field['options'] as $option_value => $option_label) {
                    $checked = in_array($option_value, (array)$value) ? 'checked' : '';
                    printf(
                        '<label><input type="checkbox" name="qss_plugin_%s[%s]" value="1" %s> %s</label><br/>',
                        esc_attr($key),
                        esc_attr($option_value),
                        $checked,
                        esc_html($option_label)
                    );
                }
                echo '</div>';
                break;
            
            default:
                if (strpos($field['description'], '<div class="password-field">') !== false) {
                    echo $field['description'];
                } else {
                    printf(
                        '<input type="text" class="regular-text" id="qss_plugin_%s" name="qss_plugin_%s" value="%s" />',
                        esc_attr($key),
                        esc_attr($key),
                        esc_attr($value)
                    );
                }
                break;
        }
        
        if (!empty($field['description']) && strpos($field['description'], '<div class="password-field">') === false) {
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
        include PLUGIN_DIR . 'templates/admin-options-page.php';
    }
}
