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
        
        // Use applied filters
        let acfFilters = appliedFilters.acf || {};
        let taxFilters = appliedFilters.tax || {};
        let dateFrom = appliedFilters.dateFrom || '';
        let dateTo = appliedFilters.dateTo || '';
        
        // Debug logging
        console.log('Loading posts with filters:', { search, acfFilters, taxFilters, dateFrom, dateTo });
        
        // Show loading state
        if (!append) {
            $wrapper.find('.gpw-posts-grid').html('<div class="gpw-loading">Loading posts...</div>');
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
                    
                    // Update field counts in accordion headers
                    updateFieldCounts($wrapper);
                } else {
                    if (!append) {
                        $wrapper.find('.gpw-posts-grid').html('<div class="gpw-error">Error: ' + (res.data || 'Unknown error') + '</div>');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
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
            $resultsCount.html('No posts found');
        }
    }
    
    function updateFieldCounts($wrapper) {
        $wrapper.find('.gpw-filters-accordion').each(function() {
            let $accordion = $(this);
            let $header = $accordion.find('.gpw-accordion-header h3');
            let $checkboxes = $accordion.find('input[type="checkbox"]:checked');
            let count = $checkboxes.length;
            
            let headerText = $header.text().replace(/\s*\d+$/, ''); // Remove existing count
            if (count > 0) {
                $header.html(headerText + ' <span class="gpw-field-count">' + count + '</span>');
            } else {
                $header.text(headerText);
            }
        });
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
                $toggle.text('▼');
            } else {
                $content.slideDown(300);
                $toggle.text('▲');
            }
        });
    }
    
    // Collect pending filters from DOM
    function collectPendingFilters($wrapper) {
        let filters = {
            acf: {},
            tax: {},
            dateFrom: '',
            dateTo: '',
            search: ''
        };
        
        // Collect search term
        filters.search = $wrapper.find('.gpw-search').val() || '';
        
        // Collect ACF filters
        $wrapper.find('.gpw-acf-filter').each(function() {
            let $field = $(this);
            let fieldName = $field.data('field');
            let fieldType = $field.attr('type') || 'select';
            
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
            let fieldType = $field.attr('type') || 'select';
            
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
        filters.dateFrom = $wrapper.find('.gpw-date-filter-from').val() || '';
        filters.dateTo = $wrapper.find('.gpw-date-filter-to').val() || '';
        
        console.log('Collected pending filters:', filters);
        return filters;
    }
    
    // Update selected filters display
    function updateSelectedFiltersDisplay($wrapper) {
        let filters = collectPendingFilters($wrapper);
        let $selectedFilters = $wrapper.find('.gpw-selected-filters');
        let $selectedTags = $wrapper.find('.gpw-selected-filters-tags');
        let $selectedCount = $wrapper.find('.gpw-selected-count');
        let $actionButtons = $wrapper.find('.gpw-filter-actions');
        
        let selectedItems = [];
        let totalCount = 0;
        
        // Count search
        if (filters.search && filters.search.trim() !== '') {
            selectedItems.push({
                type: 'search',
                value: filters.search,
                label: 'Search: "' + filters.search + '"'
            });
            totalCount++;
        }
        
        // Count ACF filters
        Object.keys(filters.acf).forEach(fieldName => {
            if (Array.isArray(filters.acf[fieldName])) {
                filters.acf[fieldName].forEach(value => {
                    if (value && value.trim() !== '') {
                        selectedItems.push({
                            type: 'acf',
                            field: fieldName,
                            value: value,
                            label: fieldName + ': ' + value
                        });
                        totalCount++;
                    }
                });
            } else if (filters.acf[fieldName] && filters.acf[fieldName].trim() !== '') {
                selectedItems.push({
                    type: 'acf',
                    field: fieldName,
                    value: filters.acf[fieldName],
                    label: fieldName + ': ' + filters.acf[fieldName]
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
                            // Get term name for display
                            let $option = $wrapper.find('.gpw-tax-filter[data-taxonomy="' + taxonomy + '"][value="' + value + '"]');
                            let termName = $option.closest('label').text().trim() || value;
                            selectedItems.push({
                                type: 'tax',
                                taxonomy: taxonomy,
                                value: value,
                                label: taxonomy + ': ' + termName
                            });
                            totalCount++;
                        }
                    });
                } else if (filters.tax[taxonomy] && filters.tax[taxonomy].trim() !== '') {
                    let $option = $wrapper.find('.gpw-tax-filter[data-taxonomy="' + taxonomy + '"][value="' + filters.tax[taxonomy] + '"]');
                    let termName = $option.find('option:selected').text() || filters.tax[taxonomy];
                    selectedItems.push({
                        type: 'tax',
                        taxonomy: taxonomy,
                        value: filters.tax[taxonomy],
                        label: taxonomy + ': ' + termName
                    });
                    totalCount++;
                }
            }
        });
        
        // Count date filters
        if (filters.dateFrom || filters.dateTo) {
            let dateLabel = '';
            if (filters.dateFrom && filters.dateTo) {
                dateLabel = 'Date: ' + filters.dateFrom + ' - ' + filters.dateTo;
            } else if (filters.dateFrom) {
                dateLabel = 'Date: From ' + filters.dateFrom;
            } else if (filters.dateTo) {
                dateLabel = 'Date: To ' + filters.dateTo;
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
        
        // Show/hide selected filters section and action buttons
        if (totalCount > 0) {
            $selectedFilters.show();
            $actionButtons.show();
        } else {
            $selectedFilters.hide();
            $actionButtons.hide();
        }
        
        // Update field counts in accordion headers
        updateFieldCounts($wrapper);
    }
    
    // Remove selected filter tag
    function removeSelectedFilter($wrapper, $tag) {
        let type = $tag.data('type');
        let field = $tag.data('field');
        let taxonomy = $tag.data('taxonomy');
        let value = $tag.data('value');
        
        if (type === 'search') {
            $wrapper.find('.gpw-search').val('');
        } else if (type === 'acf' && field) {
            $wrapper.find('.gpw-acf-filter[data-field="' + field + '"]').each(function() {
                if ($(this).val() === value) {
                    if ($(this).attr('type') === 'checkbox' || $(this).attr('type') === 'radio') {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                }
            });
        } else if (type === 'tax' && taxonomy) {
            $wrapper.find('.gpw-tax-filter[data-taxonomy="' + taxonomy + '"]').each(function() {
                if ($(this).val() === value) {
                    if ($(this).attr('type') === 'checkbox') {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
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
        console.log('Applying filters:', appliedFilters);
        loadPosts($wrapper, 1);
        
        // Hide action buttons after applying
        $wrapper.find('.gpw-filter-actions').hide();
    }
    
    // Reset all filters
    function resetFilters($wrapper) {
        // Clear search
        $wrapper.find('.gpw-search').val('');
        
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
        $wrapper.find('.gpw-acf-filter').each(function() {
            if ($(this).is('select')) {
                $(this).prop('selectedIndex', 0);
            }
        });
        $wrapper.find('.gpw-tax-filter').each(function() {
            if ($(this).is('select')) {
                $(this).prop('selectedIndex', 0);
            }
        });
        
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
        
        console.log('Initializing widget with settings:', settings);
        
        // Initialize filters
        pendingFilters = {};
        appliedFilters = {};
        
        // Initial load
        loadPosts($wrapper, 1);
        
        // Search handler with immediate search
        if (settings.show_search === 'yes') {
            let debouncedSearch = debounce(function() {
                updateSelectedFiltersDisplay($wrapper);
            }, 300);
            
            $wrapper.on('input keyup', '.gpw-search', debouncedSearch);
        }
        
        // ACF filter handlers
        $wrapper.on('change', '.gpw-acf-filter', function() {
            updateSelectedFiltersDisplay($wrapper);
        });
        
        // Taxonomy filter handlers
        $wrapper.on('change', '.gpw-tax-filter', function() {
            updateSelectedFiltersDisplay($wrapper);
        });
        
        // Date filter handlers
        $wrapper.on('change', '.gpw-date-filter-from, .gpw-date-filter-to', function() {
            updateSelectedFiltersDisplay($wrapper);
        });
        
        // Remove selected filter tag
        $wrapper.on('click', '.gpw-remove-tag', function(e) {
            e.preventDefault();
            e.stopPropagation();
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
        
        // Setup accordion toggle
        setupAccordionToggle($wrapper);
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