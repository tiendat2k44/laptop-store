/**
 * Main JavaScript File - LaptopStore
 * Tất cả các hàm chung cho toàn website
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('LaptopStore JS loaded');
    
    // Initialize all components
    initCartFunctions();
    initProductFilters();
    initFormValidations();
    initMobileMenu();
    initNotifications();
});

/**
 * Initialize cart-related functions
 */
function initCartFunctions() {
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            addToCart(productId, productName);
        });
    });
    
    // Cart quantity controls
    document.querySelectorAll('.quantity-control').forEach(control => {
        const minusBtn = control.querySelector('.quantity-minus');
        const plusBtn = control.querySelector('.quantity-plus');
        const input = control.querySelector('.quantity-input');
        
        if (minusBtn) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || 1;
                if (value > 1) {
                    input.value = value - 1;
                    updateCartItem(this.dataset.itemId, input.value);
                }
            });
        }
        
        if (plusBtn) {
            plusBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || 1;
                const maxStock = parseInt(this.dataset.maxStock) || 99;
                if (value < maxStock) {
                    input.value = value + 1;
                    updateCartItem(this.dataset.itemId, input.value);
                }
            });
        }
        
        if (input) {
            input.addEventListener('change', function() {
                let value = parseInt(this.value) || 1;
                const maxStock = parseInt(this.dataset.maxStock) || 99;
                
                if (value < 1) value = 1;
                if (value > maxStock) value = maxStock;
                
                this.value = value;
                updateCartItem(this.dataset.itemId, value);
            });
        }
    });
}

/**
 * Add product to cart via AJAX
 */
function addToCart(productId, productName) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('action', 'add_to_cart');
    
    fetch('/laptopstore/pages/cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', `Đã thêm "${productName}" vào giỏ hàng`);
            updateCartCount(data.cart_count);
            updateCartTotal(data.cart_total);
        } else {
            showNotification('error', data.message || 'Lỗi khi thêm vào giỏ hàng');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Lỗi kết nối server');
    });
}

/**
 * Update cart item quantity via AJAX
 */
function updateCartItem(itemId, quantity) {
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('quantity', quantity);
    formData.append('action', 'update_cart');
    
    fetch('/laptopstore/pages/cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartTotal(data.cart_total);
            updateCartCount(data.cart_count);
            
            // Update specific item total if element exists
            const itemTotalEl = document.querySelector(`.item-total[data-item="${itemId}"]`);
            if (itemTotalEl && data.item_total) {
                itemTotalEl.textContent = formatPrice(data.item_total);
            }
        } else {
            showNotification('error', data.message || 'Lỗi cập nhật giỏ hàng');
        }
    });
}

/**
 * Remove item from cart
 */
