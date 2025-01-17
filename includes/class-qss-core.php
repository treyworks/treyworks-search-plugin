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
        $modal_title = get_option('qss_plugin_modal_title', __('Search the site', 'quick-search-summarizer'));
        ?>
        <!-- Modal Structure -->
        <div id="qss-modal" class="qss-modal">
            <div class="qss-modal-content">
                <div class="qss-modal-header">
                    <h2 class="qss-modal-title"><?php echo esc_html($modal_title); ?></h2>
                    <button class="qss-close-modal" aria-label="<?php echo esc_attr__('Close modal', 'quick-search-summarizer'); ?>">&times;</button>
                </div>
                
                <div class="qss-modal-search-container">
                    <div class="qss-modal-search">
                        <div class="qss-search-input-wrapper">
                            <input type="text" id="qss-modal-search-input" class="qss-search-input" placeholder="<?php echo esc_attr__('Refine your search...', 'quick-search-summarizer'); ?>">
                            <!-- <button type="button" class="qss-clear-button" aria-label="Clear search">&times;</button> -->
                        </div>
                        <button id="qss-modal-search-button" class="qss-search-button" aria-label="<?php echo esc_attr__('Search', 'quick-search-summarizer'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="qss-search-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="qss-loading" class="qss-loading"><?php echo esc_html__('Searching...', 'quick-search-summarizer'); ?></div>

                <div id="qss-search-results" class="qss-search-results" style="display: none;">
                    <div id="qss-summary" class="qss-summary"></div>
                    
                    <div class="qss-sources-wrapper">
                        <button class="qss-sources-toggle" aria-expanded="false">
                            <span><?php echo esc_html__('View all search results', 'quick-search-summarizer'); ?></span>
                            <svg class="qss-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                            </svg>
                        </button>
                        <div id="qss-sources-list" class="qss-sources-list" style="display: none;"></div>
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
                'rest_url' => esc_url_raw(rest_url('quick-search-summarizer/v1/search'))
            )
        );
    }
}
