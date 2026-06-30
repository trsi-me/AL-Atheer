<?php
/**
 * صفحة تفاصيل مسار واحد
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$route = getRouteById($id);

if ($route === null) {
    http_response_code(404);
    $pageTitle = 'المسار غير موجود';
    $extraCss = ['assets/css/route-detail.css'];
    require __DIR__ . '/includes/header.php';
    echo '<section class="route-detail route-detail--error"><h1>المسار غير موجود</h1><p>تأكد من الرابط أو ارجع إلى قائمة المسارات.</p>';
    echo '<p><a class="btn btn--secondary" href="' . htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">العودة للمسارات</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = (string) $route['name'];
$extraCss = ['assets/css/route-detail.css'];
$extraHeadLinks = [
    ['rel' => 'stylesheet', 'href' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'],
];
$bodyClass = 'page-route-detail';

$name = (string) $route['name'];
$description = (string) ($route['description'] ?? '');
$detailExtra = (string) ($route['detail_extra'] ?? '');
$dateStr = formatDate(isset($route['date']) ? (string) $route['date'] : null);
$timeStr = formatTime(isset($route['time']) ? (string) $route['time'] : null);
$meeting = (string) ($route['meeting_point'] ?? '');
if ($meeting === '') {
    $meeting = '—';
}
$price = $route['price'];
$priceStr = ($price !== null && $price !== '') ? number_format((float) $price, 0, '.', '') . ' ريال' : '—';
$minP = $route['min_participants'];
$maxP = $route['max_participants'];
$loc = (string) ($route['location'] ?? '');
$imgResolved = resolveRouteImageUrl(isset($route['image_url']) ? (string) $route['image_url'] : null);
$bookHref = routeBookingUrl($id);

$noteLine = 'رحلة رياضية فلكية وثقافية';
if ($loc !== '') {
    $noteLine .= '، جودة (' . $loc . ')';
}
$noteLine .= '، مناسبة للعوائل.';

$itineraryData = null;
$rawIt = $route['itinerary'] ?? null;
if (is_string($rawIt) && $rawIt !== '') {
    $itineraryData = json_decode($rawIt, true);
}

$mapPayload = [
    'center'       => [25.3843, 49.5877],
    'zoom'         => 11,
    'points'       => [],
    'followRoads'  => true,
];
$rawMap = $route['map_json'] ?? null;
if (is_string($rawMap) && trim($rawMap) !== '') {
    $decoded = json_decode($rawMap, true);
    if (is_array($decoded)) {
        if (isset($decoded['center']) && is_array($decoded['center'])) {
            $mapPayload['center'] = $decoded['center'];
        }
        if (isset($decoded['zoom'])) {
            $mapPayload['zoom'] = (int) $decoded['zoom'];
        }
        if (isset($decoded['points']) && is_array($decoded['points'])) {
            $mapPayload['points'] = $decoded['points'];
        }
        if (array_key_exists('followRoads', $decoded)) {
            $mapPayload['followRoads'] = (bool) $decoded['followRoads'];
        }
    }
}

$mapJsonForScript = json_encode($mapPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

require __DIR__ . '/includes/header.php';
?>

<article class="route-detail" id="route-top">
    <header class="route-detail__header">
        <h1 class="route-detail__title"><?php echo htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
        <?php if ($description !== ''): ?>
        <p class="route-detail__intro"><?php echo nl2br(htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?></p>
        <?php endif; ?>
        <?php if ($detailExtra !== ''): ?>
        <div class="route-detail__extra">
            <?php echo nl2br(htmlspecialchars($detailExtra, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?>
        </div>
        <?php endif; ?>
        <p class="route-detail__note"><strong>ملاحظة:</strong> <?php echo htmlspecialchars($noteLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    </header>

    <?php if ($imgResolved !== ''): ?>
    <div class="route-detail__fullimage route-detail__fullimage--top">
        <img src="<?php echo htmlspecialchars($imgResolved, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" loading="lazy" width="1200" height="675">
    </div>
    <?php endif; ?>

    <section class="route-detail__facts" aria-label="معلومات أساسية">
        <div class="fact-card">
            <span class="fact-card__label">التاريخ</span>
            <span class="fact-card__value"><?php echo htmlspecialchars($dateStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
        </div>
        <div class="fact-card">
            <span class="fact-card__label">الوقت</span>
            <span class="fact-card__value"><?php echo htmlspecialchars($timeStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
        </div>
        <div class="fact-card">
            <span class="fact-card__label">نقطة التجمع</span>
            <span class="fact-card__value"><?php echo htmlspecialchars($meeting, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
        </div>
        <div class="fact-card">
            <span class="fact-card__label">الرسوم</span>
            <span class="fact-card__value"><?php echo htmlspecialchars($priceStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
        </div>
    </section>

    <section class="route-detail__participants" aria-label="عدد المشاركين">
        <p class="route-detail__participants-text">
            أقل عدد: <strong><?php echo $minP !== null && $minP !== '' ? htmlspecialchars((string) $minP, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '—'; ?></strong>
            — أقصى عدد: <strong><?php echo $maxP !== null && $maxP !== '' ? htmlspecialchars((string) $maxP, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '—'; ?></strong>
        </p>
    </section>

    <section class="route-detail__mapsec" id="route-map-block" aria-label="خريطة الموقع">
        <h2 class="route-detail__h2">خريطة الموقع والمسار</h2>
        <p class="route-detail__map-lead">يُرسم خط متصل بين المحطات؛ يُحاول اتباع طرق المشي في الخريطة المفتوحة عند الإمكان، وإلا يُعرض خطاً مباشراً بين النقاط. اسحب الخريطة وقرّب كما تشاء.</p>
        <div id="route-map" class="route-map"></div>
        <script type="application/json" id="route-map-payload"><?php echo $mapJsonForScript; ?></script>
    </section>

    <?php if (is_array($itineraryData) && !empty($itineraryData)): ?>
    <section class="route-detail__itinerary" aria-label="خريطة المسار اليومية">
        <h2 class="route-detail__h2">خريطة المسار اليومية</h2>
        <?php if (isset($itineraryData['أيام']) && is_array($itineraryData['أيام'])): ?>
            <?php foreach ($itineraryData['أيام'] as $day): ?>
                <?php if (!is_array($day)) { continue; } ?>
                <div class="itinerary-day">
                    <h3 class="itinerary-day__title"><?php echo htmlspecialchars((string) ($day['اليوم'] ?? 'يوم'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h3>
                    <?php if (!empty($day['محطات']) && is_array($day['محطات'])): ?>
                    <ul class="itinerary-day__list">
                        <?php foreach ($day['محطات'] as $stop): ?>
                        <li><?php echo htmlspecialchars((string) $stop, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <pre class="route-detail__json"><?php echo htmlspecialchars(json_encode($itineraryData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></pre>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <footer class="route-detail__footer-actions">
        <a class="btn btn--primary btn--large" href="<?php echo htmlspecialchars($bookHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">احجز الآن</a>
        <a class="btn btn--muted btn--large" href="<?php echo htmlspecialchars(assetUrl('index.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">العودة للمسارات</a>
    </footer>
</article>

<?php
$extraJs = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'assets/js/route-map.js',
];
require __DIR__ . '/includes/footer.php';
