/**
 * Admin Logs JavaScript
 */

(function($) {
    'use strict';

    // DOM ready
    $(function() {
        const $logsTable = $('.treyworks-search-logs');
        
        // Toggle context visibility
        $logsTable.on('click', '.toggle-context', function() {
            const $button = $(this);
            const $context = $button.next('.log-context');
            
            $context.slideToggle(200);
            
            if ($button.text() === treyworksSearchLogs.showContext) {
                $button.text(treyworksSearchLogs.hideContext);
            } else {
                $button.text(treyworksSearchLogs.showContext);
            }
        });
        
        // Delete single log
        $logsTable.on('click', '.delete-log', function() {
            const logId = $(this).data('id');
            deleteLogs([logId]);
        });
        
        // Delete selected logs
        $('#delete-selected-logs').on('click', function() {
            const selectedLogs = [];
            
            $('input[name="log_ids[]"]:checked').each(function() {
                selectedLogs.push($(this).val());
            });
            
            if (selectedLogs.length === 0) {
                alert(treyworksSearchLogs.noLogsSelected);
                return;
            }
            
            if (confirm(treyworksSearchLogs.confirmDelete)) {
                deleteLogs(selectedLogs);
            }
        });
        
        // Delete all logs
        $('#delete-all-logs').on('click', function() {
            if (confirm(treyworksSearchLogs.confirmDeleteAll)) {
                deleteAllLogs();
            }
        });
        
        // Select all checkboxes
        $('#cb-select-all, #cb-select-all-2').on('click', function() {
            $('input[name="log_ids[]"]').prop('checked', $(this).prop('checked'));
        });
        
        /**
         * Delete specific logs
         */
        function deleteLogs(logIds) {
            $.ajax({
                url: treyworksSearchLogs.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'treyworks_search_delete_logs',
                    nonce: treyworksSearchLogs.nonce,
                    log_ids: logIds
                },
                beforeSend: function() {
                    // Show loading indicator or disable buttons
                },
                success: function(response) {
                    if (response.success) {
                        // Remove deleted rows
                        for (let i = 0; i < logIds.length; i++) {
                            $('#log-' + logIds[i]).remove();
                        }
                        
                        // Show success message
                        showNotice(response.data.message, 'success');
                        
                        // If no logs remain, refresh the page
                        if ($('#the-list tr').length === 0) {
                            location.reload();
                        }
                    } else {
                        // Show error message
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    // Show error message
                    showNotice(treyworksSearchLogs.ajaxError, 'error');
                },
                complete: function() {
                    // Hide loading indicator or enable buttons
                }
            });
        }
        
        /**
         * Delete all logs
         */
        function deleteAllLogs() {
            $.ajax({
                url: treyworksSearchLogs.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'treyworks_search_delete_all_logs',
                    nonce: treyworksSearchLogs.nonce
                },
                beforeSend: function() {
                    // Show loading indicator or disable buttons
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show empty table
                        location.reload();
                    } else {
                        // Show error message
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    // Show error message
                    showNotice(treyworksSearchLogs.ajaxError, 'error');
                },
                complete: function() {
                    // Hide loading indicator or enable buttons
                }
            });
        }
        
        /**
         * Show admin notice
         */
        function showNotice(message, type) {
            const $notice = $('<div class="notice is-dismissible"></div>').addClass('notice-' + type);
            const $message = $('<p></p>').text(message);
            
            $notice.append($message);
            $('.wrap > h1').after($notice);
            
            // Add dismiss button functionality
            const $button = $('<button type="button" class="notice-dismiss"></button>');
            $button.append('<span class="screen-reader-text">Dismiss this notice.</span>');
            $button.on('click', function() {
                $notice.remove();
            });
            
            $notice.append($button);
        }
    });
})(jQuery);
