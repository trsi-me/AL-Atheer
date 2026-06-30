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
    var payWallet = document.getElementById('pay-wallet');
    var payCardForm = document.getElementById('pay-card-form');
    var bookingError = document.getElementById('booking-error');
    var bookingLoading = document.getElementById('booking-loading');
    var stepIndicators = document.querySelectorAll('[data-step-indicator]');
    var steps = document.querySelectorAll('.booking-step');

    function formatMoney(amount) {
        if (!amount || amount <= 0) {
            return 'مجاني';
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

    function updatePaymentView() {
        var method = getSelectedPaymentMethod();
        var isCard = method === 'mada' || method === 'card';
        if (payWallet) {
            payWallet.hidden = isCard;
        }
        if (payCardForm) {
            payCardForm.hidden = !isCard;
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

    function formatCardNumber(value) {
        var digits = value.replace(/\D/g, '').slice(0, 16);
        return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    }

    function formatExpiry(value) {
        var digits = value.replace(/\D/g, '').slice(0, 4);
        if (digits.length >= 3) {
            return digits.slice(0, 2) + '/' + digits.slice(2);
        }
        return digits;
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

    var cardNumber = form.querySelector('[name="card_number"]');
    if (cardNumber) {
        cardNumber.addEventListener('input', function () {
            cardNumber.value = formatCardNumber(cardNumber.value);
        });
    }

    var cardExpiry = form.querySelector('[name="card_expiry"]');
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function () {
            cardExpiry.value = formatExpiry(cardExpiry.value);
        });
    }

    if (payAmountLabel && config.unitPrice !== undefined) {
        payAmountLabel.textContent = formatMoney(config.unitPrice);
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (!validateStep1() || !validateStep2()) {
            showStep(validateStep1() ? 2 : 1);
            return;
        }
        if (currentStep !== 3) {
            showStep(3);
            return;
        }

        showError('');
        if (bookingLoading) {
            bookingLoading.hidden = false;
        }

        var submitButtons = form.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(function (btn) {
            btn.disabled = true;
        });

        var formData = new FormData(form);
        var controller = new AbortController();
        var timeoutId = window.setTimeout(function () {
            controller.abort();
        }, 20000);

        fetch(config.submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            signal: controller.signal
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (result.data && result.data.ok && result.data.redirect) {
                    window.location.href = result.data.redirect;
                    return;
                }
                if (bookingLoading) {
                    bookingLoading.hidden = true;
                }
                submitButtons.forEach(function (btn) {
                    btn.disabled = false;
                });
                showError((result.data && result.data.message) || 'تعذّر إتمام الطلب.');
            })
            .catch(function () {
                if (bookingLoading) {
                    bookingLoading.hidden = true;
                }
                submitButtons.forEach(function (btn) {
                    btn.disabled = false;
                });
                showError('حدث خطأ في الاتصال. حاول مرة أخرى.');
            })
            .finally(function () {
                window.clearTimeout(timeoutId);
            });
    });

    updatePaymentView();
})();
