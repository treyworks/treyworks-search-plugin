<?php
if (!defined('ABSPATH')) {
    exit;
}

class QSS_Core {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('quick_search_summarizer', array($this, 'render_search_form'));
        add_shortcode('quick_search_answer', array($this, 'render_question_form'));
        add_action('wp_footer', array($this, 'render_modal'));
    }

    public function init() {
        // Initialize core functionality
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render_search_form() {
        ob_start();
        include QSS_PLUGIN_DIR . 'templates/search-form.php';
        return ob_get_clean();
    }

    public function render_question_form() {
        ob_start();
        include QSS_PLUGIN_DIR . 'templates/question-form.php';
        return ob_get_clean();
    }

    public function render_modal() {
        include QSS_PLUGIN_DIR . 'templates/modal.php';
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
            QSS_PLUGIN_URL . 'assets/css/quick-search-summarizer.css',
            array(),
            QSS_VERSION
        );

        // Enqueue plugin JavaScript
        wp_enqueue_script(
            'qss-script',
            QSS_PLUGIN_URL . 'assets/js/quick-search-summarizer.js',
            array('jquery', 'marked'),
            QSS_VERSION,
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
                'rest_url' => esc_url_raw(rest_url('quick-search-summarizer/v1/search')),
                'get_answer_url' => esc_url_raw(rest_url('quick-search-summarizer/v1/get_answer'))
            )
        );
    }
}
