<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (isset($_GET['add'])) {
    $addId = (int) $_GET['add'];
    if ($addId > 0) {
        cartAdd($addId, 1);
        header('Location: ' . assetUrl('cart.php?added=1'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['cart_action']) ? (string) $_POST['cart_action'] : '';
    $routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
    if ($action === 'update' && $routeId > 0) {
        cartUpdate($routeId, (int) ($_POST['participants'] ?? 1));
    } elseif ($action === 'remove' && $routeId > 0) {
        cartRemove($routeId);
    }
    header('Location: ' . cartPageUrl());
    exit;
}

$lines = cartGetLines();
$added = isset($_GET['added']);
$pageTitle = 'سلة التسوق';
$extraCss = ['assets/css/cart.css'];
$bodyClass = 'page-cart';

require __DIR__ . '/includes/header.php';
?>

<section class="cart-page">
    <header class="cart-page__head">
        <h1 class="cart-page__title">سلة التسوق</h1>
        <p class="cart-page__lead">أضف المسارات التي تريدها ثم أكمل الدفع — مثل تجربة المتجر الإلكتروني.</p>
    </header>

    <?php if ($added): ?>
    <p class="cart-toast" role="status">تمت إضافة المسار إلى السلة.</p>
    <?php endif; ?>

    <?php if (count($lines) === 0): ?>
    <div class="cart-empty">
        <p class="cart-empty__text">سلتك فارغة حالياً.</p>
        <a class="btn btn--primary" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">تصفح المسارات</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($lines as $line): ?>
            <?php
            $route = $line['route'];
            $rid = (int) $line['route_id'];
            $imgResolved = resolveRouteImageUrl(isset($route['image_url']) ? (string) $route['image_url'] : null);
            ?>
            <article class="cart-item">
                <div class="cart-item__media<?php echo $imgResolved === '' ? ' cart-item__media--placeholder' : ''; ?>">
                    <?php if ($imgResolved !== ''): ?>
                    <img src="<?php echo htmlspecialchars($imgResolved, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="cart-item__body">
                    <h2 class="cart-item__name">
                        <a href="<?php echo htmlspecialchars(assetUrl('route.php?id=' . $rid), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $route['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></a>
                    </h2>
                    <p class="cart-item__meta"><?php echo htmlspecialchars(routeTypeLabel((string) $route['type']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> — <?php echo htmlspecialchars(formatMoney((float) $line['unit_price']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> للفرد</p>
                    <form class="cart-item__form" method="post" action="">
                        <input type="hidden" name="route_id" value="<?php echo $rid; ?>">
                        <label class="cart-item__qty">
                            <span>المشاركون</span>
                            <input type="number" name="participants" min="<?php echo (int) $line['min_participants']; ?>" max="<?php echo (int) $line['max_participants']; ?>" value="<?php echo (int) $line['participants']; ?>">
                        </label>
                        <button type="submit" name="cart_action" value="update" class="btn btn--small btn--secondary">تحديث</button>
                        <button type="submit" name="cart_action" value="remove" class="btn btn--small btn--muted">حذف</button>
                    </form>
                </div>
                <div class="cart-item__price">
                    <strong><?php echo htmlspecialchars(formatMoney((float) $line['line_total']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <aside class="cart-summary">
            <h2 class="cart-summary__title">ملخص الطلب</h2>
            <p class="cart-summary__row"><span>عدد المسارات</span><strong><?php echo count($lines); ?></strong></p>
            <p class="cart-summary__row"><span>المجموع</span><strong><?php echo htmlspecialchars(formatMoney(cartSubtotal()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></p>
            <a class="btn btn--primary btn--large cart-summary__checkout" href="<?php echo htmlspecialchars(checkoutPageUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">إتمام الشراء والدفع</a>
            <a class="btn btn--secondary" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">متابعة التسوق</a>
        </aside>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
