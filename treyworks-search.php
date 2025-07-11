<?php
/**
 * Plugin Name: Treyworks Search for WordPress
 * Plugin URI: https://treyworks.com/ai-search-plugin/
 * Description: A WordPress plugin for quick search and summarization using AI
 * Version: 1.2.0
 * Author: Treyworks LLC
 * Author URI: https://treyworks.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: treyworks-search
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load Composer dependencies
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Define plugin activation and deactivation hooks
register_activation_hook(__FILE__, 'treyworks_search_activate');
register_uninstall_hook(__FILE__, 'treyworks_search_uninstall');

/**
 * Plugin activation function
 * Creates the logs database table
 */
function treyworks_search_activate() {
    // Include DB Logger class
    require_once plugin_dir_path(__FILE__) . 'includes/class-db-logger.php';
    
    // Create logs table
    DB_Logger::create_table();
}

/**
 * Plugin uninstall function
 * Drops the logs database table
 */
function treyworks_search_uninstall() {
    // Include DB Logger class
    require_once plugin_dir_path(__FILE__) . 'includes/class-db-logger.php';
    
    // Drop logs table if user confirmed
    if (get_option('treyworks_search_confirm_uninstall', false)) {
        DB_Logger::drop_table();
    }
}

/**
 * Global logging function
 * 
 * @param string|array $message The message to log
 * @param string $level The log level (info, warning, error, debug)
 * @param array $context Additional context data
 * @return bool Whether the log was successfully added
 */
function treyworks_log($message, $level = 'info', $context = []) {
    // Include DB Logger class if not already included
    if (!class_exists('DB_Logger')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-db-logger.php';
    }
    
    return DB_Logger::log($message, $level, $context);
}

