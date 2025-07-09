<?php
if (!defined('ABSPATH')) {
    exit;
}

class QSS_Core {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('treyworks_search', array($this, 'render_search_form'));
        add_shortcode('treyworks_answer', array($this, 'render_question_form')); 
        add_action('wp_footer', array($this, 'render_modal'));
    }

    public function init() {
        // Initialize core functionality
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render_search_form() {
        ob_start();
        include PLUGIN_DIR . 'templates/search-form.php';
        return ob_get_clean();
    }

    public function render_question_form($atts) { 
        // Extract attributes, specifically 'post_ids'
        $atts = shortcode_atts(
            array(
                'post_ids' => '', // Default to empty string
            ),
            $atts,
            'treyworks_answer'
        );

        // Sanitize the post_ids attribute (basic sanitation)
        $post_ids = sanitize_text_field($atts['post_ids']);
        
        ob_start();
        // Pass the post_ids to the template
        include PLUGIN_DIR . 'templates/question-form.php';
        return ob_get_clean();
    }

    public function render_modal() {
        include PLUGIN_DIR . 'templates/modal.php';
    }

    public function enqueue_scripts() {
        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue marked.js for markdown parsing
        wp_enqueue_script(
            'marked',
            'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            array(),
            '9.1.2',
            true
        );

        // Enqueue plugin CSS
        wp_enqueue_style(
            'qss-styles',
            PLUGIN_URL . 'assets/css/treyworks-search.css',
            array(),
            PLUGIN_VERSION
        );

        // Enqueue plugin JavaScript
        wp_enqueue_script(
            'qss-script',
            PLUGIN_URL . 'assets/js/treyworks-search.js',
            array('jquery', 'marked'),
            PLUGIN_VERSION,
            true
        );

        // Add prism.js for syntax highlighting
        wp_enqueue_style(
            'prismjs',
            'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css',
            array(),
            '1.29.0'
        );
        
        wp_enqueue_script(
            'prismjs',
            'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js',
            array(),
            '1.29.0',
            true
        );

        // Localize script
        wp_localize_script(
            'qss-script',
            'qssConfig',
            array(
                'replaceWpSearch' => (bool) get_option('qss_plugin_replace_wp_search', false),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => esc_url_raw(rest_url('treyworks-search/v1/search')),
                'get_answer_url' => esc_url_raw(rest_url('treyworks-search/v1/get_answer'))
            )
        );
    }
}
