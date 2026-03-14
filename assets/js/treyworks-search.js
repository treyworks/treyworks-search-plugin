jQuery(document).ready(function($) {
    const searchForm = $('#qss-search-form');
    const searchInput = $('#qss-search-input');
    const modalSearchInput = $('#qss-modal-search-input');
    const modalSearchButton = $('#qss-modal-search-button');
    const searchResults = $('#qss-search-results');
    const summaryContainer = $('#qss-summary');
    const sourcesList = $('#qss-sources-list');
    const sourcesCount = $('#qss-sources-count');
    const loadingIndicator = $('#qss-loading');
    const modal = $('#qss-modal');
    const closeModal = $('.qss-close-modal');
    const clearButtons = $('.qss-clear-button');
    const commonQuestions = $('#qss-common-questions');
    const dismissQuestionsBtn = $('.qss-dismiss-questions');
    
    // Question form elements
    const questionForm = $('#qss-question-form');
    const questionInput = $('#qss-question-input');
    const answerContainer = $('#qss-answer-container');
    const answerContent = $('.qss-answer-content');
    const questionLoading = $('#qss-question-loading');
    const clearFormButton = $('#qss-clear-form');

    // Check for search query parameter on page load if replace WP search is enabled
    if (qssConfig.replaceWpSearch) {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('s');
        if (searchParam) {
            const decodedSearch = decodeURIComponent(searchParam);
            showSearchModal(decodedSearch);
        }
    }

    // Configure marked.js
    marked.setOptions({
        breaks: true,
        gfm: true,
        highlight: function(code, lang) {
            if (Prism.languages[lang]) {
                return Prism.highlight(code, Prism.languages[lang], lang);
            }
            return code;
        }
    });

    // Function to show modal with optional initial search
    function showSearchModal(initialQuery = '') {
        if (initialQuery) {
            modalSearchInput.val(initialQuery);
            performSearch(initialQuery);
        } else {
            // Only show common questions when no initial query
            if (commonQuestions.length) {
                commonQuestions.removeClass('qss-hidden');
            }
        }
        
        modal.addClass('active fade-in');
        $('.qss-modal-content').addClass('slide-in-bck-center');
        
        // Focus the input immediately
        if (window.matchMedia("(min-width: 768px)").matches) {
            modalSearchInput.focus();
        }
    }

    // Handle dismiss button click for common questions
    dismissQuestionsBtn.on('click', function(e) {
        e.preventDefault();
        commonQuestions.addClass('qss-hidden');
    });

    // Handle quick search triggers
    $(document).on('click', '.qss-trigger-modal', function(e) {
        e.preventDefault();
        const searchQuery = $(this).data('search');
        showSearchModal(searchQuery);
    });

    // Handle common question clicks
    $(document).on('click', '.qss-common-question', function() {
        const $this = $(this);
        const questionText = $this.text();
        
        // Add visual feedback
        $this.addClass('clicked');
        setTimeout(() => {
            $this.removeClass('clicked');
        }, 300);
        
        // Set input value and perform search
        modalSearchInput.val(questionText);
        performSearch(questionText);
    });

    // Handle initial search form submission
    searchForm.on('submit', function(e) {
        e.preventDefault();
        const query = searchInput.val().trim();
        if (!query) return;

        // Copy query to modal search input and show modal
        showSearchModal(query);
    });

    // Handle modal search button click
    modalSearchButton.on('click', function() {
        const query = modalSearchInput.val().trim();
        if (!query) return;
        performSearch(query);
    });

    // Handle modal search input enter key
    modalSearchInput.on('keypress', function(e) {
        if (e.which === 13) {
            const query = modalSearchInput.val().trim();
            if (!query) return;
            performSearch(query);
        }
    });

    // Handle clear button clicks
    clearButtons.on('click', function() {
        const input = $(this).siblings('input');
        input.val('').focus();
    });

    // Show/hide clear button based on input content
    $('.qss-search-input').on('input', function() {
        const clearButton = $(this).siblings('.qss-clear-button');
        clearButton.css('opacity', this.value.length ? '1' : '0');
    }).trigger('input'); // Initialize button visibility

    // Close modal with animation
    function closeModalWithAnimation() {
        modal.addClass('fade-out');
        $('.qss-modal-content').addClass('slide-out-bck-center');
        
        // Remove modal after animation completes
        setTimeout(() => {
            modal.removeClass('active fade-in fade-out');
            $('.qss-modal-content').removeClass('slide-in-bck-center slide-out-bck-center');
        }, 700); // Match animation duration
    }

    // Close modal
    closeModal.on('click', function() {
        closeModalWithAnimation();
    });

    // Close modal on outside click
    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            closeModalWithAnimation();
        }
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Escape key to close modal
        if (e.key === 'Escape' && modal.hasClass('active')) {
            closeModalWithAnimation();
        }
        // Control+K to open modal
        if (e.key === 'k' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault(); // Prevent default browser behavior
            showSearchModal();
        }
    });

    function updateLoadingMessage(message) {
        // Update phase indicators - remove active from all, then add to current
        $('.qss-phase-indicator').removeClass('active');
        
        const phase = message.toLowerCase();
        if (phase.includes('analyzing')) {
            $('.qss-phase-indicator[data-phase="extracting"]').addClass('active');
        } else if (phase.includes('searching')) {
            $('.qss-phase-indicator[data-phase="searching"]').addClass('active');
        } else if (phase.includes('crafting')) {
            $('.qss-phase-indicator[data-phase="summarizing"]').addClass('active');
        }
    }

    function displaySearchResults(data) {
        // Hide loading indicator
        loadingIndicator.hide();
        searchResults.show();

        // Display summary if available
        if (data.summary) {
            const parsedSummary = marked.parse(data.summary);
            summaryContainer.html('<div class="qss-markdown">' + parsedSummary + '</div>');
        }

        // Display results and collect sources
        if (data.results && data.results.length > 0) {
            sourcesCount.text(`${data.results.length} result${data.results.length === 1 ? '' : 's'}`);
            for (let i = 0; i < data.results.length; i++) {
                const result = data.results[i];
                const truncatedContent = truncateText(stripHtml(result.content), 200);

                sourcesList.append(`
                    <div class="qss-source-item">
                        <span class="qss-source-number">${i + 1}</span>
                        <div class="qss-source-content">
                            <a href="${result.permalink}" target="_blank" rel="noopener noreferrer">${result.title}</a>
                            <p class="qss-source-excerpt">${escapeHtml(truncatedContent)}</p>
                        </div>
                    </div>
                `);
            }
        } else {
            sourcesCount.text('');
            // No results found
            summaryContainer.html('<div class="qss-no-results">No results found. Please try a different search query.</div>');
        }
    }

    function performSearchFallback(query) {
        // Fallback to regular AJAX if SSE fails
        $.ajax({
            url: qssConfig.rest_url,
            method: 'POST',
            headers: {
                'X-WP-Nonce': qssConfig.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                search_query: query
            }),
            success: function(response) {
                displaySearchResults(response);
            },
            error: function(xhr, status, error) {
                loadingIndicator.hide();
                searchResults.show();
                
                let errorMessage = 'An error occurred while processing your search.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                summaryContainer.html(`<div class="qss-error">${errorMessage}</div>`);
                console.error('Search error:', error);
            }
        });
    }

    function performSearch(query) {
        // Hide common questions when search is performed
        if (commonQuestions.length) {
            commonQuestions.addClass('qss-hidden');
        }

        // Show loading indicator
        loadingIndicator.show();
        searchResults.hide();
        summaryContainer.empty();
        sourcesList.empty();
        sourcesCount.text('');
        updateLoadingMessage('Initializing...');

        // Check if EventSource is supported
        if (typeof EventSource === 'undefined') {
            console.log('EventSource not supported, using fallback');
            performSearchFallback(query);
            return;
        }

        // Build SSE URL with query parameters
        const streamUrl = qssConfig.stream_url + 
            '?search_query=' + encodeURIComponent(query) + 
            '&_wpnonce=' + qssConfig.nonce;

        const eventSource = new EventSource(streamUrl);

        eventSource.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                
                switch(data.phase) {
                    case 'extracting':
                        updateLoadingMessage(data.message);
                        break;
                    case 'searching':
                        updateLoadingMessage(data.message);
                        break;
                    case 'summarizing':
                        updateLoadingMessage(data.message);
                        break;
                    case 'complete':
                        eventSource.close();
                        displaySearchResults(data.data);
                        break;
                    case 'error':
                        eventSource.close();
                        loadingIndicator.hide();
                        searchResults.show();
                        summaryContainer.html(`<div class="qss-error">${data.message}</div>`);
                        break;
                }
            } catch (e) {
                console.error('Error parsing SSE data:', e);
                eventSource.close();
                performSearchFallback(query);
            }
        };

        eventSource.onerror = function(error) {
            console.error('SSE error, falling back to AJAX:', error);
            eventSource.close();
            performSearchFallback(query);
        };
    }

    // Handle question form submission
    $(document).on('submit', '#qss-question-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const question = questionInput.val();
        const postIds = $form.data('post-ids'); // Get post IDs from data attribute

        // Basic validation
        if (!question.trim()) {
            alert('Please enter a question.');
            return;
        }

        // Prepare data for AJAX request
        const data = {
            search_query: question,
        };
        
        // Add post_ids to data if available
        if (postIds) {
            data.post_ids = postIds;
        }

        // Show loading indicator
        questionLoading.show();
        questionForm.hide();
        answerContainer.hide();

        // Make API call to the /get_answer endpoint
        $.ajax({
            url: qssConfig.get_answer_url, // Use the REST API endpoint URL
            type: 'POST', 
            headers: {
                'X-WP-Nonce': qssConfig.nonce,
                'Content-Type': 'application/json',
            },
            data: JSON.stringify(data), 
            success: function (response) {
                // Hide loading indicator
                questionLoading.hide();
                
                // Display answer
                const parsedAnswer = marked.parse(response);
                answerContent.html('<div class="qss-markdown">' + parsedAnswer + '</div>');
                answerContainer.show();
                questionForm.show();
            },
            error: function(xhr, status, error) {
                questionLoading.hide();
                
                let errorMessage = 'An error occurred while processing your question.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                answerContent.html(`<div class="qss-error">${errorMessage}</div>`);
                answerContainer.show();
                questionForm.show();
                console.error('Question error:', error);
            }
        });
    });
    
    // Handle clear form button click
    clearFormButton.on('click', function() {
        questionInput.val('').focus();
        answerContainer.hide();
    });
    
    // Handle clear button for question input
    questionForm.find('.qss-clear-button').on('click', function() {
        questionInput.val('').focus();
    });

    // Show/hide clear button for question input
    questionInput.on('input', function() {
        const clearButton = $(this).siblings('.qss-clear-button');
        clearButton.css('opacity', this.value.length ? '1' : '0');
    }).trigger('input');

    // Helper function to strip HTML tags
    function stripHtml(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent || '';
    }

    // Helper function to truncate text
    function truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }
});
