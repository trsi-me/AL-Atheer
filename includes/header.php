<?php
/**
 * الهيدر المشترك — مشروع الأثير
 */
declare(strict_types=1);

if (!function_exists('assetUrl')) {
    require_once __DIR__ . '/functions.php';
}

$pageTitle = $pageTitle ?? 'المسارات';
$siteName  = 'جمعية المشي والجري بالأحساء';
$extraCss  = $extraCss ?? [];
$extraHeadLinks = $extraHeadLinks ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="منصة عرض مسارات جمعية المشي والجري بالأحساء — رياضية، فلكية، ثقافية، وغيرها.">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> — <?php echo htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(assetUrl('assets/css/main.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
<?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(assetUrl($css), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
<?php endforeach; ?>
<?php foreach ($extraHeadLinks as $hl): ?>
    <link<?php
        foreach ($hl as $attr => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            echo ' ' . htmlspecialchars((string) $attr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '="' . htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }
    ?>>
<?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
<header class="site-header">
    <div class="site-header__inner">
        <a class="site-header__brand" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
            <img class="site-header__logo" src="<?php echo htmlspecialchars(assetUrl('assets/images/widelogo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" width="280" height="72">
        </a>
        <nav class="site-header__nav" aria-label="التنقل الرئيسي">
            <a href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">الرئيسية</a>
        </nav>
    </div>
</header>
<main class="site-main">
