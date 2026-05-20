(function () {
    'use strict';

    var storageKey = 'banoks_online_cart';

    function formatPeso(value) {
        return '\u20b1' + Number(value || 0).toFixed(2);
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function getCart() {
        try {
            return JSON.parse(window.localStorage.getItem(storageKey)) || {};
        } catch (error) {
            return {};
        }
    }

    function saveCart(cart) {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(cart));
        } catch (error) {
            return;
        }
        renderAllCarts();
    }

    function addToCart(product) {
        var cart = getCart();
        var id = String(product.id);
        if (!cart[id]) {
            cart[id] = {
                id: id,
                name: product.name,
                price: Number(product.price || 0),
                image: product.image || '',
                qty: 0
            };
        }
        cart[id].qty += Number(product.qty || 1);
        saveCart(cart);
    }

    function setCartQty(productId, quantity) {
        var cart = getCart();
        var id = String(productId);
        if (!cart[id]) {
            return;
        }
        if (quantity <= 0) {
            delete cart[id];
        } else {
            cart[id].qty = quantity;
        }
        saveCart(cart);
    }

    function getCartItems() {
        return Object.keys(getCart()).map(function (key) {
            return getCart()[key];
        }).filter(function (item) {
            return item && Number(item.qty) > 0;
        });
    }

    function getCartCount() {
        return getCartItems().reduce(function (sum, item) {
            return sum + Number(item.qty || 0);
        }, 0);
    }

    function renderCartBadges() {
        var count = getCartCount();
        document.querySelectorAll('.banoks-cart-count').forEach(function (badge) {
            badge.textContent = String(count);
            badge.classList.toggle('has-items', count > 0);
        });
    }

    function renderCheckout(form) {
        var list = form.querySelector('#banoks-checkout-cart-list');
        var hidden = form.querySelector('#banoks-checkout-hidden-items');
        var subtotalEl = form.querySelector('#banoks-subtotal');
        var feeEl = form.querySelector('#banoks-delivery-fee');
        var totalEl = form.querySelector('#banoks-total');
        var submitButton = form.querySelector('button[type="submit"]');
        var items = getCartItems();
        var subtotal = 0;

        if (list) {
            list.innerHTML = '';
        }
        if (hidden) {
            hidden.innerHTML = '';
        }

        if (!items.length && list) {
            list.innerHTML = '<p class="banoks-muted">Your cart is empty.</p>';
        }

        items.forEach(function (item) {
            var lineTotal = Number(item.price || 0) * Number(item.qty || 0);
            subtotal += lineTotal;

            if (list) {
                list.insertAdjacentHTML('beforeend',
                    '<div class="banoks-checkout-cart-item" data-product-id="' + escapeHtml(item.id) + '">' +
                        '<div class="banoks-checkout-cart-image">' + (item.image ? '<img src="' + escapeHtml(item.image) + '" alt="">' : '') + '</div>' +
                        '<div class="banoks-checkout-cart-info">' +
                            '<strong>' + escapeHtml(item.name) + '</strong>' +
                            '<span>' + formatPeso(item.price) + '</span>' +
                        '</div>' +
                        '<div class="banoks-checkout-cart-controls">' +
                            '<button type="button" class="banoks-checkout-qty" data-change="-1">-</button>' +
                            '<span>' + Number(item.qty || 0) + '</span>' +
                            '<button type="button" class="banoks-checkout-qty" data-change="1">+</button>' +
                        '</div>' +
                        '<strong class="banoks-checkout-line-total">' + formatPeso(lineTotal) + '</strong>' +
                    '</div>'
                );
            }

            if (hidden) {
                hidden.insertAdjacentHTML('beforeend', '<input type="hidden" name="items[' + escapeHtml(item.id) + ']" value="' + Number(item.qty || 0) + '">');
            }
        });

        var fulfillment = form.querySelector('#banoks-fulfillment-type');
        var isPickup = fulfillment && fulfillment.value === 'pickup';
        var area = form.querySelector('#banoks-delivery-area');
        var selected = area && area.options[area.selectedIndex] ? area.options[area.selectedIndex] : null;
        var fee = isPickup ? 0 : (selected ? parseFloat(selected.getAttribute('data-fee')) || 0 : 0);

        if (subtotalEl) {
            subtotalEl.textContent = formatPeso(subtotal);
        }
        if (feeEl) {
            feeEl.textContent = formatPeso(fee);
        }
        if (totalEl) {
            totalEl.textContent = formatPeso(subtotal + fee);
        }
        if (submitButton) {
            submitButton.disabled = form.getAttribute('data-can-order') !== '1' || !items.length;
        }
    }

    function renderAllCarts() {
        renderCartBadges();
        document.querySelectorAll('.banoks-online-checkout-form').forEach(renderCheckout);
    }

    if (new URLSearchParams(window.location.search).get('banoks_order_success') === '1') {
        try {
            window.localStorage.removeItem(storageKey);
        } catch (error) {}
    }

    document.querySelectorAll('[data-banoks-menu]').forEach(function (menu) {
        var activeProduct = null;
        var shell = menu.closest('.banoks-online-shell') || document;
        var modal = shell.querySelector('#banoks-cart-modal');
        var modalTitle = shell.querySelector('#banoks-cart-modal-title');
        var modalPrice = shell.querySelector('#banoks-cart-modal-price');
        var modalImage = shell.querySelector('#banoks-cart-modal-image');
        var modalQty = shell.querySelector('#banoks-cart-modal-qty');
        var modalAddonList = shell.querySelector('#banoks-cart-addon-list');
        var addonMap = window.banoksOnlineAddons || {};

        function openCartModal(button) {
            var productId = button.getAttribute('data-product-id');
            activeProduct = {
                id: productId,
                name: button.getAttribute('data-product-name') || 'Product',
                price: parseFloat(button.getAttribute('data-product-price')) || 0,
                image: button.getAttribute('data-product-image') || ''
            };
            var addons = addonMap[productId] || [];

            if (modalTitle) {
                modalTitle.textContent = activeProduct.name;
            }
            if (modalPrice) {
                modalPrice.textContent = formatPeso(activeProduct.price);
            }
            if (modalImage) {
                modalImage.innerHTML = activeProduct.image ? '<img src="' + escapeHtml(activeProduct.image) + '" alt="">' : '';
                modalImage.classList.toggle('has-image', !!activeProduct.image);
            }
            if (modalQty) {
                modalQty.value = '1';
            }
            if (modalAddonList) {
                if (!addons.length) {
                    modalAddonList.innerHTML = '<p class="banoks-cart-no-addons">No add-ons available for this item.</p>';
                } else {
                    modalAddonList.innerHTML = addons.map(function (addon) {
                        return '<label class="banoks-cart-addon-option">' +
                            '<input type="checkbox" data-addon-id="' + addon.id + '" data-addon-name="' + escapeHtml(addon.name) + '" data-addon-price="' + Number(addon.price || 0) + '" data-addon-image="' + escapeHtml(addon.image || '') + '">' +
                            '<span>' + escapeHtml(addon.name) + '<small>' + formatPeso(addon.price) + '</small></span>' +
                            '</label>';
                    }).join('');
                }
            }
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('is-open');
            }
        }

        function closeCartModal() {
            activeProduct = null;
            if (modal) {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-open');
            }
        }

        menu.querySelectorAll('.banoks-menu-category-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var category = button.getAttribute('data-category-filter') || 'all';

                menu.querySelectorAll('.banoks-menu-category-btn').forEach(function (filterButton) {
                    filterButton.classList.toggle('is-active', filterButton === button);
                });

                menu.querySelectorAll('.banoks-menu-item').forEach(function (item) {
                    item.hidden = category !== 'all' && item.getAttribute('data-category') !== category;
                });
            });
        });

        menu.querySelectorAll('.banoks-add-cart-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                openCartModal(button);
            });
        });

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal || event.target.classList.contains('banoks-cart-modal-close')) {
                    closeCartModal();
                }
            });
        }

        shell.querySelectorAll('.banoks-cart-qty-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!modalQty) {
                    return;
                }
                var value = parseInt(modalQty.value, 10) || 1;
                value = button.getAttribute('data-qty-action') === 'minus' ? Math.max(1, value - 1) : value + 1;
                modalQty.value = String(value);
            });
        });

        var confirmButton = shell.querySelector('#banoks-cart-confirm');
        if (confirmButton) {
            confirmButton.addEventListener('click', function () {
                var quantity = modalQty ? Math.max(1, parseInt(modalQty.value, 10) || 1) : 1;
                if (activeProduct) {
                    addToCart({
                        id: activeProduct.id,
                        name: activeProduct.name,
                        price: activeProduct.price,
                        image: activeProduct.image,
                        qty: quantity
                    });
                }
                if (modalAddonList) {
                    modalAddonList.querySelectorAll('input[type="checkbox"]:checked').forEach(function (addonInput) {
                        addToCart({
                            id: addonInput.getAttribute('data-addon-id'),
                            name: addonInput.getAttribute('data-addon-name'),
                            price: parseFloat(addonInput.getAttribute('data-addon-price')) || 0,
                            image: addonInput.getAttribute('data-addon-image') || '',
                            qty: quantity
                        });
                    });
                }
                closeCartModal();
            });
        }
    });

    document.querySelectorAll('.banoks-online-checkout-form').forEach(function (form) {
        function updateFulfillmentFields() {
            var fulfillment = form.querySelector('#banoks-fulfillment-type');
            var isPickup = fulfillment && fulfillment.value === 'pickup';
            var deliveryFields = form.querySelector('.banoks-delivery-checkout-fields');
            var area = form.querySelector('#banoks-delivery-area');
            var address = form.querySelector('textarea[name="delivery_address"]');
            var payment = form.querySelector('#banoks-payment-method');
            var isGcash = payment && payment.value === 'gcash';

            if (deliveryFields) {
                deliveryFields.style.display = isPickup ? 'none' : 'block';
            }
            if (area) {
                area.required = !isPickup;
                area.disabled = isPickup;
            }
            if (address) {
                address.required = !isPickup;
                address.disabled = isPickup;
            }
            if (payment) {
                Array.prototype.forEach.call(payment.options, function (option) {
                    var optionFulfillment = option.getAttribute('data-fulfillment');
                    option.hidden = !!optionFulfillment && optionFulfillment !== fulfillment.value;
                    option.disabled = !!optionFulfillment && optionFulfillment !== fulfillment.value;
                });

                if (payment.selectedOptions.length && payment.selectedOptions[0].disabled) {
                    payment.value = isPickup ? 'pay_at_pickup' : 'cod';
                }
            }
            form.querySelectorAll('.banoks-gcash-required').forEach(function (field) {
                field.required = !!isGcash;
                field.disabled = !isGcash;
            });
        }

        form.addEventListener('change', function () {
            updateFulfillmentFields();
            var payment = form.querySelector('#banoks-payment-method');
            var gcash = form.querySelector('.banoks-gcash-fields');
            if (payment && gcash) {
                gcash.style.display = payment.value === 'gcash' ? 'block' : 'none';
            }
            renderCheckout(form);
        });

        form.addEventListener('click', function (event) {
            var button = event.target.closest('.banoks-checkout-qty');
            if (!button) {
                return;
            }
            var item = button.closest('.banoks-checkout-cart-item');
            var productId = item ? item.getAttribute('data-product-id') : '';
            var cart = getCart();
            if (!productId || !cart[productId]) {
                return;
            }
            setCartQty(productId, Number(cart[productId].qty || 0) + Number(button.getAttribute('data-change') || 0));
        });

        form.addEventListener('submit', function () {
            renderCheckout(form);
        });

        updateFulfillmentFields();
        var payment = form.querySelector('#banoks-payment-method');
        var gcash = form.querySelector('.banoks-gcash-fields');
        if (payment && gcash) {
            gcash.style.display = payment.value === 'gcash' ? 'block' : 'none';
        }
        renderCheckout(form);
    });

    renderAllCarts();
})();