function removeCartItem(itemId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
    
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('action', 'remove_from_cart');
    
    fetch('/laptopstore/pages/cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
            document.querySelector(`.cart-item[data-item="${itemId}"]`).remove();
            updateCartTotal(data.cart_total);
            updateCartCount(data.cart_count);
            
            // If cart is empty, show empty message
            if (data.cart_count === 0) {
                document.querySelector('.cart-items').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>Giỏ hàng trống</h5>
                        <p class="text-muted">Hãy thêm sản phẩm vào giỏ hàng của bạn</p>
                        <a href="/laptopstore/pages/products.php" class="btn btn-primary">
                            Tiếp tục mua sắm
                        </a>
                    </div>
                `;
            }
        } else {
            showNotification('error', data.message || 'Lỗi khi xóa sản phẩm');
        }
    });
}

/**
 * Update cart count in navbar
 */
function updateCartCount(count) {
    const cartCountEl = document.querySelector('.cart-count');
    if (cartCountEl) {
        cartCountEl.textContent = count;
        if (count > 0) {
            cartCountEl.style.display = 'flex';
        } else {
            cartCountEl.style.display = 'none';
        }
    }
}

/**
 * Update cart total
 */
function updateCartTotal(total) {
    const totalElements = document.querySelectorAll('.cart-total, .cart-total-amount');
    totalElements.forEach(el => {
        el.textContent = formatPrice(total);
    });
}

/**
 * Format price with VND currency
 */
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0
    }).format(price);
}

/**
 * Initialize product filters
 */
function initProductFilters() {
    const filterForm = document.querySelector('#product-filters');
    if (!filterForm) return;
    
    // Price range slider
    const priceSlider = document.getElementById('price-range');
    const priceValue = document.getElementById('price-value');
    
    if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = formatPrice(this.value * 1000000);
        });
    }
    
    // Brand filter checkboxes
    document.querySelectorAll('.brand-filter').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Sort select
    const sortSelect = document.getElementById('sort-by');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            filterForm.submit();
        });
    }
}

/**
 * Initialize form validations
 */
function initFormValidations() {
    // Registration form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!validateRegistrationForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Checkout form (detailed validation in checkout.js)
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (!validateCheckoutForm()) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Validate registration form
 */
function validateRegistrationForm() {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const fullName = document.getElementById('full_name');
    
    let isValid = true;
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
        showFieldError(email, 'Email không hợp lệ');
        isValid = false;
    } else {
        clearFieldError(email);
    }
    
    // Password validation
    if (password.value.length < 6) {
        showFieldError(password, 'Mật khẩu phải có ít nhất 6 ký tự');
        isValid = false;
    } else {
        clearFieldError(password);
    }
    
    // Confirm password
    if (password.value !== confirmPassword.value) {
        showFieldError(confirmPassword, 'Mật khẩu xác nhận không khớp');
        isValid = false;
    } else {
        clearFieldError(confirmPassword);
    }
    
    // Full name validation
    if (fullName.value.trim().length < 2) {
        showFieldError(fullName, 'Họ tên phải có ít nhất 2 ký tự');
        isValid = false;
    } else {
        clearFieldError(fullName);
    }
    
    return isValid;
}

/**
 * Validate login form
 */
function validateLoginForm() {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    
    let isValid = true;
    
    // Email validation
    if (!email.value.trim()) {
        showFieldError(email, 'Vui lòng nhập email');
        isValid = false;
    } else {
        clearFieldError(email);
    }
    
    // Password validation
    if (!password.value.trim()) {
        showFieldError(password, 'Vui lòng nhập mật khẩu');
        isValid = false;
    } else {
        clearFieldError(password);
    }
    
    return isValid;
}

/**
 * Show field error
 */
function showFieldError(field, message) {
    const parent = field.closest('.form-group') || field.closest('.mb-3');
    let errorEl = parent.querySelector('.invalid-feedback');
    
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'invalid-feedback';
        parent.appendChild(errorEl);
    }
    
    errorEl.textContent = message;
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
}

/**
 * Clear field error
 */
function clearFieldError(field) {
    const parent = field.closest('.form-group') || field.closest('.mb-3');
    const errorEl = parent.querySelector('.invalid-feedback');
    
    if (errorEl) {
        errorEl.remove();
    }
    
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
}

/**
 * Initialize mobile menu
 */
function initMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('show');
            document.body.classList.toggle('menu-open');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mobileMenu.classList.remove('show');
                document.body.classList.remove('menu-open');
            }
        });
    }
}

/**
 * Show notification
 */
function showNotification(type, message) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto hide after 5 seconds
    const autoHide = setTimeout(() => {
        hideNotification(notification);
    }, 5000);
    
    // Close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', function() {
        clearTimeout(autoHide);
        hideNotification(notification);
    });
    
    // Click to dismiss
    notification.addEventListener('click', function(e) {
        if (e.target === notification) {
            clearTimeout(autoHide);
            hideNotification(notification);
        }
    });
}

/**
 * Hide notification
 */
function hideNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 300);
}

/**
 * Initialize notifications system
 */
function initNotifications() {
    // Check for flash messages from PHP
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(alert => {
        const type = alert.classList.contains('alert-success') ? 'success' : 
                    alert.classList.contains('alert-danger') ? 'error' :
                    alert.classList.contains('alert-warning') ? 'warning' : 'info';
        
        const message = alert.textContent.trim();
        
        // Convert Bootstrap alerts to notifications
        if (message && !alert.classList.contains('no-convert')) {
            showNotification(type, message);
            setTimeout(() => {
                alert.remove();
            }, 100);
        }
    });
}

/**
 * AJAX search products
 */
function initSearch() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    
    if (!searchInput || !searchResults) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        const query = this.value.trim();
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`/laptopstore/pages/search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.products && data.products.length > 0) {
                        let html = '<div class="search-results-list">';
                        data.products.forEach(product => {
                            html += `
                                <a href="/laptopstore/pages/product-detail.php?id=${product.id}" class="search-result-item">
                                    <img src="/laptopstore/assets/images/products/${product.image_url || 'default.jpg'}" alt="${product.name}">
                                    <div>
                                        <h6>${product.name}</h6>
                                        <p class="price">${formatPrice(product.price)}</p>
                                    </div>
                                </a>
                            `;
                        });
                        html += '</div>';
                        searchResults.innerHTML = html;
                        searchResults.classList.add('show');
                    } else {
                        searchResults.innerHTML = '<div class="search-no-results">Không tìm thấy sản phẩm</div>';
                        searchResults.classList.add('show');
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        }, 300);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('show');
        }
    });
    
    // Handle search form submit
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const query = searchInput.value.trim();
            if (!query) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Image lazy loading
 */
