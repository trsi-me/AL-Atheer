(function () {
    'use strict';

    var cartApiUrl = document.body.getAttribute('data-cart-api');
    if (!cartApiUrl) {
        return;
    }

    function updateCartBadge(count) {
        var badge = document.getElementById('cart-badge');
        if (!badge) {
            return;
        }
        badge.textContent = String(count);
        badge.hidden = count < 1;
    }

    function showToast(message) {
        var existing = document.querySelector('.cart-toast-float');
        if (existing) {
            existing.remove();
        }
        var toast = document.createElement('p');
        toast.className = 'cart-toast-float';
        toast.setAttribute('role', 'status');
        toast.textContent = message;
        document.body.appendChild(toast);
        window.setTimeout(function () {
            toast.classList.add('is-hide');
            window.setTimeout(function () {
                toast.remove();
            }, 300);
        }, 2800);
    }

    function clampQty(input, value) {
        var min = parseInt(input.getAttribute('min'), 10);
        var max = parseInt(input.getAttribute('max'), 10);
        if (isNaN(min)) {
            min = 25;
        }
        if (isNaN(max)) {
            max = 100;
        }
        if (isNaN(value) || value < min) {
            return min;
        }
        if (value > max) {
            return max;
        }
        return value;
    }

    document.querySelectorAll('[data-qty-stepper]').forEach(function (stepper) {
        var input = stepper.querySelector('.qty-stepper__input');
        var minus = stepper.querySelector('[data-qty-minus]');
        var plus = stepper.querySelector('[data-qty-plus]');
        var form = stepper.closest('form');
        if (!input) {
            return;
        }

        function applyDelta(delta) {
            var next = clampQty(input, parseInt(input.value, 10) + delta);
            input.value = String(next);
            if (form) {
                var updateBtn = form.querySelector('button[name="cart_action"][value="update"]');
                if (updateBtn) {
                    updateBtn.click();
                } else {
                    form.submit();
                }
            }
        }

        if (minus) {
            minus.addEventListener('click', function () {
                applyDelta(-1);
            });
        }
        if (plus) {
            plus.addEventListener('click', function () {
                applyDelta(1);
            });
        }
    });

    document.querySelectorAll('[data-add-to-cart]').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            var routeId = btn.getAttribute('data-route-id');
            if (!routeId) {
                return;
            }
            var formData = new FormData();
            formData.append('action', 'add');
            formData.append('route_id', routeId);
            formData.append('participants', '25');

            btn.disabled = true;
            fetch(cartApiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    btn.disabled = false;
                    if (data.ok) {
                        updateCartBadge(data.count || 0);
                        showToast(data.message || 'تمت الإضافة للسلة');
                    } else {
                        showToast(data.message || 'تعذّر الإضافة');
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    showToast('حدث خطأ. حاول مرة أخرى.');
                });
        });
    });

    fetch(cartApiUrl + '?action=count', { credentials: 'same-origin' })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.ok) {
                updateCartBadge(data.count || 0);
            }
        })
        .catch(function () {});
})();
