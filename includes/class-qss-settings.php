<?php
if (!defined('ABSPATH')) exit;

/**
 * Plugin Settings Class
 */
class QSS_Plugin_Settings {
    private static $instance = null;
    private static $openai_models = array();
    private static $gemini_models = array();

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
        // Initialize model arrays
        self::$openai_models = array(
            'gpt-5.2' => __('GPT-5.2', 'qss-plugin'),
            'gpt-5.1' => __('GPT-5.1', 'qss-plugin'),
            'gpt-5-mini-2025-08-07' => __('GPT-5 Mini', 'qss-plugin'),
            'gpt-4.1' => __('GPT-4.1', 'qss-plugin'),
            'gpt-4.1-mini' => __('GPT-4.1 Mini', 'qss-plugin'),
        );
        
        self::$gemini_models = array(
            'gemini-3-flash-preview' => __('Gemini 3 Flash Preview', 'qss-plugin'),
            'gemini-2.5-pro' => __('Gemini 2.5 Pro', 'qss-plugin'),
            'gemini-2.5-flash' => __('Gemini 2.5 Flash', 'qss-plugin'),
        );
        
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

        // Enqueue Google Fonts
        wp_enqueue_style(
            'treyworks-fonts', 
            'https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&family=Work+Sans:wght@300;400;500;600&display=swap', 
            array(), 
            null
        );

        // Enqueue Admin Theme
        wp_enqueue_style(
            'treyworks-admin-theme', 
            PLUGIN_URL . 'assets/css/admin-theme.css', 
            array(), 
            PLUGIN_VERSION
        );

        wp_enqueue_script(
            'qss-admin',
            PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PLUGIN_VERSION,
            true
        );
        
