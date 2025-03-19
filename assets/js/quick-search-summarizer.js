jQuery(document).ready(function($) {
    const searchForm = $('#qss-search-form');
    const searchInput = $('#qss-search-input');
    const modalSearchInput = $('#qss-modal-search-input');
    const modalSearchButton = $('#qss-modal-search-button');
    const searchResults = $('#qss-search-results');
    const summaryContainer = $('#qss-summary');
    const sourcesList = $('#qss-sources-list');
    const loadingIndicator = $('#qss-loading');
    const modal = $('#qss-modal');
    const closeModal = $('.qss-close-modal');
    const clearButtons = $('.qss-clear-button');
    const commonQuestions = $('#qss-common-questions');
    const dismissQuestionsBtn = $('.qss-dismiss-questions');

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

    // Handle sources toggle
    $(document).on('click', '.qss-sources-toggle', function() {
        const isExpanded = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', !isExpanded);
        
        const sourcesList = $('#qss-sources-list');
        sourcesList.slideToggle(200, function() {
            if (!isExpanded) {
                const modalContent = $('.qss-modal-content');
                const sourcesTop = sourcesList.offset().top;
                const modalScrollTop = modalContent.scrollTop();
                const modalTop = modalContent.offset().top;
                const scrollTo = modalScrollTop + (sourcesTop - modalTop) - 100; // 100px offset for better visibility
                
                modalContent.animate({
                    scrollTop: scrollTo
                }, 300);
            }
        });
    });

    function performSearch(query) {
        // Hide common questions when search is performed
        if (commonQuestions.length) {
            commonQuestions.addClass('qss-hidden');
        }

        // Show loading indicator
        loadingIndicator.show();
        searchResults.hide();
        summaryContainer.empty();
        sourcesList.empty().hide();
        $('.qss-sources-toggle').attr('aria-expanded', 'false');

        // Make API call
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
                // Hide loading indicator
                loadingIndicator.hide();
                searchResults.show();

                // Display summary if available
                if (response.summary) {
                    const parsedSummary = marked.parse(response.summary);
                    summaryContainer.html('<h3>Summary</h3><div class="qss-markdown">' + parsedSummary + '</div>');
                }

                // Display results and collect sources
                if (response.results && response.results.length > 0) {
                    // Add sources list header
                    sourcesList.append(`<h3>Sources</h3>`);
                    
                    for (let i = 0; i < response.results.length; i++) {
                        const result = response.results[i];
                        const truncatedContent = truncateText(stripHtml(result.content), 200);
                        const parsedContent = marked.parse(truncatedContent);
                        
                        // Add to sources list
                        sourcesList.append(`
                            <div class="qss-source-item">
                                <span class="qss-source-number">${i + 1}</span>
                                <a href="${result.permalink}" target="_blank">${result.title}</a>
                            </div>
                        `);
                    }
                } else {
                    // No results found
                    summaryContainer.html('<div class="qss-no-results">No results found. Please try a different search query.</div>');
                }
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

    // Helper function to strip HTML tags
    function stripHtml(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent || '';
    }

    // Helper function to truncate text
    function truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }
});
