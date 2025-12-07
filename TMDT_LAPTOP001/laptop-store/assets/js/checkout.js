// Validation for checkout form
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="phone"]');
            const address = document.querySelector('textarea[name="shipping_address"]');
            const paymentMethod = document.querySelector('select[name="payment_method"]');
            
            let isValid = true;
            let errorMessage = '';
            
            // Validate phone
            const phoneRegex = /^(0[3|5|7|8|9])[0-9]{8}$/;
            if (!phoneRegex.test(phone.value)) {
                isValid = false;
                errorMessage += 'Số điện thoại không hợp lệ\n';
                phone.classList.add('is-invalid');
            } else {
                phone.classList.remove('is-invalid');
            }
            
            // Validate address
            if (address.value.trim().length < 10) {
                isValid = false;
                errorMessage += 'Địa chỉ phải có ít nhất 10 ký tự\n';
                address.classList.add('is-invalid');
            } else {
                address.classList.remove('is-invalid');
            }
            
            // Validate payment method
            if (!paymentMethod.value) {
                isValid = false;
                errorMessage += 'Vui lòng chọn phương thức thanh toán\n';
                paymentMethod.classList.add('is-invalid');
            } else {
                paymentMethod.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Vui lòng sửa các lỗi sau:\n' + errorMessage);
            }
        });
    }
    
    // Real-time validation
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            const phoneRegex = /^(0[3|5|7|8|9])[0-9]{8}$/;
            if (phoneRegex.test(this.value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
});