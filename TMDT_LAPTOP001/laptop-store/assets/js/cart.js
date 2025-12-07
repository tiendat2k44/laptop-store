/**
 * Cart JavaScript - LaptopStore
 * Xử lý tất cả chức năng liên quan đến giỏ hàng
 *
 * Lưu ý về endpoint:
 * - Bạn có thể cấu hình global JS biến window.CART_ACTION_URL để ghi đè URL mặc định
 * - Mặc định sẽ thử '/laptop-store/pages/cart-actions.php', '/laptopstore/pages/cart-actions.php', '/laptop-store/api/cart.php'
 */

(function () {
    'use strict';

    // CONFIG: nếu muốn thay đổi endpoint, set window.CART_ACTION_URL trước khi file này chạy
    const FALLBACK_ENDPOINTS = [
        '/laptop-store/pages/cart-actions.php',
        '/laptopstore/pages/cart-actions.php',
        '/laptop-store/api/cart.php',
        '/laptopstore/api/cart.php',
        '/laptop-store/pages/cart.php',
        '/laptopstore/pages/cart.php'
    ];

    function getCartActionUrl() {
        if (window.CART_ACTION_URL) return window.CART_ACTION_URL;
        // return first fallback; change if your app uses different path
        return FALLBACK_ENDPOINTS[0];
    }

    document.addEventListener('DOMContentLoaded', function() {
        initCartPage();
        initCheckoutButtons();
        initCartActions();
    });

    /**
     * Initialize cart page
     */
    function initCartPage() {
        // Update cart on page load
        updateCartSummary();

        // Initialize quantity controls
        initCartQuantityControls();

        // Initialize remove buttons
        initRemoveButtons();

        // Initialize cart coupon
        initCartCoupon();

        // Initialize shipping calculator
        initShippingCalculator();
    }

    /**
     * Initialize cart quantity controls
     */
    function initCartQuantityControls() {
        document.querySelectorAll('.cart-quantity-control').forEach(control => {
            const minusBtn = control.querySelector('.quantity-minus');
            const plusBtn = control.querySelector('.quantity-plus');
            const input = control.querySelector('.quantity-input');

            // we store item id/stock on control dataset for convenience
            const itemId = control.dataset.itemId;
            const maxStock = parseInt(control.dataset.maxStock || '99', 10);

            if (minusBtn && input) {
                minusBtn.addEventListener('click', function() {
                    let value = parseInt(input.value, 10) || 1;
                    if (value > 1) {
                        input.value = value - 1;
                        updateCartItemQuantity(itemId, input.value);
                    }
                });
            }

            if (plusBtn && input) {
                plusBtn.addEventListener('click', function() {
                    let value = parseInt(input.value, 10) || 1;
                    if (value < maxStock) {
                        input.value = value + 1;
                        updateCartItemQuantity(itemId, input.value);
                    } else {
                        showNotification('warning', 'Đã đạt số lượng tồn kho tối đa');
                    }
                });
            }

            if (input) {
                input.dataset.maxStock = maxStock;
                input.addEventListener('change', function() {
                    let value = parseInt(this.value, 10) || 1;

                    if (value < 1) value = 1;
                    if (value > maxStock) value = maxStock;

                    this.value = value;
                    updateCartItemQuantity(itemId, value);
                });

                // Prevent entering non-numeric values
                input.addEventListener('keypress', function(e) {
                    if (e.key < '0' || e.key > '9') {
                        e.preventDefault();
                    }
                });
            }
        });
    }

    /**
     * Update cart item quantity via AJAX
     */
    function updateCartItemQuantity(itemId, quantity) {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'update_quantity');
        body.append('item_id', itemId);
        body.append('quantity', quantity);

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();

            if (data && data.success) {
                // Update item total (if returned)
                const itemTotalEl = document.querySelector(`.item-total[data-item="${itemId}"]`);
                if (itemTotalEl && data.item_total !== undefined) {
                    itemTotalEl.textContent = formatPrice(data.item_total);
                }

                // Update cart summary
                updateCartSummaryData(data);

                showNotification('success', 'Đã cập nhật số lượng');
            } else {
                showNotification('error', (data && data.message) ? data.message : 'Lỗi cập nhật giỏ hàng');
                // Reload page to sync data
                setTimeout(() => location.reload(), 800);
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Lỗi kết nối server');
            console.error(err);
        });
    }

    /**
     * Initialize remove buttons
     */
    function initRemoveButtons() {
        document.querySelectorAll('.remove-cart-item').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.dataset.itemId;
                const productName = this.dataset.productName || '';

                if (confirm(`Bạn có chắc muốn xóa "${productName}" khỏi giỏ hàng?`)) {
                    removeCartItemAction(itemId);
                }
            });
        });
    }

    /**
     * Remove item from cart via AJAX
     */
    function removeCartItemAction(itemId) {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'remove_item');
        body.append('item_id', itemId);

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();

            if (data && data.success) {
                // Remove item from DOM
                const itemElement = document.querySelector(`.cart-item[data-item="${itemId}"]`);
                if (itemElement) {
                    itemElement.remove();
                }

                // Update cart summary
                updateCartSummaryData(data);

                // Check if cart is empty
                if ((data.cart_count || 0) === 0) {
                    showEmptyCartMessage();
                }

                showNotification('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
            } else {
                showNotification('error', (data && data.message) ? data.message : 'Lỗi khi xóa sản phẩm');
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Lỗi kết nối server');
            console.error(err);
        });
    }

    /**
     * Show empty cart message
     */
    function showEmptyCartMessage() {
        const cartItemsContainer = document.querySelector('.cart-items-container') || document.querySelector('.cart-items');
        if (cartItemsContainer) {
            cartItemsContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="empty-cart-icon mb-4">
                        <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                    </div>
                    <h3 class="mb-3">Giỏ hàng của bạn đang trống</h3>
                    <p class="text-muted mb-4">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
                    <a href="${getProductsUrl()}" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Tiếp tục mua sắm
                    </a>
                </div>
            `;
        }

        // Hide checkout button and coupon form
        document.querySelectorAll('.checkout-section, .coupon-section').forEach(el => {
            el.style.display = 'none';
        });
    }

    function getProductsUrl() {
        // Common product listing path
        return '/laptop-store/pages/products.php';
    }

    /**
     * Initialize cart coupon
     */
    function initCartCoupon() {
        const couponForm = document.getElementById('coupon-form');
        if (!couponForm) return;

        couponForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const couponCodeEl = document.getElementById('coupon-code');
            const couponCode = couponCodeEl ? couponCodeEl.value.trim() : '';
            if (!couponCode) {
                showNotification('warning', 'Vui lòng nhập mã giảm giá');
                return;
            }

            applyCoupon(couponCode);
        });
    }

    /**
     * Apply coupon via AJAX
     */
    function applyCoupon(couponCode) {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'apply_coupon');
        body.append('coupon_code', couponCode);

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();

            if (data && data.success) {
                // Update cart summary with discount
                updateCartSummaryData(data);

                // Show discount details
                const discountSection = document.querySelector('.discount-section');
                if (discountSection) {
                    discountSection.innerHTML = `
                        <div class="alert alert-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-tag me-2"></i>
                                    <strong>Mã giảm giá: ${escapeHtml(couponCode)}</strong>
                                    <div class="small">Giảm ${formatPrice(data.discount_amount || 0)}</div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger remove-coupon-btn">
                                    <i class="fas fa-times"></i> Xóa
                                </button>
                            </div>
                        </div>
                    `;

                    // Add event listener to remove coupon button
                    const btn = discountSection.querySelector('.remove-coupon-btn');
                    if (btn) btn.addEventListener('click', removeCoupon);
                }

                showNotification('success', 'Áp dụng mã giảm giá thành công');
            } else {
                showNotification('error', (data && data.message) ? data.message : 'Mã giảm giá không hợp lệ');
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Lỗi kết nối server');
            console.error(err);
        });
    }

    /**
     * Remove coupon
     */
    function removeCoupon() {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'remove_coupon');

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();

            if (data && data.success) {
                // Update cart summary
                updateCartSummaryData(data);

                // Remove discount section
                const discountSection = document.querySelector('.discount-section');
                if (discountSection) {
                    discountSection.innerHTML = '';
                }

                showNotification('success', 'Đã xóa mã giảm giá');
            } else {
                showNotification('error', (data && data.message) ? data.message : 'Lỗi khi xóa mã giảm giá');
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Lỗi kết nối server');
            console.error(err);
        });
    }

    /**
     * Initialize shipping calculator
     */
    function initShippingCalculator() {
        const shippingForm = document.getElementById('shipping-calculator');
        if (!shippingForm) return;

        shippingForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const cityEl = document.getElementById('shipping-city');
            const districtEl = document.getElementById('shipping-district');
            const city = cityEl ? cityEl.value : '';
            const district = districtEl ? districtEl.value : '';

            if (!city || !district) {
                showNotification('warning', 'Vui lòng chọn thành phố và quận/huyện');
                return;
            }

            calculateShipping(city, district);
        });
    }

    /**
     * Calculate shipping via AJAX
     */
    function calculateShipping(city, district) {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'calculate_shipping');
        body.append('city', city);
        body.append('district', district);

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();

            if (data && data.success) {
                // Update shipping fee in cart summary
                updateCartSummaryData(data);

                // Show shipping method options
                const shippingMethods = document.getElementById('shipping-methods');
                if (shippingMethods && Array.isArray(data.shipping_methods)) {
                    shippingMethods.innerHTML = '';

                    data.shipping_methods.forEach(method => {
                        const methodEl = document.createElement('div');
                        methodEl.className = 'form-check mb-2';
                        methodEl.innerHTML = `
                            <input class="form-check-input" type="radio" 
                                   name="shipping_method" 
                                   id="shipping-${escapeHtml(method.id)}" 
                                   value="${escapeHtml(method.id)}"
                                   ${method.recommended ? 'checked' : ''}>
                            <label class="form-check-label" for="shipping-${escapeHtml(method.id)}">
                                <div class="d-flex justify-content-between">
                                    <span>${escapeHtml(method.name)}</span>
                                    <span class="fw-bold">${formatPrice(method.cost)}</span>
                                </div>
                                <small class="text-muted">${escapeHtml(method.description || '')}</small>
                            </label>
                        `;
                        shippingMethods.appendChild(methodEl);
                    });

                    // Add event listeners to shipping method radios
                    document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            selectShippingMethod(this.value);
                        });
                    });
                }

                showNotification('success', 'Đã tính phí vận chuyển');
            } else {
                showNotification('error', (data && data.message) ? data.message : 'Lỗi tính phí vận chuyển');
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Lỗi kết nối server');
            console.error(err);
        });
    }

    /**
     * Select shipping method
     */
    function selectShippingMethod(methodId) {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'select_shipping');
        body.append('method_id', methodId);

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();
            if (data && data.success) {
                updateCartSummaryData(data);
            }
        })
        .catch(err => {
            hideLoading();
            console.error(err);
        });
    }

    /**
     * Update cart summary with data from server
     */
    function updateCartSummaryData(data) {
        // Update subtotal
        const subtotalEl = document.querySelector('.cart-subtotal');
        if (subtotalEl && data.subtotal !== undefined) {
            subtotalEl.textContent = formatPrice(data.subtotal);
        }

        // Update discount
        const discountEl = document.querySelector('.cart-discount');
        if (discountEl && data.discount_amount !== undefined) {
            discountEl.textContent = `-${formatPrice(data.discount_amount)}`;
            const row = discountEl.closest('.discount-row');
            if (row) row.style.display = data.discount_amount > 0 ? 'flex' : 'none';
        }

        // Update shipping
        const shippingEl = document.querySelector('.cart-shipping');
        if (shippingEl && data.shipping_fee !== undefined) {
            shippingEl.textContent = formatPrice(data.shipping_fee);
        }

        // Update tax
        const taxEl = document.querySelector('.cart-tax');
        if (taxEl && data.tax_amount !== undefined) {
            taxEl.textContent = formatPrice(data.tax_amount);
        }

        // Update total
        const totalEls = document.querySelectorAll('.cart-total-amount');
        if (totalEls.length > 0 && data.total_amount !== undefined) {
            totalEls.forEach(el => {
                el.textContent = formatPrice(data.total_amount);
            });
        }

        // Update cart count
        const cartCountEl = document.querySelectorAll('.cart-count');
        if (cartCountEl.length > 0 && data.cart_count !== undefined) {
            cartCountEl.forEach(el => {
                el.textContent = data.cart_count;
                el.style.display = data.cart_count > 0 ? 'flex' : 'none';
            });
        }
    }

    /**
     * Update cart summary via AJAX
     */
    function updateCartSummary() {
        const url = getCartActionUrl() + '?action=get_summary';
        fetch(url, { credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    updateCartSummaryData(data);
                }
            })
            .catch(error => {
                console.error('Error updating cart summary:', error);
            });
    }

    /**
     * Initialize checkout buttons
     */
    function initCheckoutButtons() {
        const checkoutBtns = document.querySelectorAll('.btn-checkout');
        checkoutBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Validate cart before checkout
                if (!validateCartBeforeCheckout()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state and redirect to checkout
                e.preventDefault();
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';

                // For safety, refresh summary then redirect
                updateCartSummary();

                setTimeout(() => {
                    // Prefer explicit checkout path if set
                    const checkoutUrl = window.CHECKOUT_URL || '/laptop-store/pages/checkout.php';
                    window.location.href = checkoutUrl;
                }, 600);
            });
        });
    }

    /**
     * Validate cart before checkout
     * - ensure cart not empty
     * - optionally can validate stock via server
     */
    function validateCartBeforeCheckout() {
        // Try to read cart_count element
        const cartCountEl = document.querySelector('.cart-count');
        const count = cartCountEl ? parseInt(cartCountEl.textContent, 10) || 0 : null;

        if (count === 0) {
            showNotification('warning', 'Giỏ hàng của bạn đang trống');
            return false;
        }

        // If cart count unknown, ensure at least one .cart-item exists
        if (count === null) {
            const anyItem = document.querySelector('.cart-item');
            if (!anyItem) {
                showNotification('warning', 'Giỏ hàng của bạn đang trống');
                return false;
            }
        }

        // Optionally, call server to validate stock before redirecting to checkout
        // For now we assume OK
        return true;
    }

    /**
     * Initialize general cart actions (for forms, links)
     */
    function initCartActions() {
        // Add to cart buttons
        document.querySelectorAll('[data-action="add-to-cart"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const qty = parseInt(this.dataset.qty || '1', 10) || 1;
                addToCartAction(productId, qty);
            });
        });

        // Update quantity input change already handled in initCartQuantityControls
    }

    /**
     * Add to cart via AJAX
     */
    function addToCartAction(productId, quantity) {
        showLoading();

        const body = new URLSearchParams();
        body.append('action', 'add');
        body.append('product_id', productId);
        body.append('quantity', quantity);

        postToCartAction(getCartActionUrl(), body.toString())
        .then(data => {
            hideLoading();
            if (data && data.success) {
                updateCartSummaryData(data);
                showNotification('success', 'Đã thêm sản phẩm vào giỏ hàng');
            } else {
                showNotification('error', (data && data.message) ? data.message : 'Thêm vào giỏ hàng thất bại');
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Lỗi kết nối server');
            console.error(err);
        });
    }

    /**
     * Helper: POST to cart action endpoint and parse JSON
     */
    function postToCartAction(url, bodyString) {
        // If default endpoint not correct for your app, set window.CART_ACTION_URL before load
        const targetUrl = window.CART_ACTION_URL || url;
        return fetch(targetUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: bodyString,
            credentials: 'same-origin'
        })
        .then(resp => {
            if (!resp.ok) {
                return resp.text().then(text => {
                    throw new Error('HTTP ' + resp.status + ': ' + text);
                });
            }
            return resp.json();
        });
    }

    /**
     * UI Helpers: show/hide loading overlay
     */
    function showLoading() {
        if (document.getElementById('ls-loading-overlay')) return;
        const overlay = document.createElement('div');
        overlay.id = 'ls-loading-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.right = 0;
        overlay.style.bottom = 0;
        overlay.style.background = 'rgba(0,0,0,0.25)';
        overlay.style.zIndex = 99999;
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.innerHTML = `<div class="spinner-border text-light" role="status" aria-hidden="true"></div>`;
        document.body.appendChild(overlay);
    }

    function hideLoading() {
        const overlay = document.getElementById('ls-loading-overlay');
        if (overlay) overlay.remove();
    }

    /**
     * showNotification - transient toasts on top-right
     * type: 'success' | 'error' | 'warning' | 'info'
     */
    function showNotification(type, message, timeout = 3500) {
        const wrapId = 'ls-notification-wrap';
        let wrap = document.getElementById(wrapId);
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = wrapId;
            wrap.style.position = 'fixed';
            wrap.style.top = '20px';
            wrap.style.right = '20px';
            wrap.style.zIndex = 100000;
            wrap.style.maxWidth = '360px';
            document.body.appendChild(wrap);
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${mapTypeToBootstrap(type)} shadow-sm`;
        alert.style.marginBottom = '8px';
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.2s ease';
        alert.innerHTML = `<div style="display:flex;align-items:center;gap:10px;">
            <div style="flex:1">${escapeHtml(message)}</div>
            <button type="button" class="btn-close" aria-label="Close"></button>
        </div>`;

        wrap.appendChild(alert);
        // fade in
        requestAnimationFrame(() => alert.style.opacity = '1');

        // close button
        alert.querySelector('.btn-close').addEventListener('click', () => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 200);
        });

        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 200);
        }, timeout);
    }

    function mapTypeToBootstrap(type) {
        switch (type) {
            case 'success': return 'success';
            case 'error': return 'danger';
            case 'warning': return 'warning';
            default: return 'info';
        }
    }

    /**
     * Utility: escape HTML to prevent injection
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /**
     * Utility: formatPrice (assumes integer amount in VND)
     */
    function formatPrice(amount) {
        if (amount === null || amount === undefined) return '0₫';
        const num = Number(amount) || 0;
        // use Intl.NumberFormat if available for thousand separators
        try {
            return new Intl.NumberFormat('vi-VN').format(num) + '₫';
        } catch (e) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '₫';
        }
    }

    // Expose some functions for debugging if needed
    window.LaptopStoreCart = {
        updateCartItemQuantity,
        removeCartItemAction,
        addToCartAction,
        updateCartSummary,
        showNotification,
        formatPrice
    };

})();