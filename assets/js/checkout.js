(function () {
    'use strict';

    var configEl = document.getElementById('booking-config');
    var form = document.getElementById('booking-form');
    if (!configEl || !form) {
        return;
    }

    var config = {};
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch (e) {
        return;
    }

    var currentStep = 1;
    var payAmountLabel = document.getElementById('pay-amount-label');
    var payMethodLabel = document.getElementById('pay-method-label');
    var bookingError = document.getElementById('booking-error');
    var stepIndicators = document.querySelectorAll('[data-step-indicator]');
    var steps = document.querySelectorAll('.booking-step');

    function formatMoney(amount) {
        if (!amount || amount <= 0) {
            return '00';
        }
        return amount.toLocaleString('ar-SA') + ' ريال';
    }

    function showStep(step) {
        currentStep = step;
        steps.forEach(function (el) {
            var n = parseInt(el.getAttribute('data-step'), 10);
            var active = n === step;
            el.classList.toggle('is-active', active);
            el.hidden = !active;
        });
        stepIndicators.forEach(function (el) {
            var n = parseInt(el.getAttribute('data-step-indicator'), 10);
            el.classList.toggle('is-active', n === step);
            el.classList.toggle('is-done', n < step);
        });
        if (step === 3) {
            updatePaymentView();
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function getSelectedPaymentMethod() {
        var checked = form.querySelector('input[name="payment_method"]:checked');
        return checked ? checked.value : 'mada';
    }

    function getSelectedPaymentLabel() {
        var checked = form.querySelector('input[name="payment_method"]:checked');
        if (!checked) {
            return '';
        }
        var card = checked.closest('.pay-method');
        if (!card) {
            return '';
        }
        var labelEl = card.querySelector('.pay-method__label');
        return labelEl ? labelEl.textContent.trim() : '';
    }

    function updatePaymentView() {
        if (payMethodLabel) {
            var label = getSelectedPaymentLabel();
            payMethodLabel.textContent = 'طريقة الدفع: ' + (label || 'مدى');
        }
    }

    function showError(msg) {
        if (!bookingError) {
            return;
        }
        bookingError.textContent = msg;
        bookingError.hidden = !msg;
    }

    function validateStep1() {
        var name = form.querySelector('[name="full_name"]');
        var phone = form.querySelector('[name="phone"]');
        var civilId = form.querySelector('[name="civil_id"]');
        var email = form.querySelector('[name="email"]');
        if (!name.value.trim() || name.value.trim().length < 3) {
            showError('أدخل الاسم الكامل.');
            name.focus();
            return false;
        }
        var digits = phone.value.replace(/\D/g, '');
        if (!/^05\d{8}$/.test(digits)) {
            showError('أدخل رقم جوال سعودي صحيح.');
            phone.focus();
            return false;
        }
        var civilDigits = civilId ? civilId.value.replace(/\D/g, '') : '';
        if (!/^[12]\d{9}$/.test(civilDigits)) {
            showError('أدخل رقم السجل المدني صحيحاً (10 أرقام).');
            if (civilId) {
                civilId.focus();
            }
            return false;
        }
        if (!email || !email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showError('أدخل بريداً إلكترونياً صالحاً لاستلام التأكيد.');
            if (email) {
                email.focus();
            }
            return false;
        }
        showError('');
        return true;
    }

    function validateStep2() {
        if (!getSelectedPaymentMethod()) {
            showError('اختر طريقة دفع.');
            return false;
        }
        showError('');
        return true;
    }

    form.querySelectorAll('[data-next-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (currentStep === 1 && !validateStep1()) {
                return;
            }
            if (currentStep === 2 && !validateStep2()) {
                return;
            }
            if (currentStep < 3) {
                showStep(currentStep + 1);
            }
        });
    });

    form.querySelectorAll('[data-prev-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (currentStep > 1) {
                showError('');
                showStep(currentStep - 1);
            }
        });
    });

    form.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', updatePaymentView);
    });

    var civilIdInput = form.querySelector('[name="civil_id"]');
    if (civilIdInput) {
        civilIdInput.addEventListener('input', function () {
            civilIdInput.value = civilIdInput.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    if (payAmountLabel && config.unitPrice !== undefined) {
        payAmountLabel.textContent = formatMoney(config.unitPrice);
    }

    form.addEventListener('submit', function (ev) {
        if (!validateStep1() || !validateStep2()) {
            ev.preventDefault();
            showStep(validateStep1() ? 2 : 1);
            return;
        }
        if (currentStep !== 3) {
            ev.preventDefault();
            showStep(3);
            return;
        }

        showError('');
        var submitBtn = form.querySelector('.pay-confirm__submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'جاري التأكيد...';
        }
    });

    updatePaymentView();
})();
