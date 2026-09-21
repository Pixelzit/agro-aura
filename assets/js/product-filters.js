/**
 * Agro Aura Product Filters & Refine Shelf JavaScript
 * Handles dynamic AJAX filtering for categories, price ranges, pack sizes, and sorting.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('agro-shop-sidebar');
        var mainContent = document.querySelector('.agro-shop-main-content');
        var productsGrid = mainContent ? mainContent.querySelector('ul.products') : null;
        var resultCountEl = mainContent ? mainContent.querySelector('.shop-result-count') : null;
        var activeFiltersWrap = mainContent ? mainContent.querySelector('.agro-active-filters') : null;
        var orderbySelect = mainContent ? mainContent.querySelector('.agro-catalog-ordering select.orderby') : null;

        // Configuration from localized script or dataset
        var ajaxConfig = window.agroFilterData || {};
        var ajaxUrl = ajaxConfig.ajaxUrl || (sidebar ? sidebar.getAttribute('data-ajax-url') : '/wp-admin/admin-ajax.php');
        var nonce = ajaxConfig.nonce || (sidebar ? sidebar.getAttribute('data-nonce') : '');
        var shopUrl = ajaxConfig.shopUrl || (sidebar ? sidebar.getAttribute('data-shop-url') : '/shop/');

        // Filter & Pagination state
        var activeCategory = (sidebar && sidebar.getAttribute('data-current-cat')) ? sidebar.getAttribute('data-current-cat') : 'all';
        var selectedPriceRanges = [];
        var selectedPackSize = '';
        var currentOrderby = orderbySelect ? orderbySelect.value : 'menu_order';
        var currentPage = (mainContent && mainContent.dataset.currentPage) ? parseInt(mainContent.dataset.currentPage, 10) : 1;
        var maxPages = (mainContent && mainContent.dataset.maxPages) ? parseInt(mainContent.dataset.maxPages, 10) : 1;
        var isLoadingMore = false;
        var infiniteObserver = null;

        var infiniteStatus = document.getElementById('agro-infinite-scroll-status');
        var infiniteLoader = infiniteStatus ? infiniteStatus.querySelector('.agro-infinite-loader') : null;
        var infiniteEnd = infiniteStatus ? infiniteStatus.querySelector('.agro-infinite-end') : null;

        var activeController = null;

        // Initialize state from existing markup
        function syncInitialState() {
            if (!sidebar) return;

            // 1. Initial Category
            var activeCatLink = sidebar.querySelector('.category-filter-list li.is-active a');
            if (activeCatLink && activeCatLink.dataset.catSlug) {
                activeCategory = activeCatLink.dataset.catSlug;
            }

            // 2. Initial Price Ranges
            selectedPriceRanges = [];
            sidebar.querySelectorAll('.filter-checkbox-list input[type="checkbox"]:checked').forEach(function (cb) {
                selectedPriceRanges.push(cb.value);
            });

            // 3. Initial Pack Size
            var activePackBtn = sidebar.querySelector('.pack-size-filter-grid .pack-pill-btn.is-active');
            if (activePackBtn && activePackBtn.dataset.pack) {
                selectedPackSize = activePackBtn.dataset.pack;
            } else {
                selectedPackSize = '';
            }

            // 4. Initial Orderby
            if (orderbySelect) {
                currentOrderby = orderbySelect.value;
            }

            // 5. Initial Page from URL or dataset
            if (mainContent && mainContent.dataset.currentPage) {
                currentPage = parseInt(mainContent.dataset.currentPage, 10) || 1;
            } else {
                var urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('paged')) {
                    currentPage = parseInt(urlParams.get('paged'), 10) || 1;
                } else if (urlParams.has('product-page')) {
                    currentPage = parseInt(urlParams.get('product-page'), 10) || 1;
                } else {
                    var pathMatch = window.location.pathname.match(/page\/([0-9]+)/i);
                    if (pathMatch && pathMatch[1]) {
                        currentPage = parseInt(pathMatch[1], 10) || 1;
                    } else {
                        currentPage = 1;
                    }
                }
            }

            if (mainContent && mainContent.dataset.maxPages) {
                maxPages = parseInt(mainContent.dataset.maxPages, 10) || 1;
            }
        }

        syncInitialState();

        // Infinite Scroll: Load Next Page on Scroll
        function loadNextPage() {
            if (isLoadingMore || currentPage >= maxPages || !productsGrid) {
                return;
            }

            var nextPage = currentPage + 1;
            isLoadingMore = true;

            if (infiniteLoader) {
                infiniteLoader.style.display = 'inline-flex';
            }
            if (infiniteEnd) {
                infiniteEnd.style.display = 'none';
            }

            var formData = new FormData();
            formData.append('action', 'agro_filter_products');
            formData.append('nonce', nonce);
            formData.append('category', activeCategory);
            formData.append('pack_size', selectedPackSize);
            formData.append('orderby', currentOrderby);
            formData.append('paged', nextPage);

            for (var i = 0; i < selectedPriceRanges.length; i++) {
                formData.append('price_ranges[]', selectedPriceRanges[i]);
            }

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    isLoadingMore = false;
                    if (infiniteLoader) {
                        infiniteLoader.style.display = 'none';
                    }

                    if (result && result.success && result.data) {
                        var data = result.data;
                        currentPage = parseInt(data.current_page, 10) || nextPage;
                        maxPages = parseInt(data.max_pages, 10) || maxPages;

                        // Append newly fetched products to current grid
                        if (data.html) {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data.html;
                            var items = tempDiv.querySelectorAll('li.product, .agro-product-card-item');

                            if (items.length > 0) {
                                items.forEach(function (item) {
                                    productsGrid.appendChild(item);
                                });
                            } else if (!tempDiv.querySelector('.agro-no-products-found')) {
                                productsGrid.insertAdjacentHTML('beforeend', data.html);
                            }
                        }

                        // Update Result Count text smoothly: "Showing 1–X of Y fresh products"
                        if (resultCountEl && data.found_posts) {
                            var perPage = (mainContent && mainContent.dataset.perPage) ? parseInt(mainContent.dataset.perPage, 10) : 12;
                            var end = Math.min(currentPage * perPage, data.found_posts);
                            resultCountEl.innerHTML = 'Showing <strong>1–' + end + '</strong> of <strong>' + data.found_posts + '</strong> fresh products';
                        }

                        // Update URL silently in browser history without page jump
                        if (data.url) {
                            window.history.replaceState({ agroFilter: true, paged: currentPage }, '', data.url);
                        }

                        // Check if all pages are loaded
                        if (currentPage >= maxPages) {
                            if (infiniteEnd) {
                                infiniteEnd.style.display = 'inline-flex';
                            }
                            if (infiniteObserver && infiniteStatus) {
                                infiniteObserver.unobserve(infiniteStatus);
                            }
                        }
                    }
                })
                .catch(function (err) {
                    isLoadingMore = false;
                    if (infiniteLoader) {
                        infiniteLoader.style.display = 'none';
                    }
                    console.error('Agro Aura infinite scroll error:', err);
                });
        }

        // Setup Sentinel Observer for Infinite Scroll
        function observeSentinel() {
            if (!infiniteStatus) {
                infiniteStatus = document.getElementById('agro-infinite-scroll-status');
                if (infiniteStatus) {
                    infiniteLoader = infiniteStatus.querySelector('.agro-infinite-loader');
                    infiniteEnd = infiniteStatus.querySelector('.agro-infinite-end');
                }
            }
            if (!infiniteStatus) return;

            if (infiniteObserver) {
                infiniteObserver.disconnect();
            }

            if (currentPage >= maxPages) {
                if (infiniteEnd && maxPages > 0) {
                    infiniteEnd.style.display = 'inline-flex';
                }
                return;
            }

            if ('IntersectionObserver' in window) {
                infiniteObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            if (!isLoadingMore && currentPage < maxPages) {
                                loadNextPage();
                            }
                        }
                    });
                }, {
                    root: null,
                    rootMargin: '400px 0px',
                    threshold: 0.01
                });

                infiniteObserver.observe(infiniteStatus);
            } else {
                // Fallback scroll listener
                window.addEventListener('scroll', function () {
                    if (isLoadingMore || currentPage >= maxPages || !infiniteStatus) return;
                    var rect = infiniteStatus.getBoundingClientRect();
                    if (rect.top <= window.innerHeight + 400) {
                        loadNextPage();
                    }
                }, { passive: true });
            }
        }

        observeSentinel();

        // Core AJAX Filter Function (resets grid to page 1 with new filter params)
        function executeFilter(updateUrl, page) {
            if (!mainContent || !productsGrid) {
                return;
            }

            if (typeof page !== 'undefined' && page !== null) {
                currentPage = parseInt(page, 10) || 1;
            } else {
                currentPage = 1;
            }

            // Abort previous pending request
            if (activeController) {
                activeController.abort();
            }
            activeController = new AbortController();

            // Set loading state
            mainContent.classList.add('is-loading');

            var formData = new FormData();
            formData.append('action', 'agro_filter_products');
            formData.append('nonce', nonce);
            formData.append('category', activeCategory);
            formData.append('pack_size', selectedPackSize);
            formData.append('orderby', currentOrderby);
            formData.append('paged', currentPage);

            for (var i = 0; i < selectedPriceRanges.length; i++) {
                formData.append('price_ranges[]', selectedPriceRanges[i]);
            }

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                signal: activeController.signal
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    mainContent.classList.remove('is-loading');

                    if (result && result.success && result.data) {
                        var data = result.data;

                        // 1. Reset Products Grid with new page 1 results
                        productsGrid.innerHTML = data.html;

                        // 2. Update Result Count Text
                        if (resultCountEl && data.result_count_text) {
                            resultCountEl.innerHTML = data.result_count_text;
                        }

                        // 3. Update state
                        currentPage = parseInt(data.current_page, 10) || 1;
                        maxPages = parseInt(data.max_pages, 10) || 1;

                        // 4. Reset Infinite Scroll Status
                        if (infiniteLoader) {
                            infiniteLoader.style.display = 'none';
                        }
                        if (infiniteEnd) {
                            if (maxPages <= 1 && data.found_posts > 0) {
                                infiniteEnd.style.display = 'inline-flex';
                            } else {
                                infiniteEnd.style.display = 'none';
                            }
                        }

                        // Reconnect observer for infinite scrolling on new query
                        observeSentinel();

                        // 5. Update Active Chips
                        renderActiveChips(data.chips || []);

                        // 6. Update Price Counts if returned
                        if (data.price_counts) {
                            updatePriceCountBadges(data.price_counts);
                        }

                        // 7. Update Pack Counts if returned
                        if (data.pack_counts) {
                            updatePackCountBadges(data.pack_counts);
                        }

                        // 8. Update URL in browser history without reload
                        if (updateUrl !== false && data.url) {
                            window.history.pushState({ agroFilter: true, paged: currentPage }, '', data.url);
                        }
                    }
                })
                .catch(function (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Agro Aura filter error:', err);
                        mainContent.classList.remove('is-loading');
                    }
                });
        }

        // Render Active Chips in Top Bar
        function renderActiveChips(chips) {
            if (!activeFiltersWrap) return;

            activeFiltersWrap.innerHTML = '';
            if (!chips || chips.length === 0) return;

            chips.forEach(function (chip) {
                var chipEl = document.createElement('span');
                chipEl.className = 'filter-chip';
                chipEl.setAttribute('data-type', chip.type);
                chipEl.setAttribute('data-key', chip.key);

                var textSpan = document.createElement('span');
                textSpan.textContent = chip.label;

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-chip-btn';
                removeBtn.setAttribute('aria-label', 'Remove filter');
                removeBtn.innerHTML = '&times;';

                chipEl.appendChild(textSpan);
                chipEl.appendChild(removeBtn);
                activeFiltersWrap.appendChild(chipEl);
            });
        }

        // Update Dynamic Price Count Badges
        function updatePriceCountBadges(priceCounts) {
            if (!sidebar) return;
            for (var key in priceCounts) {
                if (priceCounts.hasOwnProperty(key)) {
                    var cb = sidebar.querySelector('.filter-checkbox-list input[value="' + key + '"]');
                    if (cb) {
                        var countEl = cb.closest('label').querySelector('.item-count');
                        if (countEl) {
                            countEl.textContent = priceCounts[key];
                        }
                    }
                }
            }
        }

        // Update Dynamic Pack Count Badges / Pills
        function updatePackCountBadges(packCounts) {
            if (!sidebar) return;
            for (var pack in packCounts) {
                if (packCounts.hasOwnProperty(pack)) {
                    var btn = sidebar.querySelector('.pack-size-filter-grid .pack-pill-btn[data-pack="' + pack + '"]');
                    if (btn) {
                        btn.setAttribute('data-count', packCounts[pack]);
                    }
                }
            }
        }

        // 0. Accordion Toggle for .filter-group on screens <= 1024px
        function initFilterAccordions() {
            if (!sidebar) return;
            if (window.innerWidth <= 1024) {
                var groups = sidebar.querySelectorAll('.filter-group');
                groups.forEach(function (group, idx) {
                    if (group.dataset.userToggled === 'true') {
                        return;
                    }
                    var hasActive = group.querySelector('.is-active, input[type="checkbox"]:checked, .pack-pill-btn.is-active');
                    // if (hasActive || idx === 0) {
                    //     group.classList.add('is-open');
                    // } else {
                    //     group.classList.remove('is-open');
                    // }
                });
            } else {
                sidebar.querySelectorAll('.filter-group').forEach(function (group) {
                    group.classList.remove('is-open');
                });
            }
        }

        initFilterAccordions();

        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                var heading = e.target.closest('.filter-group .filter-heading');
                if (!heading) return;

                if (window.innerWidth <= 1024) {
                    e.preventDefault();
                    var group = heading.closest('.filter-group');
                    if (group) {
                        group.classList.toggle('is-open');
                        group.dataset.userToggled = 'true';
                    }
                }
            });
        }

        window.addEventListener('resize', function () {
            initFilterAccordions();
        });

        // 1. Sidebar Category Filter Click Handling
        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                var catLink = e.target.closest('.category-filter-list a');
                if (!catLink) return;

                e.preventDefault();
                var slug = catLink.dataset.catSlug || 'all';

                // Update active state in sidebar UI
                sidebar.querySelectorAll('.category-filter-list li').forEach(function (li) {
                    li.classList.remove('is-active');
                });
                catLink.closest('li').classList.add('is-active');

                activeCategory = slug;
                executeFilter(true, 1);
            });
        }

        // 2. Price Range Checkbox Change Handling
        if (sidebar) {
            sidebar.addEventListener('change', function (e) {
                if (e.target.matches('.filter-checkbox-list input[type="checkbox"]')) {
                    selectedPriceRanges = [];
                    sidebar.querySelectorAll('.filter-checkbox-list input[type="checkbox"]:checked').forEach(function (cb) {
                        selectedPriceRanges.push(cb.value);
                    });
                    executeFilter(true, 1);
                }
            });
        }

        // 3. Pack Size Filter Pills Click Handling
        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                var pill = e.target.closest('.pack-size-filter-grid .pack-pill-btn');
                if (!pill) return;

                e.preventDefault();
                var pack = pill.dataset.pack || pill.textContent.trim();

                if (pill.classList.contains('is-active')) {
                    // Toggle off if already selected
                    pill.classList.remove('is-active');
                    selectedPackSize = '';
                } else {
                    // Select this pack size
                    sidebar.querySelectorAll('.pack-size-filter-grid .pack-pill-btn').forEach(function (p) {
                        p.classList.remove('is-active');
                    });
                    pill.classList.add('is-active');
                    selectedPackSize = pack;
                }

                executeFilter(true, 1);
            });
        }

        // 4. Catalog Ordering Dropdown Change
        if (orderbySelect) {
            orderbySelect.addEventListener('change', function () {
                currentOrderby = this.value;
                executeFilter(true, 1);
            });
            // Intercept form submission if inside a form
            var orderForm = orderbySelect.closest('form');
            if (orderForm) {
                orderForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    currentOrderby = orderbySelect.value;
                    executeFilter(true, 1);
                });
            }
        }

        // 5. Active Filter Chip Removal Click Handling
        if (activeFiltersWrap) {
            activeFiltersWrap.addEventListener('click', function (e) {
                var removeBtn = e.target.closest('.remove-chip-btn');
                if (!removeBtn) return;

                e.preventDefault();
                var chip = removeBtn.closest('.filter-chip');
                if (!chip) return;

                var type = chip.getAttribute('data-type');
                var key = chip.getAttribute('data-key');

                if (type === 'pack') {
                    selectedPackSize = '';
                    if (sidebar) {
                        sidebar.querySelectorAll('.pack-size-filter-grid .pack-pill-btn').forEach(function (p) {
                            p.classList.remove('is-active');
                        });
                    }
                } else if (type === 'price') {
                    selectedPriceRanges = selectedPriceRanges.filter(function (k) { return k !== key; });
                    if (sidebar) {
                        var cb = sidebar.querySelector('.filter-checkbox-list input[value="' + key + '"]');
                        if (cb) cb.checked = false;
                    }
                } else if (type === 'cat') {
                    activeCategory = 'all';
                    if (sidebar) {
                        sidebar.querySelectorAll('.category-filter-list li').forEach(function (li) {
                            li.classList.remove('is-active');
                        });
                        var allLi = sidebar.querySelector('.category-filter-list li:first-child');
                        if (allLi) allLi.classList.add('is-active');
                    }
                }

                executeFilter(true, 1);
            });
        }

        // 6. Reset All / Clear All Filters Handling
        document.addEventListener('click', function (e) {
            var resetBtn = e.target.closest('.refine-shelf-header .reset-all-btn, .agro-clear-filters-btn');
            if (!resetBtn) return;

            e.preventDefault();

            // Reset state
            activeCategory = 'all';
            selectedPriceRanges = [];
            selectedPackSize = '';

            // Reset sidebar checkboxes and pills
            if (sidebar) {
                sidebar.querySelectorAll('.filter-checkbox-list input[type="checkbox"]').forEach(function (cb) {
                    cb.checked = false;
                });
                sidebar.querySelectorAll('.pack-size-filter-grid .pack-pill-btn').forEach(function (p) {
                    p.classList.remove('is-active');
                });
                sidebar.querySelectorAll('.category-filter-list li').forEach(function (li) {
                    li.classList.remove('is-active');
                });
                var allLi = sidebar.querySelector('.category-filter-list li:first-child');
                if (allLi) allLi.classList.add('is-active');
            }

            // Clear chips
            if (activeFiltersWrap) {
                activeFiltersWrap.innerHTML = '';
            }

            executeFilter(true, 1);
        });

        // 7. Product Card Variation Pack Pill clicks (Event delegation for cards everywhere)
        document.addEventListener('click', function (e) {
            var pill = e.target.closest('.card-pack-pills .pack-pill');
            if (!pill) return;
            e.preventDefault();

            var card = pill.closest('.agro-product-card-item, li.product');
            if (!card) return;

            // Toggle selected state
            card.querySelectorAll('.card-pack-pills .pack-pill').forEach(function (p) {
                p.classList.remove('is-selected');
            });
            pill.classList.add('is-selected');

            // Extract variation / pack data
            var price = pill.dataset.price;
            var regular = pill.dataset.regular;
            var save = pill.dataset.save;
            var discount = pill.dataset.discount;
            var variationId = pill.dataset.variationId;
            var packName = pill.dataset.pack || pill.textContent.trim();

            // 1. Update Current Price
            var currentPriceEl = card.querySelector('.current-price');
            if (currentPriceEl && price) {
                currentPriceEl.textContent = '₹' + parseInt(price, 10).toLocaleString('en-IN');
            }

            // 2. Update Regular Price
            var regularPriceEl = card.querySelector('.regular-price');
            if (regularPriceEl) {
                if (regular && parseInt(regular, 10) > parseInt(price, 10)) {
                    regularPriceEl.textContent = '₹' + parseInt(regular, 10).toLocaleString('en-IN');
                    regularPriceEl.style.display = 'inline';
                } else {
                    regularPriceEl.style.display = 'none';
                }
            }

            // 3. Update Savings Text
            var savingsEl = card.querySelector('.savings-text');
            if (savingsEl) {
                if (save && parseInt(save, 10) > 0) {
                    savingsEl.textContent = 'Save ₹' + parseInt(save, 10).toLocaleString('en-IN');
                    savingsEl.style.display = 'block';
                } else {
                    savingsEl.style.display = 'none';
                }
            }

            // 4. Update Discount Badge on Image
            var discountBadge = card.querySelector('.badge-discount');
            if (discountBadge) {
                if (discount && parseInt(discount, 10) > 0) {
                    discountBadge.textContent = discount + '% OFF';
                    discountBadge.style.display = '';
                } else {
                    discountBadge.style.display = 'none';
                }
            }

            // 5. Update Add to Cart Button target
            var addBtn = card.querySelector('.add_to_cart_button');
            if (addBtn) {
                if (variationId) {
                    addBtn.dataset.productId = variationId;
                    addBtn.setAttribute('data-product_id', variationId);
                    addBtn.href = '?add-to-cart=' + variationId;
                }
                addBtn.dataset.pack = packName;
                addBtn.setAttribute('data-pack', packName);
            }
        });

        // 8. Dynamic AJAX Pagination Click Handling
        document.addEventListener('click', function (e) {
            var pageLink = e.target.closest('#agro-shop-pagination a.page-numbers, .agro-shop-main-content .woocommerce-pagination a.page-numbers');
            if (!pageLink) return;

            e.preventDefault();

            var href = pageLink.getAttribute('href') || '';
            var targetPage = 1;

            var pagedMatch = href.match(/[?&](?:paged|product-page)=([0-9]+)/i) || href.match(/page\/([0-9]+)/i);
            if (pagedMatch && pagedMatch[1]) {
                targetPage = parseInt(pagedMatch[1], 10);
            } else {
                var text = pageLink.textContent.trim();
                if (/^\d+$/.test(text)) {
                    targetPage = parseInt(text, 10);
                } else if (pageLink.classList.contains('next')) {
                    targetPage = currentPage + 1;
                } else if (pageLink.classList.contains('prev')) {
                    targetPage = Math.max(1, currentPage - 1);
                }
            }

            if (targetPage && targetPage !== currentPage) {
                executeFilter(true, targetPage);

                // Smooth scroll to top of products grid
                if (mainContent) {
                    var targetOffset = mainContent.getBoundingClientRect().top + window.pageYOffset - 90;
                    window.scrollTo({
                        top: Math.max(0, targetOffset),
                        behavior: 'smooth'
                    });
                }
            }
        });

        // 9. Browser Back / Forward Button Handling
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.agroFilter) {
                syncInitialState();
                executeFilter(false, currentPage);
            }
        });
    });
})();
