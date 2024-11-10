const selectGateway = () => {
    let gateways = document.querySelectorAll('.sc-gateway input');

    if (gateways) {
        gateways.forEach(gateway => {
            gateway.addEventListener('change', function () {
                let fieldWrapper = this.closest('.sc-gateway').querySelector('.sc-gateway-field');

                document.querySelectorAll('.sc-gateway-field').forEach(field => {
                    field.style.display = 'none';
                })

                fieldWrapper.style.display = 'block';
            })
        })
    }
}

const proceedPay = () => {
    let payButton = document.querySelector('.sc-pay .button');

    if (payButton) {
        payButton.addEventListener('click', function (e) {
            e.preventDefault();

            let form = document.querySelector('form.sc-checkout');
            let payment_method = document.querySelector('input[name="sc-gateway"]:checked').value;
            let isValid = validForm(form);
            let isValidEmail = validEmail(form);

            if (isValid) {
                if (isValidEmail) {
                    let data = new FormData(form);
                    data.append('action', 'sc_proceed_pay');
                    data.append('payment_method', payment_method)

                    fetch('/wp-admin/admin-ajax.php', {
                            method: 'POST',
                            body: data,
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.is_thank_you) {
                                    window.location.href = data.payment_url;
                                } else {
                                    window.open(data.payment_url, '_blank');
                                    showMessage('Success, you will be redirected!', 'success');
                                }
                            }
                        })
                        .catch(error => console.error('Error when calling a function:', error))
                } else {
                    showMessage('Please, enter a valid email!', 'error');
                }
            } else {
                showMessage('Please, fill all fields!', 'error');
            }
        })
    }
}

const validForm = (form) => {
    if (!form) {
        console.error('Form not found');
        return false;
    }

    const requiredFields = form.querySelectorAll('input[required]');
    let allFieldsFilled = true;

    requiredFields.forEach(field => {
        const checkField = () => {
            if (!field.value.trim()) {
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        };

        checkField();

        field.addEventListener('input', checkField);
        field.addEventListener('blur', checkField);

        if (!field.value.trim()) {
            allFieldsFilled = false;
        }
    });

    return allFieldsFilled;
}

const validEmail = (form) => {
    let emailInput = form.querySelector('input[type="email"]');
    let email = emailInput.value;

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}

const applyCoupon = () => {
    let applyButton = document.querySelector('.sc-coupon .button');
    let couponInput = document.querySelector('#sc-coupon');

    if (applyButton && couponInput) {
        applyButton.addEventListener('click', function (e) {
            e.preventDefault();

            let couponValue = couponInput.value;

            let data = new FormData();
            data.append('action', 'sc_apply_coupon');
            data.append('coupon', couponValue)

            fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: data,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        changeSummary(data.summary);
                        showMessage(data.message, 'success');
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => console.error('Error when calling a function:', error))
        })
    }
}

const removeCoupon = () => {
    let removeCoupon = document.querySelector('.remove-coupon');

    if (removeCoupon) {
        removeCoupon.addEventListener('click', function (e) {
            e.preventDefault();

            let data = new FormData();
            data.append('action', 'sc_remove_coupon');

            fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: data,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        changeSummary(data.summary, 'remove');
                        showMessage(data.message, 'success');
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => console.error('Error when calling a function:', error))
        })
    }
}

const changeSummary = (data, type = 'add') => {
    document.querySelector('.sc-summary-subtotal .value').innerHTML = data.subtotal;
    document.querySelector('.sc-summary-discount .value').innerHTML = data.discount_total;
    document.querySelector('.sc-summary-total .value').innerHTML = data.total;

    if (type == 'add') {
        document.querySelector('.sc-summary-coupon .value').innerHTML = data.applied_coupon;
        document.querySelector('.sc-summary-coupon').classList.remove('sc-hide');
    } else {
        document.querySelector('.sc-summary-coupon .value').innerHTML = '';
        document.querySelector('.sc-summary-coupon').classList.add('sc-hide');
    }
}

const showMessage = (msg, type) => {
    let notifyWrapper = document.querySelector('.sc-checkout-notify');

    if (notifyWrapper) {
        let notifyP = notifyWrapper.querySelector('p');

        notifyWrapper.classList.remove('error', 'success');
        notifyWrapper.classList.add('shown');
        notifyWrapper.classList.add(type);

        notifyP.innerText = msg;
    }
}

document.addEventListener('DOMContentLoaded', function (e) {
    selectGateway();
    proceedPay();
    applyCoupon();
    removeCoupon();
})