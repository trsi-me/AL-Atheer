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
            formData.append('participants', '1');

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
