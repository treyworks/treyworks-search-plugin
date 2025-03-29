<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="qss-question-container">
    <form id="qss-question-form" class="qss-search-form">
        <div class="qss-search-input-group">
            <div class="qss-search-input-wrapper">
                <input type="text" id="qss-question-input" class="qss-search-input" placeholder="<?php echo esc_attr__('Ask a question...', 'quick-search-summarizer'); ?>" required>
                <button type="button" class="qss-clear-button" aria-label="Clear question">&times;</button>
            </div>
            <button type="submit" class="qss-search-button"><?php echo esc_html__('Ask', 'quick-search-summarizer'); ?></button>
        </div>
    </form>

    <div id="qss-answer-container" class="qss-answer-container" style="display: none;">
        <div class="qss-answer-content"></div>
    </div>

    <div id="qss-question-loading" class="qss-loading" style="display: none;">
        <div class="qss-loader"></div>
        <p><?php echo esc_html__('Finding your answer...', 'quick-search-summarizer'); ?></p>
    </div>
</div>
