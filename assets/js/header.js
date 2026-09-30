/**
 * Header, Mobile Side Drawer, and Real-Time Cart Synchronization
 */
document.addEventListener('DOMContentLoaded', function () {
    // 1. Dismiss Top Announcement Header Bar
    const topHeaderCloseBtn = document.querySelector('.site-top-header-close');
    if (topHeaderCloseBtn) {
        topHeaderCloseBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const topHeader = this.closest('.site-top-header');
            if (topHeader) {
                topHeader.style.transition = 'opacity 0.2s ease, max-height 0.25s ease, padding 0.25s ease';
                topHeader.style.overflow = 'hidden';
                topHeader.style.maxHeight = topHeader.offsetHeight + 'px';
                requestAnimationFrame(function () {
                    topHeader.style.opacity = '0';
                    topHeader.style.maxHeight = '0';
                    topHeader.style.paddingTop = '0';
                    topHeader.style.paddingBottom = '0';
                });
                setTimeout(function () {
                    topHeader.remove();
                }, 250);
            }
        });
    }

    // 2. Mobile Side Drawer Navigation
    const toggleBtn = document.querySelector('.agro-mobile-menu-toggle');
    const closeBtn = document.querySelector('.drawer-close');
    const drawer = document.getElementById('agro-side-drawer');
    const backdrop = document.querySelector('.agro-drawer-backdrop');

    if (drawer && toggleBtn) {
        function openDrawer() {
            drawer.classList.add('is-open');
            if (backdrop) backdrop.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            toggleBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('drawer-open');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            if (backdrop) backdrop.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            toggleBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('drawer-open');
        }

        toggleBtn.addEventListener('click', openDrawer);

        if (closeBtn) {
            closeBtn.addEventListener('click', closeDrawer);
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeDrawer);
        }

        // Close when pressing Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });

        // Automatically close drawer if resized above 1024px
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024 && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    }
});

/**
 * ==========================================================================
 * REAL-TIME HEADER CART SYNCHRONIZATION
 * Keeps header cart totals and item count instantly in sync across:
 * 1. WooCommerce Blocks Cart (Gutenberg) updates and deletions
 * 2. Classic cart AJAX events
 * 3. Store API / REST cart mutations
 * ==========================================================================
 */
