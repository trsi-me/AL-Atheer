<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$lines = cartGetLines();
if (count($lines) === 0) {
    header('Location: ' . cartPageUrl());
    exit;
}

$pageTitle = 'إتمام الشراء';
$extraCss = ['assets/css/booking.css', 'assets/css/cart.css'];
$bodyClass = 'page-checkout';

$paymentMethods = paymentMethodsCatalog();
$subtotal = cartSubtotal();

$checkoutConfig = [
    'unitPrice' => $subtotal,
    'submitUrl' => assetUrl('api/checkout_submit.php'),
];

require __DIR__ . '/includes/header.php';
?>

<section class="booking checkout-page" id="checkout-app">
    <header class="booking__head">
        <h1 class="booking__title">إتمام الشراء</h1>
        <p class="booking__lead">راجع طلبك وادفع بأمان — <?php echo count($lines); ?> مسار في السلة.</p>
    </header>

    <ol class="booking-steps" aria-label="خطوات الدفع">
        <li class="booking-steps__item is-active" data-step-indicator="1"><span>1</span> بياناتك</li>
        <li class="booking-steps__item" data-step-indicator="2"><span>2</span> الدفع</li>
        <li class="booking-steps__item" data-step-indicator="3"><span>3</span> التأكيد</li>
    </ol>

    <div class="booking__layout">
        <aside class="booking-summary cart-checkout-summary" aria-label="ملخص السلة">
            <h2 class="booking-summary__name">سلة التسوق</h2>
            <ul class="cart-checkout-list">
                <?php foreach ($lines as $line): ?>
                <li class="cart-checkout-list__item">
                    <span class="cart-checkout-list__name"><?php echo htmlspecialchars((string) $line['route']['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                    <span class="cart-checkout-list__meta"><?php echo (int) $line['participants']; ?> مشارك — <?php echo htmlspecialchars(formatMoney((float) $line['line_total']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="booking-summary__total">
                <span>الإجمالي</span>
                <strong id="summary-total"><?php echo htmlspecialchars(formatMoney($subtotal), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
            </div>
            <a class="btn btn--muted btn--small" href="<?php echo htmlspecialchars(cartPageUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">تعديل السلة</a>
        </aside>

        <div class="booking-panel">
            <form class="booking-form" id="booking-form" novalidate>
                <div class="booking-step is-active" data-step="1">
                    <h2 class="booking-panel__title">بيانات المشتري</h2>
                    <label class="booking-field">
                        <span>الاسم الكامل</span>
                        <input type="text" name="full_name" autocomplete="name" required>
                    </label>
                    <label class="booking-field">
                        <span>رقم الجوال</span>
                        <input type="tel" name="phone" inputmode="numeric" placeholder="05xxxxxxxx" autocomplete="tel" required>
                    </label>
                    <label class="booking-field">
                        <span>البريد الإلكتروني (اختياري)</span>
                        <input type="email" name="email" autocomplete="email">
                    </label>
                    <label class="booking-field">
                        <span>ملاحظات (اختياري)</span>
                        <textarea name="notes" rows="3"></textarea>
                    </label>
                    <div class="booking-panel__actions">
                        <a class="btn btn--muted" href="<?php echo htmlspecialchars(cartPageUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">رجوع للسلة</a>
                        <button type="button" class="btn btn--primary" data-next-step>متابعة للدفع</button>
                    </div>
                </div>

                <div class="booking-step" data-step="2" hidden>
                    <h2 class="booking-panel__title">اختر طريقة الدفع</h2>
                    <div class="pay-methods" role="radiogroup" aria-label="طرق الدفع">
                        <?php foreach ($paymentMethods as $key => $method): ?>
                        <label class="pay-method">
                            <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"<?php echo $key === 'mada' ? ' checked' : ''; ?>>
                            <span class="pay-method__card">
                                <span class="pay-method__badge"><?php echo htmlspecialchars($method['badge'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                                <span class="pay-method__label"><?php echo htmlspecialchars($method['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                                <span class="pay-method__hint"><?php echo htmlspecialchars($method['hint'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="booking-panel__actions">
                        <button type="button" class="btn btn--muted" data-prev-step>رجوع</button>
                        <button type="button" class="btn btn--primary" data-next-step>متابعة</button>
                    </div>
                </div>

                <div class="booking-step" data-step="3" hidden>
                    <h2 class="booking-panel__title">تأكيد الدفع</h2>
                    <div class="pay-wallet" id="pay-wallet" hidden>
                        <p class="pay-wallet__text">سيتم فتح محفظتك الرقمية لإتمام الدفع بأمان.</p>
                        <button type="submit" class="btn btn--primary btn--large pay-wallet__btn">ادفع الآن</button>
                    </div>
                    <div class="pay-card-form" id="pay-card-form">
                        <label class="booking-field">
                            <span>رقم البطاقة</span>
                            <input type="text" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" maxlength="19">
                        </label>
                        <div class="pay-card-form__row">
                            <label class="booking-field">
                                <span>تاريخ الانتهاء</span>
                                <input type="text" name="card_expiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5">
                            </label>
                            <label class="booking-field">
                                <span>CVV</span>
                                <input type="password" name="card_cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="***" maxlength="4">
                            </label>
                        </div>
                        <button type="submit" class="btn btn--primary btn--large">ادفع <span id="pay-amount-label"><?php echo htmlspecialchars(formatMoney($subtotal), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></button>
                    </div>
                    <p class="booking-secure"><span aria-hidden="true">🔒</span> اتصال مشفّر — بيانات الدفع لا تُخزَّن على الخادم.</p>
                    <p class="booking-error" id="booking-error" role="alert" hidden></p>
                    <div class="booking-panel__actions">
                        <button type="button" class="btn btn--muted" data-prev-step>رجوع</button>
                    </div>
                </div>
            </form>
            <div class="booking-loading" id="booking-loading" hidden aria-live="polite">
                <div class="booking-loading__spinner" aria-hidden="true"></div>
                <p>جاري معالجة الدفع…</p>
            </div>
        </div>
    </div>
</section>

<script type="application/json" id="booking-config"><?php echo json_encode($checkoutConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

<?php
$extraJs = ['assets/js/checkout.js'];
require __DIR__ . '/includes/footer.php';
