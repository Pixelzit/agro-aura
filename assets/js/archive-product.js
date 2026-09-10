/**
 * Archive / Shop Page Interactions
 * Filter pills, active chips, checkboxes, and card pack toggles.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Product Card Pack Pill clicks (Event delegation for all cards everywhere)
    document.addEventListener('click', function (e) {
        const pill = e.target.closest('.card-pack-pills .pack-pill');
        if (!pill) return;
        e.preventDefault();

        const card = pill.closest('.agro-product-card-item, li.product');
        if (!card) return;

        // Toggle selected state
        card.querySelectorAll('.card-pack-pills .pack-pill').forEach(function (p) {
            p.classList.remove('is-selected');
        });
        pill.classList.add('is-selected');

        // Extract variation / pack data
        const price = pill.dataset.price;
        const regular = pill.dataset.regular;
        const save = pill.dataset.save;
        const discount = pill.dataset.discount;
        const variationId = pill.dataset.variationId;
        const packName = pill.dataset.pack || pill.textContent.trim();

        // 1. Update Current Price
        const currentPriceEl = card.querySelector('.current-price');
        if (currentPriceEl && price) {
            currentPriceEl.textContent = '₹' + parseInt(price, 10).toLocaleString('en-IN');
        }

        // 2. Update Regular Price
        const regularPriceEl = card.querySelector('.regular-price');
        if (regularPriceEl) {
            if (regular && parseInt(regular, 10) > parseInt(price, 10)) {
                regularPriceEl.textContent = '₹' + parseInt(regular, 10).toLocaleString('en-IN');
                regularPriceEl.style.display = 'inline';
            } else {
                regularPriceEl.style.display = 'none';
            }
        }

        // 3. Update Savings Text
        const savingsEl = card.querySelector('.savings-text');
        if (savingsEl) {
            if (save && parseInt(save, 10) > 0) {
                savingsEl.textContent = 'Save ₹' + parseInt(save, 10).toLocaleString('en-IN');
                savingsEl.style.display = 'block';
            } else {
                savingsEl.style.display = 'none';
            }
        }

        // 4. Update Discount Badge on Image
        const discountBadge = card.querySelector('.badge-discount');
        if (discountBadge) {
            if (discount && parseInt(discount, 10) > 0) {
                discountBadge.textContent = discount + '% OFF';
                discountBadge.style.display = '';
            } else {
                discountBadge.style.display = 'none';
            }
        }

        // 5. Update Add to Cart Button target
        const addBtn = card.querySelector('.add_to_cart_button');
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
});