        // Enqueue API-specific assets for copy button functionality
        wp_enqueue_style('treyworks-search-admin-api', PLUGIN_URL . 'assets/css/admin-api.css', array(), PLUGIN_VERSION);
        wp_enqueue_script('treyworks-search-admin-api', PLUGIN_URL . 'assets/js/admin-api.js', array('jquery'), PLUGIN_VERSION, true);
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
     * Get LLM Model name from settings based on provider
     * @param string $provider Optional provider override
     * @return string The model name for the selected provider
     */
    public function get_llm_model($provider = null, $type = 'extraction') {
        // If no provider is specified, get it from the settings
        if (empty($provider)) {
            $provider = get_option('qss_plugin_llm_provider', 'openai');
        }
        
        // Return the appropriate model based on the provider
        if ($provider === 'gemini') {
            if ($type === 'extraction') {
                return get_option('qss_plugin_gemini_extraction_model', 'gemini-2.5-flash');
            } else {
                return get_option('qss_plugin_gemini_generative_model', 'gemini-2.5-flash');
            }
        } else {
            // Default to OpenAI
            if ($type === 'extraction') {   
                return get_option('qss_plugin_openai_extraction_model', 'gpt-4.1');
            } else {
                return get_option('qss_plugin_openai_generative_model', 'gpt-4.1');
            }
        }
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
            'search_custom_fields' => array(
                'label' => __('Search Custom Fields', 'qss-plugin'),
                'type' => 'checkbox',
                'description' => __('Include post custom field values in search queries. This may increase search time but provides more comprehensive results.', 'qss-plugin'),
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ),
            'max_search_results' => array(
                'label' => __('Max Search Results', 'qss-plugin'),
                'type' => 'number',
                'description' => __('Maximum number of search results to return and process. Higher values provide more context but may increase processing time.', 'qss-plugin'),
                'sanitize_callback' => 'absint',
                'default' => 10
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
            'openai_extraction_model' => array(
                'label' => __('OpenAI Extraction Model', 'qss-plugin'),
                'type' => 'select',
                'options' => self::$openai_models,
                'description' => __('Select the OpenAI model to use.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4.1-nano',
                'condition' => array('llm_provider', 'openai')
            ),
            'openai_generative_model' => array(
                'label' => __('OpenAI Generative Model', 'qss-plugin'),
                'type' => 'select',
                'options' => self::$openai_models,
                'description' => __('Select the OpenAI model to use.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4.1',
                'condition' => array('llm_provider', 'openai')
            ),
            'gemini_extraction_model' => array(
                'label' => __('Gemini Extraction Model', 'qss-plugin'),
                'type' => 'select',
                'options' => self::$gemini_models,
                'description' => __('Select the Gemini model to use.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gemini-2.0-flash-lite',
                'condition' => array('llm_provider', 'gemini')
            ),
            'gemini_generative_model' => array(
                'label' => __('Gemini Generative Model', 'qss-plugin'),
                'type' => 'select',
                'options' => self::$gemini_models,
                'description' => __('Select the Gemini model to use.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gemini-2.5-flash',
                'condition' => array('llm_provider', 'gemini')
            ),
            'openai_api_key' => array(
                'label' => __('OpenAI API Key', 'qss-plugin'),
                'type' => 'text',
                'description' => '<div class="password-field"><input type="password" class="qss-api-key-field" name="qss_plugin_openai_api_key" value="' . esc_attr(get_option('qss_plugin_openai_api_key')) . '" /><button type="button" class="button qss-reveal-api-key"><span class="dashicons dashicons-visibility"></span></button></div>',
                'sanitize_callback' => 'sanitize_text_field',
                'condition' => array('llm_provider', 'openai')
            ),
            'gemini_api_key' => array(
                'label' => __('Google Gemini API Key', 'qss-plugin'),
                'type' => 'text',
                'description' => '<div class="password-field"><input type="password" class="qss-api-key-field" name="qss_plugin_gemini_api_key" value="' . esc_attr(get_option('qss_plugin_gemini_api_key')) . '" /><button type="button" class="button qss-reveal-api-key"><span class="dashicons dashicons-visibility"></span></button></div>',
                'sanitize_callback' => 'sanitize_text_field',
                'condition' => array('llm_provider', 'gemini')
            ),
            'integration_token' => array(
                'label' => __('REST API Integration Token', 'qss-plugin'),
                'type' => 'text',
                'description' => '<div class="password-field"><input type="password" class="qss-api-key-field" name="qss_plugin_integration_token" value="' . esc_attr(get_option('qss_plugin_integration_token')) . '" /><button type="button" class="button qss-reveal-api-key"><span class="dashicons dashicons-visibility"></span></button><button type="button" class="button button-secondary qss-generate-token">Generate New Token</button></div>',
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
            ),
            'enable_trusted_domains' => array(
                'label' => __('Enable Trusted Domains', 'qss-plugin'),
                'type' => 'checkbox',
                'description' => __('Only allow requests from trusted domains. When enabled, requests from domains not in the trusted list will be blocked.', 'qss-plugin'),
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ),
            'trusted_domains' => array(
                'label' => __('Trusted Domains', 'qss-plugin'),
                'type' => 'textarea',
                'rows' => 8,
                'description' => __('Enter one domain per line (e.g., example.com, api.example.com). Do not include http:// or https://. Your WordPress domain is automatically trusted.', 'qss-plugin'),
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => ''
            ),
            'api_endpoints_display' => array(
                'label' => __('API Endpoints', 'qss-plugin'),
                'type' => 'custom',
                'description' => '',
                'sanitize_callback' => null,
                'default' => ''
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
        
        // REST API Security Section
        add_settings_section(
            'qss_plugin_rest_api_section',
            __('REST API Security', 'qss-plugin'),
            array($this, 'rest_api_section_callback'),
            'qss-plugin-settings'
        );

        // Add fields
        foreach ($this->get_settings_fields() as $key => $field) {
            // Determine section for the field
            if (in_array($key, ['modal_title', 'replace_wp_search', 'enable_logging', 'search_input_placeholder', 'common_questions'])) {
                $section = 'qss_plugin_general_section';
            } elseif (in_array($key, ['searchable_post_types', 'search_custom_fields', 'search_acf_groups', 'max_search_results'])) {
                $section = 'qss_plugin_search_section';
            } elseif (in_array($key, ['llm_provider', 'integration_token', 'openai_api_key', 'gemini_api_key', 'openai_extraction_model', 'openai_generative_model', 'gemini_extraction_model', 'gemini_generative_model'])) {
                $section = 'qss_plugin_api_section';
            } elseif (in_array($key, ['extract_search_term_prompt', 'create_summary_prompt', 'get_answer_prompt'])) {
                $section = 'qss_plugin_prompts_section';
            } elseif (in_array($key, ['enable_trusted_domains', 'trusted_domains', 'api_endpoints_display'])) {
                $section = 'qss_plugin_rest_api_section';
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
     * REST API section callback
     */
    public function rest_api_section_callback() {
        $site_url = get_bloginfo('url');
        $parsed_url = parse_url($site_url);
        $current_domain = $parsed_url['host'];
        ?>
        <p><?php _e('Control which domains are allowed to send requests to your REST API endpoints.', 'qss-plugin'); ?></p>
        <div class="notice notice-info inline" style="margin: 1rem 0;">
            <p>
                <strong><?php _e('Note:', 'qss-plugin'); ?></strong> 
                <?php printf(
                    __('Your WordPress domain (<code>%s</code>) is always allowed to access these endpoints, regardless of the settings below.', 'qss-plugin'),
                    esc_html($current_domain)
                ); ?>
            </p>
        </div>
        <?php
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
            
            case 'number':
                printf(
                    '<input type="number" class="regular-text" id="qss_plugin_%s" name="qss_plugin_%s" value="%s" min="1" step="1" />',
                    esc_attr($key),
                    esc_attr($key),
                    esc_attr($value)
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
            
            case 'custom':
                if ($key === 'api_endpoints_display') {
                    $this->render_api_endpoints();
                }
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
     * Render API endpoints display
     */
    private function render_api_endpoints() {
        $rest_base = rest_url('treyworks-search/v1');
        $search_endpoint = $rest_base . '/search';
        $ask_endpoint = $rest_base . '/get_answer';
        ?>
        <div class="treyworks-api-endpoints-display">
            <div class="treyworks-endpoint-section">
                <h4 style="margin-top: 0;"><?php _e('Search Endpoint', 'qss-plugin'); ?></h4>
                <div class="treyworks-endpoint-url">
                    <code><?php echo esc_html($search_endpoint); ?></code>
                    <button type="button" class="button button-small copy-endpoint" data-endpoint="<?php echo esc_attr($search_endpoint); ?>">
                        <?php _e('Copy', 'qss-plugin'); ?>
                    </button>
                </div>
                <p class="description">
                    <?php _e('POST request to search your site and get AI-generated summaries.', 'qss-plugin'); ?>
                </p>
            </div>
            
            <div class="treyworks-endpoint-section">
                <h4><?php _e('Ask Endpoint', 'qss-plugin'); ?></h4>
                <div class="treyworks-endpoint-url">
                    <code><?php echo esc_html($ask_endpoint); ?></code>
                    <button type="button" class="button button-small copy-endpoint" data-endpoint="<?php echo esc_attr($ask_endpoint); ?>">
                        <?php _e('Copy', 'qss-plugin'); ?>
                    </button>
                </div>
                <p class="description">
                    <?php _e('POST request to get direct answers to questions based on your site content.', 'qss-plugin'); ?>
                </p>
            </div>
        </div>
        <?php
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
