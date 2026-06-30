<?php
/**
 * تسجيل دخول المدير
 */
declare(strict_types=1);

session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/auth.php';

if (!empty($_SESSION['atheer_admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $p = isset($_POST['password']) ? (string) $_POST['password'] : '';
    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['atheer_admin'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
}

$pageTitle = 'تسجيل الدخول';
$extraCss = [];
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="admin-login">
    <h1 class="admin-login__title">لوحة التحكم</h1>
    <?php if ($error !== ''): ?>
        <p class="admin-login__error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form class="admin-login__form" method="post" action="">
        <label class="admin-login__label">اسم المستخدم
            <input class="admin-login__input" type="text" name="username" autocomplete="username" required>
        </label>
        <label class="admin-login__label">كلمة المرور
            <input class="admin-login__input" type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn btn--primary" type="submit">دخول</button>
    </form>
    <p class="admin-login__back"><a href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">العودة للموقع</a></p>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
