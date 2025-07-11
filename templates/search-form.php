<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<form id="qss-search-form" class="qss-search-form">
    <div class="qss-search-input-group">
        <div class="qss-search-input-wrapper">
            <input type="text" id="qss-search-input" class="qss-search-input" placeholder="<?php echo esc_attr__('Enter your search query...', 'treyworks-search'); ?>" required>
            <button type="button" class="qss-clear-button" aria-label="Clear search">&times;</button>
        </div>
        <button type="submit" class="qss-search-button"><?php echo esc_html__('Search', 'treyworks-search'); ?></button>
    </div>
</form>
