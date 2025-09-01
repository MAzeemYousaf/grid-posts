jQuery(document).ready(function($) {
    let loading = false;
    let currentPage = 1;
    let maxPages = 1;
    let infiniteScrollObserver = null;
    let pendingFilters = {};
    let appliedFilters = {};

    function loadPosts($wrapper, paged = 1, append = false) {
        if (loading) return;
        
        loading = true;
        currentPage = paged;
        
        let settings = $wrapper.data('settings');
        let search = appliedFilters.search || '';
        
        // Use applied filters instead of collecting from DOM
        let acfFilters = appliedFilters.acf || {};
        let taxFilters = appliedFilters.tax || {};
        let dateFrom = appliedFilters.dateFrom || '';
        let dateTo = appliedFilters.dateTo || '';
        
        // Debug logging
        console.log('Sending to server:', { search, acfFilters, taxFilters, dateFrom, dateTo });
        
        // Show loading state
        if (!append) {
            $wrapper.find('.gpw-posts-grid').html('<div class="gpw-loading">Loading...</div>');
        }
        
        $.ajax({
            url: GPW_Ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gpw_filter_posts',
                nonce: GPW_Ajax.nonce,
                post_type: settings.post_type,
                posts_per_page: settings.posts_per_page || 6,
                template_id: settings.template_id,
                paged: paged,
                search: search,
                acf_filters: acfFilters,
                tax_filters: taxFilters,
                date_from: dateFrom,
                date_to: dateTo,
                search_in_title: settings.search_in_title || 'yes',
                search_in_content: settings.search_in_content || 'yes',
                search_in_acf: settings.search_in_acf || 'yes'
            },
            success: function(res) {
                if (res.success) {
                    if (append) {
                        $wrapper.find('.gpw-posts-grid').append(res.data.html);
                    } else {
                        $wrapper.find('.gpw-posts-grid').html(res.data.html);
                    }
                    
                    maxPages = res.data.max_pages;
                    currentPage = res.data.current_page;
                    
                    // Update pagination
                    if (settings.show_pagination === 'yes') {
                        updatePagination($wrapper, res.data);
                    }
                    
                    // Update results count
                    updateResultsCount($wrapper, res.data.found_posts);
                }
            },
            error: function() {
                if (!append) {
                    $wrapper.find('.gpw-posts-grid').html('<div class="gpw-error">Error loading posts. Please try again.</div>');
                }
            },
            complete: function() {
                loading = false;
                $wrapper.find('.gpw-loading').remove();
            }
        });
    }
    
    function updatePagination($wrapper, data) {
        let settings = $wrapper.data('settings');
        let paginationType = settings.pagination_type || 'numbers';
        let $pagination = $wrapper.find('.gpw-pagination');
        
        if (data.max_pages <= 1) {
            $pagination.hide();
            return;
        }
        
        $pagination.show();
        let paginationHtml = '';
        
        switch (paginationType) {
            case 'numbers':
                paginationHtml = generateNumberedPagination(data.current_page, data.max_pages);
                break;
            case 'prev_next':
                paginationHtml = generatePrevNextPagination(data.current_page, data.max_pages);
                break;
            case 'load_more':
                if (data.current_page < data.max_pages) {
                    paginationHtml = '<button class="gpw-load-more">' + (settings.load_more_text || 'Load More Posts') + '</button>';
                }
                break;
            case 'infinite':
                // Infinite scroll is handled separately
                break;
        }
        
        $pagination.html(paginationHtml);
    }
    
    function generateNumberedPagination(currentPage, maxPages) {
        let html = '';
        
        // Previous button
        if (currentPage > 1) {
            html += '<button class="gpw-page gpw-prev" data-page="' + (currentPage - 1) + '">Previous</button>';
        }
        
        // Page numbers
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(maxPages, currentPage + 2);
        
        if (startPage > 1) {
            html += '<button class="gpw-page" data-page="1">1</button>';
            if (startPage > 2) {
                html += '<span class="gpw-ellipsis">...</span>';
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            let activeClass = i === currentPage ? ' gpw-active' : '';
            html += '<button class="gpw-page' + activeClass + '" data-page="' + i + '">' + i + '</button>';
        }
        
        if (endPage < maxPages) {
            if (endPage < maxPages - 1) {
                html += '<span class="gpw-ellipsis">...</span>';
            }
            html += '<button class="gpw-page" data-page="' + maxPages + '">' + maxPages + '</button>';
        }
        
        // Next button
        if (currentPage < maxPages) {
            html += '<button class="gpw-page gpw-next" data-page="' + (currentPage + 1) + '">Next</button>';
        }
        
        return html;
    }
    
    function generatePrevNextPagination(currentPage, maxPages) {
        let html = '';
        
        if (currentPage > 1) {
            html += '<button class="gpw-page gpw-prev" data-page="' + (currentPage - 1) + '">Previous</button>';
        }
        
        html += '<span class="gpw-page-info">Page ' + currentPage + ' of ' + maxPages + '</span>';
        
        if (currentPage < maxPages) {
            html += '<button class="gpw-page gpw-next" data-page="' + (currentPage + 1) + '">Next</button>';
        }
        
        return html;
    }
    
    function updateResultsCount($wrapper, foundPosts) {
        let $resultsCount = $wrapper.find('.gpw-results-count');
        if ($resultsCount.length === 0) {
            $resultsCount = $('<div class="gpw-results-count"></div>');
            $wrapper.find('.gpw-posts-grid').before($resultsCount);
        }
        
        if (foundPosts > 0) {
            $resultsCount.html('Found ' + foundPosts + ' post' + (foundPosts !== 1 ? 's' : ''));
        } else {
            $resultsCount.html('');
        }
    }
    
    function setupInfiniteScroll($wrapper) {
        let settings = $wrapper.data('settings');
        
        if (settings.show_pagination === 'yes' && settings.pagination_type === 'infinite') {
            // Remove existing observer
            if (infiniteScrollObserver) {
                infiniteScrollObserver.disconnect();
            }
            
            // Create new observer
            infiniteScrollObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && currentPage < maxPages && !loading) {
                        loadPosts($wrapper, currentPage + 1, true);
                    }
                });
            }, {
                rootMargin: '100px'
            });
            
            // Observe the last post
            let $lastPost = $wrapper.find('.gpw-post').last();
            if ($lastPost.length > 0) {
                infiniteScrollObserver.observe($lastPost[0]);
            }
        }
    }
    
    // Cleanup function for observers
    function cleanupObservers($wrapper) {
        const observer = $wrapper.data('mutationObserver');
        if (observer) {
            observer.disconnect();
            $wrapper.removeData('mutationObserver');
        }
        
        if (infiniteScrollObserver) {
            infiniteScrollObserver.disconnect();
            infiniteScrollObserver = null;
        }
    }
    
    // Setup accordion toggle functionality
    function setupAccordionToggle($wrapper) {
        $wrapper.on('click', '.gpw-accordion-header', function() {
            const $content = $(this).siblings('.gpw-accordion-content');
            const $toggle = $(this).find('.gpw-accordion-toggle');
            
            if ($content.is(':visible')) {
                $content.slideUp(300);
                $toggle.text('+');
            } else {
                $content.slideDown(300);
                $toggle.text('-');
            }
        });
    }
    
    // Collect pending filters from DOM
    function collectPendingFilters($wrapper) {
        let filters = {
            acf: {},
            tax: {},
            dateFrom: '',
            dateTo: ''
        };
        
        // Collect ACF filters
        $wrapper.find('.gpw-acf-filter').each(function() {
            let $field = $(this);
            let fieldName = $field.data('field');
            let fieldType = $field.attr('type');
            
            if (fieldType === 'checkbox') {
                if ($field.is(':checked')) {
                    if (!filters.acf[fieldName]) {
                        filters.acf[fieldName] = [];
                    }
                    filters.acf[fieldName].push($field.val());
                }
            } else if (fieldType === 'radio') {
                if ($field.is(':checked')) {
                    filters.acf[fieldName] = $field.val();
                }
            } else {
                let value = $field.val();
                if (value && value.trim() !== '') {
                    filters.acf[fieldName] = value;
                }
            }
        });
        
        // Collect taxonomy filters
        $wrapper.find('.gpw-tax-filter').each(function() {
            let $field = $(this);
            let taxonomy = $field.data('taxonomy');
            let fieldType = $field.attr('type');
            
            if (fieldType === 'checkbox') {
                if ($field.is(':checked')) {
                    if (!filters.tax[taxonomy]) {
                        filters.tax[taxonomy] = [];
                    }
                    filters.tax[taxonomy].push($field.val());
                }
            } else {
                let value = $field.val();
                if (value && value.trim() !== '') {
                    filters.tax[taxonomy] = value;
                }
            }
        });
        
        // Collect date filters
        filters.dateFrom = $wrapper.find('.gpw-date-filter-from').val();
        filters.dateTo = $wrapper.find('.gpw-date-filter-to').val();
        
        // Debug logging
        console.log('Collected filters:', filters);
        
        return filters;
    }
    
    // Update selected filters display
    function updateSelectedFiltersDisplay($wrapper) {
        let filters = collectPendingFilters($wrapper);
        let $selectedFilters = $wrapper.find('.gpw-selected-filters');
        let $selectedTags = $wrapper.find('.gpw-selected-filters-tags');
        let $selectedCount = $wrapper.find('.gpw-selected-count');
        
        let selectedItems = [];
        let totalCount = 0;
        
        // Count ACF filters
        Object.keys(filters.acf).forEach(fieldName => {
            if (Array.isArray(filters.acf[fieldName])) {
                filters.acf[fieldName].forEach(value => {
                    selectedItems.push({
                        type: 'acf',
                        field: fieldName,
                        value: value,
                        label: value
                    });
                    totalCount++;
                });
            } else if (filters.acf[fieldName]) {
                selectedItems.push({
                    type: 'acf',
                    field: fieldName,
                    value: filters.acf[fieldName],
                    label: filters.acf[fieldName]
                });
                totalCount++;
            }
        });
        
        // Count taxonomy filters
        Object.keys(filters.tax).forEach(taxonomy => {
            if (filters.tax[taxonomy]) {
                if (Array.isArray(filters.tax[taxonomy])) {
                    filters.tax[taxonomy].forEach(value => {
                        if (value && value.trim() !== '') {
                            selectedItems.push({
                                type: 'tax',
                                taxonomy: taxonomy,
                                value: value,
                                label: value
                            });
                            totalCount++;
                        }
                    });
                } else if (filters.tax[taxonomy] && filters.tax[taxonomy].trim() !== '') {
                    selectedItems.push({
                        type: 'tax',
                        taxonomy: taxonomy,
                        value: filters.tax[taxonomy],
                        label: filters.tax[taxonomy]
                    });
                    totalCount++;
                }
            }
        });
        
        // Count date filters
        if (filters.dateFrom || filters.dateTo) {
            let dateLabel = '';
            if (filters.dateFrom && filters.dateTo) {
                dateLabel = filters.dateFrom + ' - ' + filters.dateTo;
            } else if (filters.dateFrom) {
                dateLabel = 'From ' + filters.dateFrom;
            } else if (filters.dateTo) {
                dateLabel = 'To ' + filters.dateTo;
            }
            
            selectedItems.push({
                type: 'date',
                from: filters.dateFrom,
                to: filters.dateTo,
                label: dateLabel
            });
            totalCount++;
        }
        
        // Update count
        $selectedCount.text(totalCount);
        
        // Generate tags HTML
        let tagsHtml = '';
        selectedItems.forEach(item => {
            tagsHtml += '<span class="gpw-selected-tag" data-type="' + item.type + '"';
            if (item.field) tagsHtml += ' data-field="' + item.field + '"';
            if (item.taxonomy) tagsHtml += ' data-taxonomy="' + item.taxonomy + '"';
            if (item.value) tagsHtml += ' data-value="' + item.value + '"';
            tagsHtml += '>' + item.label + ' <span class="gpw-remove-tag">×</span></span>';
        });
        
        $selectedTags.html(tagsHtml);
        
        // Show/hide selected filters section
        if (totalCount > 0) {
            $selectedFilters.show();
        } else {
            $selectedFilters.hide();
        }
    }
    
    // Remove selected filter tag
    function removeSelectedFilter($wrapper, $tag) {
        let type = $tag.data('type');
        let field = $tag.data('field');
        let taxonomy = $tag.data('taxonomy');
        let value = $tag.data('value');
        
        if (type === 'acf' && field) {
            $wrapper.find('.gpw-acf-filter[data-field="' + field + '"]').each(function() {
                if ($(this).val() === value) {
                    $(this).prop('checked', false);
                }
            });
        } else if (type === 'tax' && taxonomy) {
            $wrapper.find('.gpw-tax-filter[data-taxonomy="' + taxonomy + '"]').each(function() {
                if ($(this).val() === value) {
                    $(this).prop('checked', false);
                }
            });
        } else if (type === 'date') {
            $wrapper.find('.gpw-date-filter-from').val('');
            $wrapper.find('.gpw-date-filter-to').val('');
        }
        
        updateSelectedFiltersDisplay($wrapper);
    }
    
    // Apply filters
    function applyFilters($wrapper) {
        pendingFilters = collectPendingFilters($wrapper);
        appliedFilters = JSON.parse(JSON.stringify(pendingFilters)); // Deep copy
        console.log('Applied filters:', appliedFilters);
        loadPosts($wrapper, 1);
    }
    
    // Reset all filters
    function resetFilters($wrapper) {
        // Uncheck all checkboxes
        $wrapper.find('.gpw-acf-filter[type="checkbox"]').prop('checked', false);
        $wrapper.find('.gpw-tax-filter[type="checkbox"]').prop('checked', false);
        
        // Uncheck radio buttons
        $wrapper.find('.gpw-acf-filter[type="radio"]').prop('checked', false);
        
        // Clear text inputs
        $wrapper.find('.gpw-acf-filter[type="text"]').val('');
        $wrapper.find('.gpw-acf-filter[type="date"]').val('');
        
        // Clear date inputs
        $wrapper.find('.gpw-date-filter-from').val('');
        $wrapper.find('.gpw-date-filter-to').val('');
        
        // Clear selects
        $wrapper.find('.gpw-acf-filter[type="select"]').prop('selectedIndex', 0);
        
        // Clear applied filters
        appliedFilters = {};
        pendingFilters = {};
        
        // Update display
        updateSelectedFiltersDisplay($wrapper);
        
        // Reload posts
        loadPosts($wrapper, 1);
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initialize widgets
    $('.gpw-wrapper').each(function() {
        let $wrapper = $(this);
        let settings = $wrapper.data('settings');
        
        // Debug: Log settings
        console.log('Widget settings:', settings);
        
        // Initialize filters
        pendingFilters = {};
        appliedFilters = {};
        
        // Initial load
        loadPosts($wrapper, 1);
        
        // Search handler with debounce
        if (settings.show_search === 'yes') {
            console.log('Setting up search handler');
            let $searchInput = $wrapper.find('.gpw-search');
            console.log('Search input found:', $searchInput.length > 0);
            
            if ($searchInput.length === 0) {
                console.error('Search input not found!');
                alert('Search input not found! Check if search is enabled in widget settings.');
            }
            
            let debouncedSearch = debounce(function() {
                // Update the search term in applied filters
                let searchTerm = $wrapper.find('.gpw-search').val();
                appliedFilters.search = searchTerm;
                console.log('Search term updated:', searchTerm);
                
                // Only search if there's a search term
                if (searchTerm && searchTerm.trim() !== '') {
                    loadPosts($wrapper, 1);
                } else {
                    // If search is empty, reset to show all posts
                    appliedFilters.search = '';
                    loadPosts($wrapper, 1);
                }
            }, 500);
            
            $wrapper.on('keyup', '.gpw-search', debouncedSearch);
            
            // Test search button
            $wrapper.on('click', '.gpw-test-search', function() {
                let searchTerm = $wrapper.find('.gpw-search').val() || 'test';
                $wrapper.find('.gpw-search').val(searchTerm);
                appliedFilters.search = searchTerm;
                console.log('Test search triggered with term:', searchTerm);
                loadPosts($wrapper, 1);
            });
        }
        
        // ACF filter handlers - update pending filters
        $wrapper.on('change', '.gpw-acf-filter', function() {
            updateSelectedFiltersDisplay($wrapper);
        });
        
        // Taxonomy filter handlers - update pending filters
        $wrapper.on('change', '.gpw-tax-filter', function() {
            updateSelectedFiltersDisplay($wrapper);
        });
        
        // Date filter handlers - update pending filters
        $wrapper.on('change', '.gpw-date-filter-from, .gpw-date-filter-to', function() {
            updateSelectedFiltersDisplay($wrapper);
        });
        
        // Remove selected filter tag
        $wrapper.on('click', '.gpw-remove-tag', function(e) {
            e.preventDefault();
            removeSelectedFilter($wrapper, $(this).closest('.gpw-selected-tag'));
        });
        
        // Confirm filters button
        $wrapper.on('click', '.gpw-confirm-filters', function(e) {
            e.preventDefault();
            applyFilters($wrapper);
        });
        
        // Reset filters button
        $wrapper.on('click', '.gpw-reset-filters', function(e) {
            e.preventDefault();
            resetFilters($wrapper);
        });
        
        // Pagination handlers
        $wrapper.on('click', '.gpw-page', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && page !== currentPage) {
                loadPosts($wrapper, page);
            }
        });
        
        // Load more handler
        $wrapper.on('click', '.gpw-load-more', function(e) {
            e.preventDefault();
            if (currentPage < maxPages && !loading) {
                loadPosts($wrapper, currentPage + 1, true);
            }
        });
        
        // Setup infinite scroll if enabled
        if (settings.show_pagination === 'yes' && settings.pagination_type === 'infinite') {
            setupInfiniteScroll($wrapper);
        }
        
        // Setup accordion toggle if using accordion layout
        if (settings.filters_layout === 'accordion') {
            setupAccordionToggle($wrapper);
        }
    });
    
    // Setup mutation observer for each wrapper
    function setupMutationObserver($wrapper) {
        if (!window.MutationObserver) return;
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && node.classList.contains('gpw-post')) {
                            setupInfiniteScroll($wrapper);
                        }
                    });
                }
            });
        });
        
        observer.observe($wrapper[0], {
            childList: true,
            subtree: true
        });
        
        // Store observer reference for cleanup
        $wrapper.data('mutationObserver', observer);
    }
    
    // Setup mutation observer for each wrapper
    $('.gpw-wrapper').each(function() {
        let $wrapper = $(this);
        setupMutationObserver($wrapper);
    });
    
    // Cleanup observers on page unload
    $(window).on('beforeunload', function() {
        $('.gpw-wrapper').each(function() {
            cleanupObservers($(this));
        });
    });
});
