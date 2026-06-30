<?php
/**
 * الصفحة الرئيسية — عرض المسارات
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'المسارات';
$extraCss  = ['assets/css/routes.css'];
$bodyClass = 'page-routes';

$routes = getAllRoutes();

require __DIR__ . '/includes/header.php';
?>

<section class="routes-hero">
    <h1 class="routes-hero__title">المسارات</h1>
    <p class="routes-hero__lead">اختر أحد المسارات الستة وانطلق معنا في رحلة رياضية وثقافية</p>
</section>

<section class="routes-grid-wrap" aria-label="قائمة المسارات">
<?php if (count($routes) === 0): ?>
    <p class="routes-empty">لا توجد مسارات معروضة حالياً. يمكن للمسؤول تشغيل جلب البيانات من لوحة التحكم.</p>
<?php else: ?>
    <div class="routes-grid">
    <?php foreach ($routes as $r): ?>
        <?php
        $rid = (int) $r['id'];
        $typeLabel = routeTypeLabel((string) $r['type']);
        $desc = truncateText((string) ($r['description'] ?? ''), 140);
        $imgResolved = resolveRouteImageUrl(isset($r['image_url']) ? (string) $r['image_url'] : null);
        $detailHref = assetUrl('route.php?id=' . $rid);
        ?>
        <article class="route-card">
            <div class="route-card__media<?php echo $imgResolved === '' ? ' route-card__media--placeholder' : ''; ?>">
                <span class="route-card__badge"><?php echo htmlspecialchars($typeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                <?php if ($imgResolved !== ''): ?>
                <img class="route-card__img" src="<?php echo htmlspecialchars($imgResolved, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="" loading="lazy" width="400" height="240">
                <?php else: ?>
                <div class="route-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <div class="route-card__body">
                <h2 class="route-card__name"><?php echo htmlspecialchars((string) $r['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
                <p class="route-card__desc"><?php echo htmlspecialchars($desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                <div class="route-card__actions">
                    <button type="button" class="btn btn--primary" data-add-to-cart data-route-id="<?php echo $rid; ?>">أضف للسلة</button>
                    <a class="btn btn--secondary" href="<?php echo htmlspecialchars($detailHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">تفاصيل</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<?php
$extraJs = ['assets/js/routes.js'];
require __DIR__ . '/includes/footer.php';
