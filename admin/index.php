<?php
/**
 * لوحة التحكم — قائمة المسارات
 */
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/functions.php';

if (empty($_SESSION['atheer_admin'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();
atheerEnsureSchema($pdo);
atheerEnsureSiteBranding($pdo);
ensureDefaultRoutesSeeded($pdo);
atheerFixLegacyImagePaths($pdo);
atheerApplyReferenceContentV2($pdo);
$stmt = $pdo->query('SELECT id, name, type, is_active, source_url, updated_at FROM routes ORDER BY id ASC');
$all = $stmt->fetchAll();

$pageTitle = 'لوحة التحكم';
$bodyClass = 'page-admin';

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="admin-panel">
    <h1 class="admin-panel__title">إدارة المسارات</h1>
    <p class="admin-panel__tools">
        <a class="btn btn--secondary" href="bookings.php">الحجوزات</a>
        <a class="btn btn--secondary" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">عرض الموقع</a>
        <a class="btn btn--muted" href="login.php?logout=1">خروج</a>
    </p>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>المعرّف</th>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>نشط</th>
                    <th>آخر تحديث</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($all as $row): ?>
                <tr>
                    <td><?php echo (int) $row['id']; ?></td>
                    <td><?php echo htmlspecialchars((string) $row['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td><?php echo !empty($row['is_active']) ? 'نعم' : 'لا'; ?></td>
                    <td><?php echo htmlspecialchars((string) $row['updated_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                    <td>
                        <a class="btn btn--small btn--secondary" href="update_routes.php?id=<?php echo (int) $row['id']; ?>">تعديل</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
