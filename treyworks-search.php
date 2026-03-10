<?php
/**
 * Plugin Name: Treyworks Search for WordPress
 * Plugin URI: https://treyworks.com/ai-search-plugin/
 * Description: A WordPress plugin for quick search and summarization using AI
 * Version: 1.4.0
 * Author: Treyworks LLC
 * Author URI: https://treyworks.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: treyworks-search
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define log level constants
define('TREYWORKS_LOG_INFO', 'info');
define('TREYWORKS_LOG_WARNING', 'warning');
define('TREYWORKS_LOG_ERROR', 'error');
define('TREYWORKS_LOG_DEBUG', 'debug');

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
 * @param string $level The log level (use TREYWORKS_LOG_INFO, TREYWORKS_LOG_WARNING, TREYWORKS_LOG_ERROR, TREYWORKS_LOG_DEBUG)
 * @param array $context Additional context data
 * @return bool Whether the log was successfully added
 */
function treyworks_log($message, $level = TREYWORKS_LOG_INFO, $context = []) {
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
            define('PLUGIN_VERSION', '1.4.0');
            define('PLUGIN_DIR', plugin_dir_path(__FILE__));
            define('PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        private function init_hooks() {
            add_action('plugins_loaded', array($this, 'init_plugin'));
            add_action('rest_api_init', [$this, 'register_rest_api_routes']);
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
            require_once PLUGIN_DIR . '/includes/class-qss-prompts.php';
            require_once PLUGIN_DIR . '/includes/class-qss-settings.php';
            require_once PLUGIN_DIR . '/includes/class-custom-field-search.php';
            require_once PLUGIN_DIR . 'includes/class-qss-core.php';
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
        }

        /**
         * Site Search Function
         */
        private function search_site($search_term, $post_ids = null) {
            // Get max search results setting
            $max_results = get_option('qss_plugin_max_search_results', 3);
            
            // Split search term by comma to handle multiple extracted terms
            $search_terms = array_map('trim', explode(',', $search_term));
            $all_results = array();
            $seen_post_ids = array();
            
            // Loop through each extracted term
            foreach ($search_terms as $term) {
                if (empty($term)) {
                    continue;
                }
                
                // Search query arguments
                $args = array(
                    's' => $term,
                    'post_type' => get_option('qss_plugin_searchable_post_types', array('post', 'page')),
                    'post_status' => 'publish',
                    'posts_per_page' => $max_results,
                );

                // If specific post IDs are provided, add them to the query
                if (!empty($post_ids) && is_array($post_ids)) {
                    // Ensure IDs are integers
                    $post_ids = array_map('intval', $post_ids);
                    $args['post__in'] = $post_ids;
                    unset($args['s']); 
                }

                // Perform the search
                $search_query = new WP_Query($args);

                // Get search results
                if ($search_query->have_posts()) {
                    while ($search_query->have_posts()) {
                        $search_query->the_post();
                        $post_id = get_the_ID();
                        
                        // Skip if we've already added this post
                        if (in_array($post_id, $seen_post_ids)) {
                            continue;
                        }
                        
                        // Get post content
                        $content = wp_strip_all_tags(get_the_content());
                        
                        // Allow plugins to modify the content (e.g., add custom fields)
                        $content = apply_filters('qss_pre_ai_content', $content, $post_id);
                        
                        $all_results[] = array(
                            'title' => get_the_title(),
                            'content' => $content,
                            'permalink' => get_permalink(),
                            'post_id' => $post_id,
                            'relevance_score' => $search_query->current_post + 1
                        );
                        
                        $seen_post_ids[] = $post_id;
                    }
                }

                wp_reset_postdata();
            }
            
            // Rank results by relevance score (lower is better)
            usort($all_results, function($a, $b) {
                return $a['relevance_score'] - $b['relevance_score'];
            });
            
            // Limit to max results and remove scoring metadata
            $final_results = array_slice($all_results, 0, $max_results);
            foreach ($final_results as &$result) {
                unset($result['relevance_score']);
                unset($result['post_id']);
            }
            
            return $final_results;
        }

        /**
         * Send Server-Sent Event
         */
        private function send_sse_event($data) {
            echo "data: " . json_encode($data) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }

        private function get_llm_client() {
            $api_key = $this->settings->get_gemini_key();
            if (empty($api_key)) {
                throw new Exception('Google Gemini API key is not set.');
            }

            $client = QSS_Gemini_Client::get_instance();

            return $client->initialize($api_key);
        }

        /**
         * Get prompt result using chosen LLM
         */
        private function get_prompt_result($system_prompt, $query) {
            // Get LLM client
            $client = $this->get_llm_client();

            // Get LLM model
            $model = $this->settings->get_llm_model('generative');
            
            try {
                $response = $client->generativeModel(model: $model)->generateContent(
                    $system_prompt . '\n User query: ' . $query
                );
                return $response->text();
            } catch (Exception $e) {
                treyworks_log('LLM API Error: ' . $e->getMessage(), TREYWORKS_LOG_ERROR);
                return $query;
            }
        }

        /**
         * Process search results using LLM
         */
        private function process_search_results($system_prompt, $results, $query) {

            // Get LLM client and model
            $client = $this->get_llm_client();
            $model = $this->settings->get_llm_model('extraction');

            // Format results for the API
            $formatted_results = array_map(function($result) {
                return array(
                    'title' => $result['title'],
                    'content' => wp_strip_all_tags($result['content']),
                    'url' => $result['permalink']
                );
            }, array_slice($results, 0, 5));

            // Encode results
            $encoded_results = json_encode([
                'query' => $query,
                'results' => $formatted_results
            ]);

            // Create prompt
            $prompt = $system_prompt . '\nSearch results:\n' . $encoded_results;

            try {
                $response = $client->generativeModel(model: $model)->generateContent(
                    $prompt . '\n\nUser query: ' . $query
                );
                return $response->text();
            } catch (Exception $e) {
                treyworks_log('LLM API Error: ' . $e->getMessage(), TREYWORKS_LOG_ERROR);
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

            // Register SSE stream search route
            register_rest_route('treyworks-search/v1', '/search-stream', [
                'methods' => ['GET'],
                'callback' => [ $this, 'get_search_results_stream' ],
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

        // Function to handle SSE stream search requests
        public function get_search_results_stream($request) {
            // Set SSE headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            
            // Disable output buffering for immediate streaming
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            for ($i = 0; $i < ob_get_level(); $i++) {
                ob_end_flush();
            }
            ob_implicit_flush(1);

            // Verify nonce from query parameter
            $nonce = $request->get_param('_wpnonce');
            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                $this->send_sse_event(['phase' => 'error', 'message' => 'Invalid nonce']);
                exit;
            }

            // Verify request server
            if (!$this->verify_request_server()) {
                $this->send_sse_event(['phase' => 'error', 'message' => 'Cross Origin access forbidden']);
                exit;
            }

            // Get custom prompts or use defaults
            $summary_prompt = get_option('qss_plugin_create_summary_prompt', QSS_Default_Prompts::CREATE_SUMMARY);
            $extract_search_term_prompt = get_option('qss_plugin_extract_search_term_prompt', QSS_Default_Prompts::EXTRACT_SEARCH_TERM);

            try {
                // Get search query from URL parameter
                $search_query = $request->get_param('search_query');

                if (empty($search_query)) {
                    $this->send_sse_event(['phase' => 'error', 'message' => 'Search query is required']);
                    exit;
                }

                // Phase 1: Extracting search terms
                $this->send_sse_event(['phase' => 'extracting', 'message' => 'Analyzing your question...']);
                
                $extracted_search_term = $this->get_prompt_result($extract_search_term_prompt, $search_query);

                // Phase 2: Searching the site
                $this->send_sse_event(['phase' => 'searching', 'message' => 'Searching the site...']);
                
                // Handle post_ids if provided
                $post_ids = null;
                $post_ids_param = $request->get_param('post_ids');
                if (!empty($post_ids_param)) {
                    $post_ids = array_map('intval', explode(',', sanitize_text_field($post_ids_param)));
                    $post_ids = array_filter($post_ids, function($id) { return $id > 0; });
                    if (empty($post_ids)) {
                        $post_ids = null;
                    }
                }

                $search_results = $this->search_site($extracted_search_term, $post_ids);

                // Phase 3: Crafting the answer
                $this->send_sse_event(['phase' => 'summarizing', 'message' => 'Crafting your answer...']);
                
                $summary = $this->process_search_results($summary_prompt, $search_results, $search_query);

                // Get model information for logging
                $extraction_model = $this->settings->get_llm_model('extraction');
                $generative_model = $this->settings->get_llm_model('generative');

                // Log search results
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Search complete: ' . $search_query), TREYWORKS_LOG_INFO, [
                    'extracted_search_term' => $extracted_search_term, 
                    'summary' => $summary,
                    'referer' => $referer,
                    'llm_provider' => 'gemini',
                    'extraction_model' => $extraction_model,
                    'generative_model' => $generative_model
                ]);

                // Phase 4: Complete
                $this->send_sse_event([
                    'phase' => 'complete',
                    'message' => 'Done!',
                    'data' => [
                        'query' => $search_query,
                        'results' => $search_results,
                        'summary' => $summary
                    ]
                ]);

            } catch (Exception $e) {
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Search error: ' . $e->getMessage()), TREYWORKS_LOG_ERROR, [
                    'exception' => get_class($e),
                    'referer' => $referer,
                    'llm_provider' => 'gemini'
                ]);
                
                $this->send_sse_event([
                    'phase' => 'error',
                    'message' => 'Error processing search request: ' . $e->getMessage()
                ]);
            }
            
            exit;
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
                $extracted_search_term = $this->get_prompt_result($extract_search_term_prompt, $search_query);

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
                $summary = $this->process_search_results($summary_prompt, $search_results, $search_query);

                // Get model information for logging
                $extraction_model = $this->settings->get_llm_model('extraction');
                $generative_model = $this->settings->get_llm_model('generative');

                // Log search results with referer URL and model info
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Search complete: ' . $search_query), TREYWORKS_LOG_INFO, [
                    'extracted_search_term' => $extracted_search_term, 
                    'summary' => $summary,
                    'referer' => $referer,
                    'llm_provider' => 'gemini',
                    'extraction_model' => $extraction_model,
                    'generative_model' => $generative_model
                ]);
                
                // Return search results and summary
                return new WP_REST_Response([
                    'query' => $search_query,
                    'results' => $search_results,
                    'summary' => $summary
                ], 200);

            } catch (Exception $e) {
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Search error: ' . $e->getMessage()), TREYWORKS_LOG_ERROR, [
                    'exception' => get_class($e),
                    'referer' => $referer,
                    'llm_provider' => 'gemini'
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
                $extracted_search_term = $this->get_prompt_result($extract_search_term_prompt, $search_query);

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
                $answer = $this->process_search_results($answer_prompt, $search_results, $search_query);
                
                // Get model information for logging
                $extraction_model = $this->settings->get_llm_model('extraction');
                $generative_model = $this->settings->get_llm_model('generative');
                
                // Log search results with referer URL and model info
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Answer complete: ' . $search_query), TREYWORKS_LOG_INFO, [
                    'answer' => $answer, 
                    'extracted_search_term' => $extracted_search_term,
                    'referer' => $referer,
                    'llm_provider' => 'gemini',
                    'extraction_model' => $extraction_model,
                    'generative_model' => $generative_model
                ]);

                // Return answer
                return new WP_REST_Response($answer, 200);

            } catch (Exception $e) {
                $referer = $request->get_header('referer') ? $request->get_header('referer') : 'unknown';
                treyworks_log(__('Error getting answer: ' . $e->getMessage()), TREYWORKS_LOG_ERROR, [
                    'exception' => get_class($e),
                    'referer' => $referer,
                    'llm_provider' => 'gemini'
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
                treyworks_log(__('Invalid nonce ' . $nonce), TREYWORKS_LOG_ERROR, ['nonce' => $nonce, 'referer' => $referer]);
                return false;
            }

            return true;
        }

        /**
         * Verify request server
         * Returns a boolean
         */
        public function verify_request_server() {
            // Check if trusted domains feature is enabled
            $enable_trusted_domains = get_option('qss_enable_trusted_domains', 0);
            
            if (!$enable_trusted_domains) {
                // If trusted domains is not enabled, allow all requests
                return true;
            }
            
            // Get request domain
            $request_domain = $_SERVER['HTTP_HOST'];
            
            // Get site domain
            $site_url = get_bloginfo('url');
            $parsed_url = parse_url($site_url);
            $server_domain = $parsed_url['host'];
            
            // Check if request is from same domain
            if ($request_domain === $server_domain) {
                return true;
            }
            
            // Get trusted domains list
            $trusted_domains = get_option('qss_trusted_domains', '');
            $trusted_domains_array = array_filter(array_map('trim', explode("\n", $trusted_domains)));
            
            // Check if request domain is in trusted list
            if (in_array($request_domain, $trusted_domains_array)) {
                return true;
            }
            
            // Domain not trusted - log and reject
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'unknown';
            treyworks_log(__('Cross Origin access forbidden'), TREYWORKS_LOG_ERROR, [
                'request_domain' => $request_domain, 
                'server_domain' => $server_domain,
                'referer' => $referer,
                'trusted_domains_enabled' => true
            ]);
            
            return false;
        }
    }

}


// Initialize the plugin
function treyworks_search() {
    return QuickSearchSummarizer::get_instance();
}

// Start the plugin
treyworks_search();
