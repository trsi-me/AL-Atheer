<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$routeId = isset($_GET['route']) ? (int) $_GET['route'] : 0;
$route = getRouteById($routeId);

if ($route === null || !isRouteBookingOpen($route)) {
    http_response_code(404);
    $pageTitle = 'المسار غير متاح';
    $extraCss = ['assets/css/booking.css'];
    require __DIR__ . '/includes/header.php';
    echo '<section class="booking booking--error"><h1>المسار غير متاح للحجز</h1>';
    echo '<p><a class="btn btn--secondary" href="' . htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">العودة للمسارات</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'حجز المسار';
$extraCss = ['assets/css/booking.css'];
$bodyClass = 'page-booking';

$name = (string) $route['name'];
$typeLabel = routeTypeLabel((string) $route['type']);
$dateStr = formatDate(isset($route['date']) ? (string) $route['date'] : null);
$timeStr = formatTime(isset($route['time']) ? (string) $route['time'] : null);
$meeting = (string) ($route['meeting_point'] ?? '');
$unitPrice = routeUnitPrice($route);
$minP = isset($route['min_participants']) && $route['min_participants'] !== '' ? (int) $route['min_participants'] : 1;
$maxP = isset($route['max_participants']) && $route['max_participants'] !== '' ? (int) $route['max_participants'] : 0;
$paymentMethods = paymentMethodsCatalog();

$bookingConfig = [
    'routeId' => $routeId,
    'unitPrice' => $unitPrice,
    'minParticipants' => max(1, $minP),
    'maxParticipants' => $maxP > 0 ? $maxP : 99,
    'submitUrl' => assetUrl('api/booking_submit.php'),
];

require __DIR__ . '/includes/header.php';
?>

<section class="booking" id="booking-app">
    <header class="booking__head">
        <h1 class="booking__title">إتمام الحجز</h1>
        <p class="booking__lead">احجز مقعدك وادفع بأمان داخل المنصة — بدون تحويل لموقع خارجي.</p>
    </header>

    <ol class="booking-steps" aria-label="خطوات الحجز">
        <li class="booking-steps__item is-active" data-step-indicator="1"><span>1</span> بياناتك</li>
        <li class="booking-steps__item" data-step-indicator="2"><span>2</span> الدفع</li>
        <li class="booking-steps__item" data-step-indicator="3"><span>3</span> التأكيد</li>
    </ol>

    <div class="booking__layout">
        <aside class="booking-summary" aria-label="ملخص المسار">
            <h2 class="booking-summary__name"><?php echo htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
            <p class="booking-summary__type"><?php echo htmlspecialchars($typeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
            <ul class="booking-summary__facts">
                <li><span>التاريخ</span><strong><?php echo htmlspecialchars($dateStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></li>
                <li><span>الوقت</span><strong><?php echo htmlspecialchars($timeStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></li>
                <?php if ($meeting !== ''): ?>
                <li><span>نقطة التجمع</span><strong><?php echo htmlspecialchars($meeting, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></li>
                <?php endif; ?>
                <li><span>الرسوم للفرد</span><strong id="summary-unit-price"><?php echo htmlspecialchars(formatMoney($unitPrice), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></li>
            </ul>
            <div class="booking-summary__total">
                <span>الإجمالي</span>
                <strong id="summary-total"><?php echo htmlspecialchars(formatMoney($unitPrice * max(1, $minP)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
            </div>
        </aside>

        <div class="booking-panel">
            <form class="booking-form" id="booking-form" novalidate>
                <input type="hidden" name="route_id" value="<?php echo $routeId; ?>">

                <div class="booking-step is-active" data-step="1">
                    <h2 class="booking-panel__title">بيانات المشارك</h2>
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
                        <span>عدد المشاركين</span>
                        <input type="number" name="participants" id="participants-input" min="<?php echo max(1, $minP); ?>" max="<?php echo $maxP > 0 ? $maxP : 99; ?>" value="<?php echo max(1, $minP); ?>" required>
                    </label>
                    <label class="booking-field">
                        <span>ملاحظات (اختياري)</span>
                        <textarea name="notes" rows="3" placeholder="أي طلبات خاصة أو معلومات إضافية"></textarea>
                    </label>
                    <div class="booking-panel__actions">
                        <a class="btn btn--muted" href="<?php echo htmlspecialchars(assetUrl('route.php?id=' . $routeId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">إلغاء</a>
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
                        <button type="submit" class="btn btn--primary btn--large pay-wallet__btn" id="wallet-pay-btn">ادفع الآن</button>
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
                        <button type="submit" class="btn btn--primary btn--large" id="card-pay-btn">ادفع <span id="pay-amount-label"><?php echo htmlspecialchars(formatMoney($unitPrice * max(1, $minP)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></button>
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

<script type="application/json" id="booking-config"><?php echo json_encode($bookingConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

<?php
$extraJs = ['assets/js/booking.js'];
require __DIR__ . '/includes/footer.php';
