<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap treyworks-search-logs">
    <h1><?php _e('Treyworks Search Logs', 'treyworks-search'); ?></h1>
    
    <!-- Filters -->
    <div class="tablenav top">
        <form method="get">
            <input type="hidden" name="page" value="treyworks-search-logs">
            <input type="hidden" name="post_type" value="treyworks_search">
            
            <div class="alignleft actions">
                <select name="level" id="filter-by-level">
                    <option value=""><?php _e('All Levels', 'treyworks-search'); ?></option>
                    <?php foreach ($log_levels as $level_key => $level_name): ?>
                        <option value="<?php echo esc_attr($level_key); ?>" <?php selected($level, $level_key); ?>>
                            <?php echo esc_html($level_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="date-from" class="screen-reader-text"><?php _e('From Date', 'treyworks-search'); ?></label>
                <input type="date" id="date-from" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php _e('From Date', 'treyworks-search'); ?>">
                
                <label for="date-to" class="screen-reader-text"><?php _e('To Date', 'treyworks-search'); ?></label>
                <input type="date" id="date-to" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php _e('To Date', 'treyworks-search'); ?>">
                
                <?php submit_button(__('Filter', 'treyworks-search'), 'action', false, false); ?>
                
                <?php if ($level || $date_from || $date_to || $search): ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=treyworks_search&page=treyworks-search-logs')); ?>" class="button">
                        <?php _e('Reset', 'treyworks-search'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="search-box">
                <label class="screen-reader-text" for="log-search-input"><?php _e('Search Logs', 'treyworks-search'); ?></label>
                <input type="search" id="log-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search logs...', 'treyworks-search'); ?>">
                <?php submit_button(__('Search', 'treyworks-search'), 'action', false, false, array('id' => 'search-submit')); ?>
            </div>
        </form>
        
        <div class="alignright actions">
            <?php if (!empty($logs_data['items'])): ?>
                <button id="delete-selected-logs" class="button"><?php _e('Delete Selected', 'treyworks-search'); ?></button>
                <button id="delete-all-logs" class="button"><?php _e('Delete All Logs', 'treyworks-search'); ?></button>
            <?php endif; ?>
        </div>
        
        <br class="clear">
    </div>
    
    <!-- Logs Table -->
    <form id="logs-filter" method="post">
        <table class="wp-list-table widefat fixed striped logs-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input id="cb-select-all" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Date', 'treyworks-search'); ?>
                    </th>
                    <th scope="col" class="manage-column column-level">
                        <?php _e('Level', 'treyworks-search'); ?>
                    </th>
                    <th scope="col" class="manage-column column-message">
                        <?php _e('Message', 'treyworks-search'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'treyworks-search'); ?>
                    </th>
                </tr>
            </thead>
            
            <tbody id="the-list">
                <?php if (empty($logs_data['items'])): ?>
                    <tr>
                        <td colspan="5" class="colspanchange">
                            <?php _e('No logs found.', 'treyworks-search'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs_data['items'] as $log): ?>
                        <tr id="log-<?php echo esc_attr($log->id); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                            </th>
                            <td class="column-date">
                                <?php echo esc_html(get_date_from_gmt($log->created_at, get_option('date_format') . ' ' . get_option('time_format'))); ?>
                            </td>
                            <td class="column-level">
                                <span class="log-level log-level-<?php echo esc_attr($log->log_level); ?>">
                                    <?php echo esc_html(isset($log_levels[$log->log_level]) ? $log_levels[$log->log_level] : $log->log_level); ?>
                                </span>
                            </td>
                            <td class="column-message">
                                <div class="log-message">
                                    <?php echo esc_html($log->message); ?>
                                </div>
                                <?php if (!empty($log->context)): ?>
                                    <button type="button" class="toggle-context button-link"><?php _e('Show Context', 'treyworks-search'); ?></button>
                                    <div class="log-context" style="display: none;">
                                        <pre><?php echo esc_html(json_encode($log->context, JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button-link delete-log" data-id="<?php echo esc_attr($log->id); ?>">
                                    <?php _e('Delete', 'treyworks-search'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Date', 'treyworks-search'); ?>
                    </th>
                    <th scope="col" class="manage-column column-level">
                        <?php _e('Level', 'treyworks-search'); ?>
                    </th>
                    <th scope="col" class="manage-column column-message">
                        <?php _e('Message', 'treyworks-search'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'treyworks-search'); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </form>
    
    <!-- Pagination -->
    <?php if ($logs_data['total_pages'] > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s item', '%s items', $logs_data['total_items'], 'treyworks-search'),
                        number_format_i18n($logs_data['total_items'])
                    ); ?>
                </span>
                
                <span class="pagination-links">
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $logs_data['total_pages'],
                        'current' => $logs_data['current_page'],
                        'add_args' => array(
                            'level' => $level,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            's' => $search
                        )
                    ));
                    
                    echo $page_links;
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>
