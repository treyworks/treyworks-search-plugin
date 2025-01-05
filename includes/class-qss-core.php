<?php
if (!defined('ABSPATH')) {
    exit;
}

class QSS_Core {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('quick_search_summarizer', array($this, 'render_search_form'));
        add_action('wp_footer', array($this, 'render_modal'));
    }

    public function init() {
        // Initialize core functionality
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render_search_form() {
        ob_start();
        ?>
        <form id="qss-search-form" class="qss-search-form">
            <div class="qss-search-input-group">
                <div class="qss-search-input-wrapper">
                    <input type="text" id="qss-search-input" class="qss-search-input" placeholder="<?php echo esc_attr__('Enter your search query...', 'quick-search-summarizer'); ?>" required>
                    <button type="button" class="qss-clear-button" aria-label="Clear search">&times;</button>
                </div>
                <button type="submit" class="qss-search-button"><?php echo esc_html__('Search', 'quick-search-summarizer'); ?></button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_modal() {
        ?>
        <!-- Modal Structure -->
        <div id="qss-modal" class="qss-modal">
            <div class="qss-modal-content">
                <span class="qss-close-modal">&times;</span>
                
                <div class="qss-modal-header">
                    <div class="qss-modal-search">
                        <div class="qss-search-input-wrapper">
                            <input type="text" id="qss-modal-search-input" class="qss-search-input" placeholder="<?php echo esc_attr__('Refine your search...', 'quick-search-summarizer'); ?>">
                            <button type="button" class="qss-clear-button" aria-label="Clear search">&times;</button>
                        </div>
                        <button id="qss-modal-search-button" class="qss-search-button"><?php echo esc_html__('Search', 'quick-search-summarizer'); ?></button>
                    </div>
                </div>

                <div id="qss-loading" class="qss-loading"><?php echo esc_html__('Searching...', 'quick-search-summarizer'); ?></div>

                <div id="qss-search-results" class="qss-modal-body">
                    <div class="qss-main-content">
                        <div id="qss-summary" class="qss-summary"></div>
                        <div id="qss-results-list" class="qss-results-list"></div>
                    </div>
                    <div class="qss-sidebar">
                        <div id="qss-sources-list" class="qss-sources-list"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
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

        // Enqueue plugin JavaScript
        wp_enqueue_script(
            'quick-search-summarizer',
            QSS_PLUGIN_URL . 'assets/js/quick-search-summarizer.js',
            array('jquery', 'marked'),
            QSS_VERSION,
            true
        );

        // Enqueue plugin CSS
        wp_enqueue_style(
            'quick-search-summarizer',
            QSS_PLUGIN_URL . 'assets/css/quick-search-summarizer.css',
            array(),
            QSS_VERSION
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
            'quick-search-summarizer',
            'qssAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => esc_url_raw(rest_url('quick-search-summarizer/v1/search')),
                'site_url' => get_site_url()
            )
        );
    }
}