function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

/**
 * Add to wishlist
 */
function addToWishlist(productId) {
    fetch('/laptopstore/pages/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Đã thêm vào danh sách yêu thích');
        } else {
            showNotification('error', data.message || 'Lỗi khi thêm vào danh sách yêu thích');
        }
    });
}

/**
 * Product comparison
 */
function addToCompare(productId) {
    let compareList = JSON.parse(localStorage.getItem('compareProducts') || '[]');
    
    if (!compareList.includes(productId)) {
        if (compareList.length >= 4) {
            showNotification('warning', 'Chỉ có thể so sánh tối đa 4 sản phẩm');
            return;
        }
        compareList.push(productId);
        localStorage.setItem('compareProducts', JSON.stringify(compareList));
        showNotification('success', 'Đã thêm vào danh sách so sánh');
        updateCompareCount();
    } else {
        showNotification('info', 'Sản phẩm đã có trong danh sách so sánh');
    }
}

/**
 * Update compare count
 */
function updateCompareCount() {
    const compareList = JSON.parse(localStorage.getItem('compareProducts') || '[]');
    const compareCountEl = document.querySelector('.compare-count');
    
    if (compareCountEl) {
        compareCountEl.textContent = compareList.length;
        compareCountEl.style.display = compareList.length > 0 ? 'block' : 'none';
    }
}

/**
 * Quick view product modal
 */
function initQuickView() {
    document.querySelectorAll('.quick-view-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            fetch(`/laptopstore/pages/quick-view.php?id=${productId}`)
                .then(response => response.text())
                .then(html => {
                    // Create modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade quick-view-modal';
                    modal.innerHTML = html;
                    document.body.appendChild(modal);
                    
                    // Initialize Bootstrap modal
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show(); 
                    
                    // Remove modal after hide
                    modal.addEventListener('hidden.bs.modal', function() {
                        modal.remove();
                    });
                })
                .catch(error => {
                    console.error('Quick view error:', error);
                    showNotification('error', 'Không thể tải thông tin sản phẩm');
                });
        });
    });
}

// Global functions for use in HTML
window.addToCart = addToCart;
window.removeCartItem = removeCartItem;
window.addToWishlist = addToWishlist;
window.addToCompare = addToCompare;
window.formatPrice = formatPrice;
window.showNotification = showNotification;