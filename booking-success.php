<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$pdo = get_pdo();
$booking = $ref !== '' ? getBookingByRef($pdo, $ref) : null;

$pageTitle = 'تم الحجز';
$extraCss = ['assets/css/booking.css'];
$bodyClass = 'page-booking-success';

require __DIR__ . '/includes/header.php';

if ($booking === null):
?>
<section class="booking booking--error">
    <h1>لم يُعثر على الحجز</h1>
    <p>تحقق من رابط التأكيد أو ارجع لقائمة المسارات.</p>
    <p><a class="btn btn--primary" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">العودة للمسارات</a></p>
</section>
<?php
else:
    $routeId = (int) $booking['route_id'];
?>
<section class="booking-success">
    <div class="booking-success__icon" aria-hidden="true">✓</div>
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
