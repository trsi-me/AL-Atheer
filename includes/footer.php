<?php
declare(strict_types=1);
if (!function_exists('assetUrl')) {
    require_once __DIR__ . '/functions.php';
}
$extraJs = $extraJs ?? [];
?>
</main>
<footer class="site-footer">
    <div class="site-footer__inner">
        <p>جمعية المشي والجري بالأحساء — منصة الأثير لعرض المسارات الرياضية والفلكية والثقافية.</p>
        <p class="site-footer__small">جميع الحقوق محفوظة.</p>
    </div>
</footer>
<script src="<?php echo htmlspecialchars(assetUrl('assets/js/main.js'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></script>
<?php if (!empty($extraJs)): ?>
<?php foreach ($extraJs as $js): ?>
<?php
    $jsSrc = (preg_match('#^https?://#i', $js)) ? $js : assetUrl($js);
?>
<script src="<?php echo htmlspecialchars($jsSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
