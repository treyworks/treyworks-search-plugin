<?php
/**
 * Modal Template
 * Renders the search modal for the Quick Search Summarizer plugin.
 */

// Get the modal title from options, with a default fallback
$modal_title = get_option('qss_plugin_modal_title', __('Search the site', 'quick-search-summarizer'));
$search_input_placeholder = get_option('qss_plugin_search_input_placeholder', __('Refine your search...', 'quick-search-summarizer'));
$common_questions = get_option('qss_plugin_common_questions', '');
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
                    <input type="text" id="qss-modal-search-input" class="qss-search-input" placeholder="<?php echo esc_attr($search_input_placeholder); ?>">
                </div>
                <button id="qss-modal-search-button" class="qss-search-button" aria-label="<?php echo esc_attr__('Search', 'quick-search-summarizer'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="qss-search-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </button>
            </div>
            
            <?php if (!empty($common_questions)) : ?>
            <div id="qss-common-questions" class="qss-common-questions">
                <div class="qss-common-questions-header">
                    <div class="qss-common-questions-title"><?php echo esc_html__('Common Questions:', 'quick-search-summarizer'); ?></div>
                    <button type="button" class="qss-dismiss-questions" aria-label="<?php echo esc_attr__('Dismiss common questions', 'quick-search-summarizer'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="qss-common-questions-list">
                    <?php 
                    $questions = explode("\n", $common_questions);
                    foreach ($questions as $question) {
                        $question = trim($question);
                        if (!empty($question)) {
                            echo '<div class="qss-common-question">' . esc_html($question) . '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
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
