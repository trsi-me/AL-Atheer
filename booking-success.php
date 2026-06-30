<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$ref = isset($_GET['order']) ? trim((string) $_GET['order']) : '';
if ($ref === '' && isset($_GET['ref'])) {
    $ref = trim((string) $_GET['ref']);
}
$pdo = get_pdo();
$order = $ref !== '' ? getOrderByRef($pdo, $ref) : null;
$booking = null;
if ($order === null && $ref !== '') {
    $booking = getBookingByRef($pdo, $ref);
}

$pageTitle = 'تم الطلب';
$extraCss = ['assets/css/booking.css'];
$bodyClass = 'page-booking-success';

require __DIR__ . '/includes/header.php';

if ($order === null && $booking === null):
?>
<section class="booking booking--error">
    <h1>لم يُعثر على الطلب</h1>
    <p>تحقق من رابط التأكيد أو ارجع لقائمة المسارات.</p>
    <p><a class="btn btn--primary" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">العودة للمسارات</a></p>
</section>
<?php elseif ($order !== null): ?>
<section class="booking-success">
    <div class="booking-success__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
    <h1 class="booking-success__title">تم تأكيد طلبك بنجاح</h1>
    <p class="booking-success__lead">شكراً لك — تم استلام الدفع وتسجيل جميع المسارات في طلبك.</p>

    <dl class="booking-success__details">
        <div><dt>رقم الطلب</dt><dd><?php echo htmlspecialchars((string) $order['order_ref'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>الاسم</dt><dd><?php echo htmlspecialchars((string) $order['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>الجوال</dt><dd><?php echo htmlspecialchars((string) $order['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>طريقة الدفع</dt><dd><?php echo htmlspecialchars(paymentMethodLabel((string) $order['payment_method']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>المبلغ الإجمالي</dt><dd><?php echo htmlspecialchars(formatMoney((float) $order['total_amount']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
    </dl>

    <h2 class="booking-success__items-title">المسارات المحجوزة</h2>
    <ul class="booking-success__items">
        <?php foreach ($order['items'] as $item): ?>
        <li>
            <strong><?php echo htmlspecialchars((string) $item['route_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
            — <?php echo (int) $item['participants']; ?> مشارك —
            <?php echo htmlspecialchars(formatMoney((float) $item['total_amount']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </li>
        <?php endforeach; ?>
    </ul>

    <p class="booking-success__note">احتفظ برقم الطلب — قد يُطلب عند نقطة التجمع.</p>

    <div class="booking-success__actions">
        <a class="btn btn--primary btn--large" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">المسارات</a>
        <a class="btn btn--secondary btn--large" href="<?php echo htmlspecialchars(cartPageUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">سلة التسوق</a>
    </div>
</section>
<?php else:
    $routeId = (int) $booking['route_id'];
?>
<section class="booking-success">
    <div class="booking-success__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
    <h1 class="booking-success__title">تم تأكيد حجزك بنجاح</h1>
    <p class="booking-success__lead">شكراً لك — تم استلام الدفع وتسجيل مشاركتك في المسار.</p>

    <dl class="booking-success__details">
        <div><dt>رقم الحجز</dt><dd><?php echo htmlspecialchars((string) $booking['booking_ref'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>المسار</dt><dd><?php echo htmlspecialchars((string) $booking['route_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>الاسم</dt><dd><?php echo htmlspecialchars((string) $booking['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>الجوال</dt><dd><?php echo htmlspecialchars((string) $booking['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>عدد المشاركين</dt><dd><?php echo (int) $booking['participants']; ?></dd></div>
        <div><dt>طريقة الدفع</dt><dd><?php echo htmlspecialchars(paymentMethodLabel((string) $booking['payment_method']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
        <div><dt>المبلغ المدفوع</dt><dd><?php echo htmlspecialchars(formatMoney((float) $booking['total_amount']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></dd></div>
    </dl>

    <p class="booking-success__note">احتفظ برقم الحجز — قد يُطلب عند نقطة التجمع.</p>

    <div class="booking-success__actions">
        <a class="btn btn--primary btn--large" href="<?php echo htmlspecialchars(assetUrl('route.php?id=' . $routeId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">تفاصيل المسار</a>
        <a class="btn btn--secondary btn--large" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">المسارات</a>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