if (!class_exists('QuickSearchSummarizer')) {
    class QuickSearchSummarizer {
        private static $instance = null;
        private $settings;
        private $core;

        private function __construct() {
            $this->define_constants();
            $this->init_hooks();
        }

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function define_constants() {
            define('PLUGIN_VERSION', '1.2.0');
            define('PLUGIN_DIR', plugin_dir_path(__FILE__));
            define('PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        private function init_hooks() {
            add_action('plugins_loaded', array($this, 'init_plugin'));
            add_action('rest_api_init', [$this, 'register_rest_api_routes']);
            add_action('admin_init', [$this, 'register_settings']);
        }

        /**
         * Register plugin settings
         */
        public function register_settings() {
            register_setting('treyworks_search_settings', 'treyworks_search_confirm_uninstall', [
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]);
        }

        public function init_plugin() {
            $this->load_dependencies();
            $this->init_classes();
        }

        private function load_dependencies() {
            // Load logger classes
            require_once PLUGIN_DIR . '/includes/class-logger.php'; // Keep for backward compatibility
            require_once PLUGIN_DIR . '/includes/class-db-logger.php';
            require_once PLUGIN_DIR . '/includes/class-admin-logs.php';
            require_once PLUGIN_DIR . '/includes/class-settings.php';
            require_once PLUGIN_DIR . '/includes/class-qss-prompts.php';
            require_once PLUGIN_DIR . '/includes/class-qss-settings.php';
            require_once PLUGIN_DIR . '/includes/class-custom-field-search.php';
            require_once PLUGIN_DIR . 'includes/class-qss-core.php';
            require_once PLUGIN_DIR . '/includes/class-openai-client.php';
            require_once PLUGIN_DIR . '/includes/class-gemini-client.php';
        }

        private function init_classes() {
            // Initialize Admin Logs first (creates main admin menu)
            Admin_Logs::init();
            
            // Initialize Settings
            $this->settings = QSS_Plugin_Settings::get_instance();
            
            // Initialize the core functionality
            if (class_exists('QSS_Core')) {
                $this->core = new QSS_Core();
            }

            // Initialize the logger
            
            if (class_exists('DB_Logger')) {
                DB_Logger::initialize(); // Initialize database logger
            }
            
            // Initialize custom field search functionality
            if (class_exists('QSS_Custom_Field_Search')) {
                new QSS_Custom_Field_Search();
            }
        }

        /**
         * Site Search Function
         */
        private function search_site($search_term, $post_ids = null) {
            // Search query arguments
            $args = array(
                's' => $search_term,
                'post_type' => get_option('qss_plugin_searchable_post_types', array('post', 'page')),
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );

            // If specific post IDs are provided, add them to the query
            if (!empty($post_ids) && is_array($post_ids)) {
                // Ensure IDs are integers
                $post_ids = array_map('intval', $post_ids);
                $args['post__in'] = $post_ids;
                // When post__in is used, 's' is ignored, so we remove it to avoid confusion
                // We will filter by content later if needed, though typically specifying IDs means we want only those.
                unset($args['s']); 
            }

            // Perform the search
            $search_query = new WP_Query($args);
            $search_results = array();

            // Get search results
            if ($search_query->have_posts()) {

                // Loop through search results
                $result_count = 0;
                while ($search_query->have_posts()) {
                    $search_query->the_post();
                    if ($result_count < 5) {

                        // Get post content
                        $content = wp_strip_all_tags(get_the_content());
                        
                        // Allow plugins to modify the content (e.g., add custom fields)
                        $content = apply_filters('qss_pre_ai_content', $content, get_the_ID());
                        
                        $search_results[] = array(
                            'title' => get_the_title(),
                            'content' => $content,
                            'permalink' => get_permalink()
                        );
                    } else {

                        $search_results[] = array(
                            'title' => get_the_title(),
                            'permalink' => get_permalink()
                        );
                    }
                    // Increment result count
                    $result_count++;
                }
            }

            wp_reset_postdata();
            return $search_results;
        }

        private function get_llm_client($llm_provider = 'openai') {
            
            // Check LLM provider
            switch ($llm_provider) {
                case 'gemini':
                    // Gemini
                    $api_key = $this->settings->get_gemini_key();
                    if (empty($api_key)) {
                        throw new Exception('Google Gemini API key is not set.');
                    }
                    $client = QSS_Gemini_Client::get_instance();
                    break;

                case 'openai':
                default:
                    // OpenAI
                    $api_key = $this->settings->get_openai_key();
                    if (empty($api_key)) {
                        throw new Exception('OpenAI API key is not set.');
                    }
                    $client = QSS_OpenAI_Client::get_instance();
                    break;
            }
            
            return $client->initialize($api_key);
        }

        /**
         * Get prompt result using chosen LLM
         */
        private function get_prompt_result($system_prompt, $query, $llm_provider) {
            $client = $this->get_llm_client($llm_provider);

            try {
                if ($llm_provider === 'gemini') {
                    $response = $client->geminiFlash()->generateContent(
                        $system_prompt . '\n User query: ' . $query
                    );
                    return $response->text();
                } else {
                    $response = $client->chat()->create([
                        'model' => $this->settings->get_llm_model(),
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $system_prompt
                            ],
                            [
                                'role' => 'user',
                                'content' => $query
                            ]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 100
                    ]);
                    return $response->choices[0]->message->content;
                }
            } catch (Exception $e) {
                treyworks_log('LLM API Error: ' . $e->getMessage());
                return $query;
            }
        }

        /**
         * Process search results using LLM
         */
        private function process_search_results($system_prompt, $results, $query, $llm_provider) {

            $client = $this->get_llm_client($llm_provider);
            
            // Format results for the API
            $formatted_results = array_map(function($result) {
                return array(
                    'title' => $result['title'],
                    'content' => wp_strip_all_tags($result['content']),
                    'url' => $result['permalink']
                );
            }, array_slice($results, 0, 5));

            $input_content = json_encode([
                'query' => $query,
                'results' => $formatted_results
            ]);

            try {
                if ($llm_provider === 'gemini') {
                    $response = $client->geminiFlash()->generateContent(
                        $system_prompt . '\nSearch results:\n' . $input_content
                    );
                    return $response->text();
                } else {
                    $response = $client->chat()->create([
                        'model' => $this->settings->get_llm_model(),
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $system_prompt
                            ],
                            [
                                'role' => 'user',
                                'content' => $input_content
                            ]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 1000
                    ]);
                    return $response->choices[0]->message->content;
                }
            } catch (Exception $e) {
                treyworks_log('LLM API Error: ' . $e->getMessage());
                return '';
            }
        }

        public function register_rest_api_routes() {
            // Register search route
            register_rest_route('treyworks-search/v1', '/search', [
                'methods' => ['POST'],
                'callback' => [ $this, 'get_search_results' ],
                'permission_callback' => function() {
                    return true; // Allow public access to the endpoint
                }
            ]);

            // Register ask route
            register_rest_route('treyworks-search/v1', '/get_answer', [
                'methods' => ['POST'],
                'callback' => [ $this, 'get_answer_callback' ],
                'permission_callback' => function() {
                    return true; // Allow public access to the endpoint
                }
            ]);
        }

         // Function to handle search requests
        public function get_search_results($request) {
            
            // Verify nonce
            if (!$this->verify_nonce($request)) {
                return new WP_Error('forbidden', __('Invalid nonce'), ['status' => 403]);
            }

            // Verify request server
            if (!$this->verify_request_server()) {
                return new WP_Error('no_crossorigin', __('Cross Origin access forbidden'), ['status' => 403]);
            }

            // Get settings
            $llm_provider = get_option('qss_plugin_llm_provider', 'openai');
            
            // Get custom prompt or use default
            $summary_prompt = get_option('qss_plugin_create_summary_prompt', QSS_Default_Prompts::CREATE_SUMMARY);

            try {
                // Get request parameters
                $params = $request->get_json_params();
                $search_query = $params['search_query'] ?? null;

                if (empty($search_query)) {
                    return new WP_Error('invalid_request', 'Search query is required', ['status' => 400]);
                }

                // Extract search term
                $extract_search_term_prompt = get_option('qss_plugin_extract_search_term_prompt', QSS_Default_Prompts::EXTRACT_SEARCH_TERM);
                
                // Get prompt result
                $extracted_search_term = $this->get_prompt_result($extract_search_term_prompt, $search_query, $llm_provider);

                // Perform WordPress search
                $post_ids = null;
                if (isset($params['post_ids'])) {
                    $post_ids = array_map('intval', explode(',', sanitize_text_field($params['post_ids'])));
                    $post_ids = array_filter($post_ids, function($id) { return $id > 0; }); // Ensure positive integers
                    if (empty($post_ids)) {
                        $post_ids = null; // Reset if parsing resulted in empty array
                    }
                }

                $search_results = $this->search_site($extracted_search_term, $post_ids);

                // Create summary of search results
                $summary = $this->process_search_results($summary_prompt, $search_results, $search_query, $llm_provider);

                // Log search results with referer URL
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Search complete: ' . $search_query), 'info', [
                    'extracted_search_term' => $extracted_search_term, 
                    'summary' => $summary,
                    'referer' => $referer
                ]);
                
                // Return search results and summary
                return new WP_REST_Response([
                    'query' => $search_query,
                    'results' => $search_results,
                    'summary' => $summary
                ], 200);

            } catch (Exception $e) {
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Search error: ' . $e->getMessage()), 'error', [
                    'exception' => get_class($e),
                    'referer' => $referer
                ]);
                return new WP_Error('api_error', 'Error processing search request: ' . $e->getMessage(), ['status' => 500]);
            }
        }

        /** Get answer results */
        public function get_answer_callback($request) {
            
            // Verify request server
            if (!$this->verify_request_server()) {
                // Verify integration token if request is not from server
                
                // Integration token passed in request header
                // Example: { "treyworks-search-token": "your_integration_token" }
                $integration_token = get_option('qss_plugin_integration_token');
            
                // Only verify integration token if it is not empty
                if (!empty($integration_token) && $integration_token !== null) {
                    
                    // Get request token
                    $request_token = $request->get_header('treyworks-search-token');

                    // Validate request token
                    if (empty($request_token)) {
                        return new WP_Error('invalid_request', 'Integration token is required', ['status' => 400]);
                    }

                    // Verify integration token
                    if ($request_token !== $integration_token) {
                        return new WP_Error('forbidden', __('Invalid integration token'), ['status' => 403]);
                    }
                }
            }

            // Get settings
            $llm_provider = get_option('qss_plugin_llm_provider', 'openai');

            try {
                // Get request parameters
                $params = $request->get_json_params();

                // Get search query - check multiple sources
                $search_query = null;
                
                // First check direct search_query parameter in JSON body
                if (isset($params['search_query'])) {
                    $search_query = $params['search_query'];
                }
                // Then check args parameter that contains search_query
                elseif (isset($params['args'])) {
                    $args = is_string($params['args']) ? json_decode($params['args'], true) : $params['args'];
                    if (isset($args['search_query'])) {
                        $search_query = $args['search_query'];
                    }
                }

                // Validate search query
                if (empty($search_query)) {
                    return new WP_Error('invalid_request', 'Search query is required', ['status' => 400]);
                }

                // Extract search term
                // Get custom extract search term prompt or use default
                $extract_search_term_prompt = get_option('qss_plugin_extract_search_term_prompt', QSS_Default_Prompts::EXTRACT_SEARCH_TERM);
                
                // Get custom get answer prompt or use default
                $answer_prompt = get_option('qss_plugin_get_answer_prompt', QSS_Default_Prompts::GET_ANSWER);

                // Get Extracted Search Term prompt result
                $extracted_search_term = $this->get_prompt_result($extract_search_term_prompt, $search_query, $llm_provider);

                // Get post IDs from request
                $post_ids = null;
                if (isset($params['post_ids'])) {
                    $post_ids = array_map('intval', explode(',', sanitize_text_field($params['post_ids'])));
                    $post_ids = array_filter($post_ids, function($id) { return $id > 0; }); // Ensure positive integers
                    if (empty($post_ids)) {
                        $post_ids = null; // Reset if parsing resulted in empty array
                    }
                }

                // Perform WordPress search
                $search_results = $this->search_site($extracted_search_term, $post_ids);

                // Get answer
                $answer = $this->process_search_results($answer_prompt, $search_results, $search_query, $llm_provider);
                
                // Log search results with referer URL
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Answer complete: ' . $search_query), 'info', [
                    'answer' => $answer, 
                    'extracted_search_term' => $extracted_search_term,
                    'referer' => $referer
                ]);

                // Return answer
                return new WP_REST_Response($answer, 200);

            } catch (Exception $e) {
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Error getting answer: ' . $e->getMessage()), 'error', [
                    'exception' => get_class($e),
                    'referer' => $referer
                ]);
                return new WP_Error('api_error', 'Error processing search request: ' . $e->getMessage(), ['status' => 500]);
            }
        
        }

        /**
         * Verify nonce
         * Returns a boolean
         */
        public function verify_nonce($request) {
            // Verify nonce
            $nonce = $request->get_header('X-WP-Nonce');
            if ( !wp_verify_nonce($nonce, 'wp_rest') ) {
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Invalid nonce ' . $nonce), 'error', ['nonce' => $nonce, 'referer' => $referer]);
                return false;
            }

            return true;
        }

        /**
         * Verify request server
         * Returns a boolean
         */
        public function verify_request_server() {
            // check request server
            $request_domain = $_SERVER['HTTP_HOST'];
            $site_url = get_bloginfo('url');
            $parsed_url = parse_url($site_url);
            $server_domain = $parsed_url['host'];

            if ($request_domain != $server_domain) {
                $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'unknown';
                treyworks_log(__('Cross Origin access forbidden'), 'error', [
                    'request_domain' => $request_domain, 
                    'server_domain' => $server_domain,
                    'referer' => $referer
                ]);
                return false;
            }

            return true;
        }
    }

}


// Initialize the plugin
function treyworks_search() {
    return QuickSearchSummarizer::get_instance();
}

// Start the plugin
treyworks_search();