(function () {
    let isRefreshing = false;
    let pendingRefresh = false;
    let previousCartHash = null;

    /**
     * Get configured WooCommerce AJAX fragment endpoint
     */
    function getEndpointUrl() {
        if (window.agroCartParams && window.agroCartParams.wcAjaxUrl) {
            return window.agroCartParams.wcAjaxUrl.replace('%%endpoint%%', 'get_refreshed_fragments');
        }
        if (window.wc_cart_fragments_params && window.wc_cart_fragments_params.wc_ajax_url) {
            return window.wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments');
        }
        return '/?wc-ajax=get_refreshed_fragments';
    }

    /**
     * Optimistically update header cart elements immediately from store data
     */
    function updateHeaderCartOptimistic(cartData) {
        if (!cartData) return;
        const cartContents = document.querySelectorAll('a.cart-contents');
        if (!cartContents.length) return;

        const count = typeof cartData.items_count !== 'undefined' ? parseInt(cartData.items_count, 10) : 0;
        const countText = count === 1 ? '1 item' : count + ' items';

        let formattedPrice = '';
        if (cartData.totals) {
            const symbol = cartData.totals.currency_symbol || (window.agroCartParams ? window.agroCartParams.currencySymbol : '₹');
            const minorUnit = typeof cartData.totals.currency_minor_unit !== 'undefined' ? cartData.totals.currency_minor_unit : 2;
            const rawTotal = parseInt(cartData.totals.total_items || cartData.totals.total_price || '0', 10);
            const num = (rawTotal / Math.pow(10, minorUnit)).toLocaleString('en-IN', {
                minimumFractionDigits: minorUnit,
                maximumFractionDigits: minorUnit
            });
            formattedPrice = `<span class="woocommerce-Price-currencySymbol">${symbol}</span>${num}`;
        }

        cartContents.forEach(function (cartEl) {
            const countEl = cartEl.querySelector('.count');
            if (countEl) {
                countEl.textContent = countText;
            }
            const amountEl = cartEl.querySelector('.amount');
            if (amountEl && formattedPrice) {
                amountEl.innerHTML = formattedPrice;
            }
        });

        // Also update handheld footer cart if present
        const footerCartContents = document.querySelectorAll('a.footer-cart-contents');
        footerCartContents.forEach(function (fEl) {
            const fCountEl = fEl.querySelector('.count');
            if (fCountEl) {
                fCountEl.textContent = count.toString();
            }
        });
    }

    /**
     * Fetch refreshed WooCommerce fragments and replace DOM elements
     */
    function refreshHeaderCart() {
        if (isRefreshing) {
            pendingRefresh = true;
            return;
        }
        isRefreshing = true;

        fetch(getEndpointUrl(), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Fragment response error: ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (data && data.fragments) {
                    Object.keys(data.fragments).forEach(function (selector) {
                        const elements = document.querySelectorAll(selector);
                        if (elements.length > 0) {
                            const temp = document.createElement('div');
                            temp.innerHTML = data.fragments[selector].trim();
                            const newEl = temp.firstElementChild;
                            if (newEl) {
                                elements.forEach(function (el) {
                                    if (el.parentNode) {
                                        el.parentNode.replaceChild(newEl.cloneNode(true), el);
                                    }
                                });
                            }
                        }
                    });

                    // Synchronize WooCommerce sessionStorage cache to prevent stale restoration
                    if (window.sessionStorage) {
                        try {
                            const hashKey = (window.wc_cart_fragments_params && window.wc_cart_fragments_params.cart_hash_key) || 'wc_cart_hash';
                            if (data.cart_hash) {
                                sessionStorage.setItem(hashKey, data.cart_hash);
                                sessionStorage.setItem('wc_fragments_' + hashKey, JSON.stringify(data.fragments));
                            } else {
                                sessionStorage.removeItem(hashKey);
                                sessionStorage.removeItem('wc_fragments_' + hashKey);
                            }
                        } catch (e) { }
                    }

                    if (window.jQuery) {
                        window.jQuery(document.body).trigger('wc_fragments_refreshed');
                    }
                }
            })
            .catch(function (err) {
                console.warn('AgroAura Cart Fragment Sync:', err);
            })
            .finally(function () {
                isRefreshing = false;
                if (pendingRefresh) {
                    pendingRefresh = false;
                    setTimeout(refreshHeaderCart, 200);
                }
            });
    }

    /**
     * Subscribe to Gutenberg WooCommerce Blocks Cart Store
     */
    function attachGutenbergCartStoreListener() {
        if (!window.wp || !window.wp.data || typeof window.wp.data.subscribe !== 'function') {
            return false;
        }

        const select = window.wp.data.select;
        if (!select || !select('wc/store/cart')) {
            return false;
        }

        try {
            window.wp.data.subscribe(function () {
                try {
                    const cartSelect = select('wc/store/cart');
                    if (!cartSelect) return;

                    const cartData = cartSelect.getCartData();
                    if (!cartData || !cartData.totals) return;

                    // Compute unique signature of current cart state
                    const currentHash = `${cartData.items_count}_${cartData.totals.total_items}_${cartData.totals.total_price}`;

                    if (previousCartHash === null) {
                        previousCartHash = currentHash;
                        return;
                    }

                    if (previousCartHash !== currentHash) {
                        previousCartHash = currentHash;
                        // Optimistically update header cart UI right away
                        updateHeaderCartOptimistic(cartData);
                        // Fetch server-rendered fragments
                        setTimeout(refreshHeaderCart, 100);
                    }
                } catch (e) { }
            });
            return true;
        } catch (e) {
            return false;
        }
    }

    // Try attaching store listener immediately and retry if Gutenberg scripts load deferred
    if (!attachGutenbergCartStoreListener()) {
        let attempts = 0;
        const interval = setInterval(function () {
            attempts++;
            if (attachGutenbergCartStoreListener() || attempts >= 25) {
                clearInterval(interval);
            }
        }, 200);
    }

    /**
     * Intercept Store API fetch requests (Gutenberg Cart Block operations)
     */
    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = function () {
            const args = arguments;
            const url = args[0] ? (typeof args[0] === 'string' ? args[0] : (args[0].url || '')) : '';
            return originalFetch.apply(this, args).then(function (response) {
                if (response && response.ok && typeof url === 'string') {
                    if (url.indexOf('/wp-json/wc/store/') !== -1 && url.indexOf('/cart') !== -1) {
                        const method = (args[1] && args[1].method) ? args[1].method.toUpperCase() : 'GET';
                        if (method !== 'GET') {
                            // Mutation request (remove, update qty, coupon) succeeded
                            setTimeout(refreshHeaderCart, 250);
                        }
                    }
                }
                return response;
            });
        };
    }

    /**
     * Intercept clicks on any remove buttons (Cart Block trash icons, classic remove links)
     */
    document.addEventListener('click', function (e) {
        const target = e.target;
        if (!target) return;
        const removeButton = target.closest(
            '.wc-block-cart-item__remove-link, .woocommerce-cart-form .product-remove a.remove, [data-wc-cart-item-remove], .cart-item-remove'
        );
        if (removeButton) {
            setTimeout(refreshHeaderCart, 500);
            setTimeout(refreshHeaderCart, 1500);
        }
    });

    /**
     * Classic WooCommerce jQuery Events
     */
    if (window.jQuery) {
        window.jQuery(document.body).on(
            'added_to_cart removed_from_cart updated_cart_totals updated_checkout wc_fragments_refreshed wc_fragments_ajax_filter wc_fragment_refresh',
            function () {
                refreshHeaderCart();
            }
        );
    }
})();
