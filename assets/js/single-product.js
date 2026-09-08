/**
 * Single Product Interactive Behaviors
 * Handles dynamic pack selection, variation form synchronization,
 * dynamic image offer badge updates, button price calculation,
 * and native WooCommerce gallery zoom activation.
 */
document.addEventListener('DOMContentLoaded', function () {
    const qtyInput = document.getElementById('agro-product-qty');
    const btnMinus = document.querySelector('.qty-btn.qty-minus');
    const btnPlus = document.querySelector('.qty-btn.qty-plus');
    const priceDisplay = document.getElementById('agro-btn-price-display');
    const packCards = document.querySelectorAll('.agro-pack-size-section .pack-card');
    const packInput = document.getElementById('agro-pack-size-input');
    const variationInput = document.getElementById('agro-variation-id');
    const attrInput = document.getElementById('agro-attr-input');
    const offerBadge = document.getElementById('agro-product-offer-badge');
    const buyNowBtn = document.getElementById('agro-buy-now-btn');
    const buyNowFlag = document.getElementById('agro-buy-now-flag');
    const cartForm = document.querySelector('form.cart');

    let currentPackPrice = 0;

    // Initialize currentPackPrice from selected card
    const initialSelectedCard = document.querySelector('.agro-pack-size-section .pack-card.is-selected');
    if (initialSelectedCard && initialSelectedCard.dataset.price) {
        currentPackPrice = parseFloat(initialSelectedCard.dataset.price);
    } else if (packCards.length > 0 && packCards[0].dataset.price) {
        currentPackPrice = parseFloat(packCards[0].dataset.price);
    }

    function updateTotalPrice() {
        if (!priceDisplay) return;
        const qty = qtyInput ? Math.max(1, parseInt(qtyInput.value, 10) || 1) : 1;
        const total = Math.round(currentPackPrice * qty);
        priceDisplay.textContent = '₹' + total.toLocaleString('en-IN');
    }

    // 1. Quantity Stepper
    if (btnMinus && qtyInput) {
        btnMinus.addEventListener('click', function (e) {
            e.preventDefault();
            let current = parseInt(qtyInput.value, 10) || 1;
            if (current > 1) {
                qtyInput.value = current - 1;
                updateTotalPrice();
            }
        });
    }

    if (btnPlus && qtyInput) {
        btnPlus.addEventListener('click', function (e) {
            e.preventDefault();
            let current = parseInt(qtyInput.value, 10) || 1;
            qtyInput.value = current + 1;
            updateTotalPrice();
        });
    }

    if (qtyInput) {
        qtyInput.addEventListener('input', function () {
            let val = parseInt(qtyInput.value, 10);
            if (isNaN(val) || val < 1) {
                qtyInput.value = 1;
            }
            updateTotalPrice();
        });
    }

    // 2. Pack Size Card Selection (Dynamic variations)
    if (packCards.length > 0) {
        packCards.forEach(function (card) {
            card.addEventListener('click', function () {
                packCards.forEach(function (c) {
                    c.classList.remove('is-selected');
                });
                card.classList.add('is-selected');

                // Update pack label in hidden input
                if (card.dataset.weight && packInput) {
                    packInput.value = card.dataset.weight;
                }

                // Update WooCommerce variation_id input
                if (card.dataset.variationId && variationInput) {
                    variationInput.value = card.dataset.variationId;
                }

                // Update attribute input
                if (card.dataset.attrVal && attrInput) {
                    attrInput.value = card.dataset.attrVal;
                }

                // Update pack price & button total
                if (card.dataset.price) {
                    currentPackPrice = parseFloat(card.dataset.price);
                    updateTotalPrice();
                }

                // Update Offer Badge directly on the product image
                if (offerBadge) {
                    const discount = parseInt(card.dataset.discount, 10);
                    if (!isNaN(discount) && discount > 0) {
                        offerBadge.textContent = discount + '% OFF';
                        offerBadge.style.display = 'inline-block';
                    } else {
                        offerBadge.style.display = 'none';
                    }
                }
            });
        });
    }

    // 3. 1-Click Buy Action
    if (buyNowBtn && cartForm) {
        buyNowBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (buyNowFlag) {
                buyNowFlag.value = '1';
            }
            // Trigger standard form submit
            cartForm.submit();
        });
    }

    // Initial calculation
    updateTotalPrice();

    // 4. Product Gallery Hover Zoom Initialization
    function initGalleryZoom() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.zoom === 'function') {
            jQuery('.woocommerce-product-gallery__image').each(function () {
                const $wrapper = jQuery(this);
                if (!$wrapper.data('zoom-initialized') && !$wrapper.find('.zoomImg').length) {
                    const $img = $wrapper.find('img');
                    const largeUrl = $img.attr('data-large_image') || $img.attr('src');
                    if (largeUrl) {
                        $wrapper.zoom({
                            url: largeUrl,
                            touch: false
                        });
                        $wrapper.data('zoom-initialized', true);
                    }
                }
            });
        }
    }

    // Run zoom init immediately and on hover fallback
    initGalleryZoom();
    setTimeout(initGalleryZoom, 300);
    setTimeout(initGalleryZoom, 1000);

    const galleryWrapper = document.querySelector('.woocommerce-product-gallery__wrapper');
    if (galleryWrapper) {
        galleryWrapper.addEventListener('mouseenter', initGalleryZoom);
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(window).on('load', initGalleryZoom);
        jQuery(document).on('woocommerce_gallery_after_init', initGalleryZoom);
    }
});

