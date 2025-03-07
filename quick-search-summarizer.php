<?php
/**
 * Plugin Name: Quick Search Summarizer
 * Plugin URI: 
 * Description: A WordPress plugin for quick search and summarization using AI
 * Version: 1.0.2
 * Author: Treyworks LLC
 * Author URI: https://treyworks.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quick-search-summarizer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load Composer dependencies
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
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
            define('QSS_VERSION', '1.0.0');
            define('QSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
            define('QSS_PLUGIN_URL', plugin_dir_url(__FILE__));
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
            // Load logger class
            require_once QSS_PLUGIN_DIR . '/includes/class-logger.php';
            require_once QSS_PLUGIN_DIR . '/includes/class-qss-prompts.php';
            require_once QSS_PLUGIN_DIR . 'includes/class-qss-settings.php';
            require_once QSS_PLUGIN_DIR . 'includes/class-qss-core.php';
            require_once QSS_PLUGIN_DIR . '/includes/class-openai-client.php';
            require_once QSS_PLUGIN_DIR . '/includes/class-gemini-client.php';
        }

        private function init_classes() {
            $this->settings = QSS_Plugin_Settings::get_instance();
            $this->core = new QSS_Core();

            // Initialize the logger
            Plugin_Logger::initialize();
        }

        /**
         * Site Search Function
         */
        private function search_site($search_term) {
            $args = array(
                's' => $search_term,
                'post_type' => get_option('qss_plugin_searchable_post_types', array('post', 'page')),
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );

            $search_query = new WP_Query($args);
            $search_results = array();

            if ($search_query->have_posts()) {
                $result_count = 0;
                while ($search_query->have_posts()) {
                    $search_query->the_post();
                    if ($result_count < 5) {
                        $search_results[] = array(
                            'title' => get_the_title(),
                            'content' => wp_strip_all_tags(get_the_content()),
                            'permalink' => get_permalink()
                        );
                    } else {
                        $search_results[] = array(
                            'title' => get_the_title(),
                            'permalink' => get_permalink()
                        );
                    }
                    $result_count++;
                }
            }

            wp_reset_postdata();
            return $search_results;
        }

        private function get_llm_client($llm_provider = 'openai') {
            
            switch ($llm_provider) {
                case 'gemini':
                    $api_key = $this->settings->get_gemini_key();
                    if (empty($api_key)) {
                        throw new Exception('Google Gemini API key is not set.');
                    }
                    $client = QSS_Gemini_Client::get_instance();
                    break;
                    
                case 'openai':
                default:
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
         * Extract search term using LLM
         */
        private function extract_search_term($query, $llm_provider) {
            $client = $this->get_llm_client($llm_provider);
            
            // Get custom prompt or use default
            $system_prompt = get_option('qss_plugin_extract_search_term_prompt', QSS_Default_Prompts::EXTRACT_SEARCH_TERM);

            try {
                if ($llm_provider === 'gemini') {
                    $response = $client->geminiFlash()->generateContent(
                        $system_prompt . '\n User query: ' . $query
                    );
                    return $response->text();
                } else {
                    $response = $client->chat()->create([
                        'model' => 'gpt-4',
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
                error_log('LLM API Error: ' . $e->getMessage());
                return $query;
            }
        }

        /**
         * Create summary using LLM
         */
        private function create_summary($results, $query, $llm_provider) {

            $client = $this->get_llm_client($llm_provider);
            
            // Get custom prompt or use default
            $system_prompt = get_option('qss_plugin_create_summary_prompt', QSS_Default_Prompts::CREATE_SUMMARY);

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
                        'model' => 'gpt-4',
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
                error_log('LLM API Error: ' . $e->getMessage());
                return '';
            }
        }

        public function register_rest_api_routes() {
            // Register search route
            register_rest_route('quick-search-summarizer/v1', '/search', [
                'methods' => ['POST'],
                'callback' => [ $this, 'get_search_results' ],
                'permission_callback' => function() {
                    return true; // Allow public access to the endpoint
                }
            ]);
    
        }

         // Function to handle search requests
        public function get_search_results($request) {
            // Log if debugging is enabled
            Plugin_Logger::log(__('## New search request'));
            
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
            $api_key = '';
            
            Plugin_Logger::log(__('* Getting LLM API Key'));
            Plugin_Logger::log(__('* LLM Provider: ' . $llm_provider));
            
            if ($llm_provider === 'gemini') {
                $api_key = $this->settings->get_gemini_key();
            } else {
                $api_key = $this->settings->get_openai_key();
            }
            

            if (empty($api_key)) {
                return new WP_Error('missing_settings', 'The LLM API Key is not set.', ['status' => 400]);
            }

            try {
                // Get request parameters
                $params = $request->get_json_params();
                $search_query = $params['search_query'] ?? null;

                if (empty($search_query)) {
                    return new WP_Error('invalid_request', 'Search query is required', ['status' => 400]);
                }

                // Extract search term
                Plugin_Logger::log(__('* Extracting search term'));
                $extracted_search_term = $this->extract_search_term($search_query, $llm_provider);

                // Perform WordPress search
                // Plugin_Logger::log(__('* Performing WordPress search: ' . $extracted_search_term));

                $search_results = $this->search_site($extracted_search_term);
                
                Plugin_Logger::log(__('* Search results: ' . json_encode($search_results)));

                Plugin_Logger::log(__('* Creating summary of search results'));
                // Create summary of search results
                $summary = $this->create_summary($search_results, $search_query, $llm_provider);
                Plugin_Logger::log(__('* Summary generated successfully'));

                Plugin_Logger::log(__('* Returning search results'));
                return new WP_REST_Response([
                    'query' => $search_query,
                    'results' => $search_results,
                    'summary' => $summary
                ], 200);

            } catch (Exception $e) {
                Plugin_Logger::log(__('Search error: ' . $e->getMessage()));
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
                Plugin_Logger::log(__('Invalid nonce ' . $nonce));
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
                Plugin_Logger::log(__('Cross Origin access forbidden'));
                return false;
            }

            return true;
        }
    }

}


// Initialize the plugin
function quick_search_summarizer() {
    return QuickSearchSummarizer::get_instance();
}

// Start the plugin
quick_search_summarizer();
