<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/functions.php';

if (empty($_SESSION['atheer_admin'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();
atheerEnsureSiteBranding($pdo);
$bookings = getAllBookings($pdo, 200);

$pageTitle = 'الحجوزات';
$bodyClass = 'page-admin';

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="admin-panel">
    <h1 class="admin-panel__title">الحجوزات</h1>
    <p class="admin-panel__tools">
        <a class="btn btn--secondary" href="index.php">المسارات</a>
        <a class="btn btn--muted" href="login.php?logout=1">خروج</a>
    </p>

    <?php if (count($bookings) === 0): ?>
    <p>لا توجد حجوزات حتى الآن.</p>
    <?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>رقم الحجز</th>
                    <th>المسار</th>
                    <th>الاسم</th>
                    <th>الجوال</th>
                    <th>السجل المدني</th>
                    <th>المشاركون</th>
                    <th>المبلغ</th>
                    <th>الدفع</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string) ($b['order_ref'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $b['booking_ref'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $b['route_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $b['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $b['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($b['civil_id'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo (int) $b['participants']; ?></td>
                    <td><?php echo htmlspecialchars(formatMoney((float) $b['total_amount']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(paymentMethodLabel((string) $b['payment_method']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $b['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
