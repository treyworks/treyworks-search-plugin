/**
 * Admin Logs JavaScript
 */

(function($) {
    'use strict';

    // DOM ready
    $(function() {
        const $logsTable = $('.treyworks-search-logs');
        const $modal = $('#context-modal');
        const $modalContent = $('#context-data');
        
        // Open modal
        $logsTable.on('click', '.view-context', function(e) {
            e.preventDefault();
            const contextData = $(this).data('context');
            
            // Format JSON for display with syntax highlighting
            try {
                // If it's already an object, use it directly, otherwise parse it
                const jsonObject = typeof contextData === 'string' ? JSON.parse(contextData) : contextData;
                const formattedJson = syntaxHighlightJSON(jsonObject);
                $modalContent.html(formattedJson);
            } catch (e) {
                $modalContent.text(contextData);
            }
            
            $modal.fadeIn(200);
            $('body').css('overflow', 'hidden'); // Prevent background scrolling
        });
        
        /**
         * Syntax highlight JSON
         */
        function syntaxHighlightJSON(json) {
            if (typeof json !== 'string') {
                json = JSON.stringify(json, null, 2);
            }
            
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }
        
        // Close modal
        $('.treyworks-modal-close, .treyworks-modal-overlay').on('click', function() {
            $modal.fadeOut(200);
            $('body').css('overflow', '');
        });
        
        // Close on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                $modal.fadeOut(200);
                $('body').css('overflow', '');
            }
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
