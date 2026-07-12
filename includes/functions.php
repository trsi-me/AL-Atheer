<?php
/**
 * دوال مساعدة — مشروع الأثير
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/booking.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/mail.php';

function siteDisplayName(): string
{
    return 'جمعية المشي والجري بالأحساء';
}

function atheerEnsureSiteBranding(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        atheerEnsureSchema($pdo);
        $title = siteDisplayName();
        $sql = "INSERT INTO settings (key_name, key_value) VALUES ('site_title', :title)
            ON DUPLICATE KEY UPDATE key_value = IF(key_value = 'الأثير', :title2, key_value)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['title' => $title, 'title2' => $title]);
    } catch (Throwable $e) {
    }
}
function atheerEnsureSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $try = static function (string $sql) use ($pdo): void {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate column') === false && stripos($msg, 'duplicate column name') === false) {
                throw $e;
            }
        }
    };
    $try('ALTER TABLE routes ADD COLUMN map_json TEXT NULL');
    $try('ALTER TABLE routes ADD COLUMN detail_extra TEXT NULL');
}

/**
 * تصحيح مسارات الصور القديمة الخاطئة إلى أسماء ملفاتك الأصلية
 */
function atheerFixLegacyImagePaths(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("UPDATE routes SET image_url = 'assets/images/الطيور المهاجرة.jpeg' WHERE image_url IN ('assets/images/tayoor.jpg','tayoor.jpg')");
        $pdo->exec("UPDATE routes SET image_url = 'assets/images/المسار الزراعي.jpg' WHERE image_url IN ('assets/images/ziraee.jpg','ziraee.jpg')");
    } catch (Throwable $e) {
        // تجاهل إن تعذّر التحديث
    }
}

/**
 * إرفاق بيانات خريطة افتراضية لكل مسار حسب رابط المصدر
 *
 * @param callable(array, ?array, int): string $mkMap
 */
function atheer_attach_route_maps(array $templates, callable $mkMap): array
{
    $urls = [
        'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A3%D8%AB%D9%8A%D8%B1-2/' => $mkMap([
            ['lat' => 25.452, 'lng' => 49.512, 'label' => 'منطقة جودة — انطلاق المسار'],
            ['lat' => 25.438, 'lng' => 49.528, 'label' => 'محطة مشاهدة سماء'],
        ], [25.445, 49.52], 11),
        'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B1%D8%B2-%D8%A7%D9%84%D8%AD%D8%B3%D8%A7%D9%88%D9%8A/' => $mkMap([
            ['lat' => 25.36, 'lng' => 49.62, 'label' => 'محطة — المزارع'],
            ['lat' => 25.355, 'lng' => 49.615, 'label' => 'مسار زراعي'],
        ], [25.358, 49.618], 12),
        'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A7%D8%AB%D9%8A%D8%B1/' => $mkMap([
            ['lat' => 25.44, 'lng' => 49.53, 'label' => 'نقطة التجمع'],
            ['lat' => 25.432, 'lng' => 49.54, 'label' => 'موقع المشاهدة'],
        ], [25.436, 49.535], 11),
        'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D9%82%D8%A7%D8%B1%D8%A9/' => $mkMap([
            ['lat' => 25.33, 'lng' => 49.59, 'label' => 'انطلاق المسار — القارة'],
            ['lat' => 25.328, 'lng' => 49.585, 'label' => 'محطة معلم'],
        ], [25.329, 49.588], 13),
        'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B7%D9%8A%D9%88%D8%B1-%D8%A7%D9%84%D9%85%D9%87%D8%A7%D8%AC%D8%B1%D8%A9/' => $mkMap([
            ['lat' => 25.41, 'lng' => 49.55, 'label' => 'نقطة التجمع'],
            ['lat' => 25.398, 'lng' => 49.562, 'label' => 'بحيرة الأصفر — مشاهدة'],
            ['lat' => 25.39, 'lng' => 49.57, 'label' => 'مسار المتابعة'],
        ], [25.4, 49.56], 11),
        'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%AB%D9%82%D8%A7%D9%81%D9%8A/' => $mkMap([
            ['lat' => 25.38, 'lng' => 49.6, 'label' => 'انطلاق ثقافي'],
            ['lat' => 25.375, 'lng' => 49.595, 'label' => 'محطة تراثية'],
        ], [25.378, 49.598], 12),
        'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B2%D8%B1%D8%A7%D8%B9%D9%8A-%D8%B9%D9%8A%D9%86-%D8%B9%D9%84%D9%89-%D8%A7%D9%84%D8%A3%D8%AD%D8%B3%D8%A7%D8%A1-/' => $mkMap([
            ['lat' => 25.35, 'lng' => 49.63, 'label' => 'نقطة التجمع'],
            ['lat' => 25.342, 'lng' => 49.625, 'label' => 'مزارع الجفر'],
            ['lat' => 25.336, 'lng' => 49.62, 'label' => 'ختام المسار'],
        ], [25.343, 49.625], 12),
    ];
    foreach ($templates as &$t) {
        $u = $t['source_url'];
        if (isset($urls[$u])) {
            $t['map_json'] = $urls[$u];
        }
    }
    unset($t);

    return $templates;
}

/**
 * تطبيق محتوى الصفحات المرجعي لمرة واحدة على قاعدة موجودة مسبقاً
 * (مفتاح الإعداد يُرفع عند الحاجة لإعادة الدمج من route_seed_content.php بعد أخطاء مثل جلب خارجي)
 */
function atheerApplyReferenceContentV2(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $chk = $pdo->prepare('SELECT key_value FROM settings WHERE key_name = :k LIMIT 1');
        $chk->execute(['k' => 'atheer_route_ref_v5']);
        $v = $chk->fetchColumn();
        if ($v === '1') {
            return;
        }
    } catch (Throwable $e) {
        // متابعة التحديث إن تعذّر قراءة الإعدادات
    }

    $mkMap = static function (array $points, ?array $center = null, int $zoom = 11): string {
        $c = $center ?? [25.3843, 49.5877];
        return json_encode([
            'center' => $c,
            'zoom'   => $zoom,
            'points' => $points,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    };

    $defaults = atheer_attach_route_maps(require __DIR__ . '/route_seed_content.php', $mkMap);

    $sql = 'UPDATE routes SET
        name = :name, description = :description, detail_extra = :detail_extra,
        `date` = :date, `time` = :time, meeting_point = :meeting_point,
        price = :price, min_participants = :minp, max_participants = :maxp,
        location = :location, itinerary = :itinerary, image_url = :image_url,
        type = :type, map_json = :map_json
        WHERE source_url = :source_url';

    $stmt = $pdo->prepare($sql);
    foreach ($defaults as $row) {
        try {
            $stmt->execute([
                'name'          => $row['name'],
                'description'   => $row['description'],
                'detail_extra'  => $row['detail_extra'],
                'date'          => $row['date'] ?? null,
                'time'          => $row['time'] ?? null,
                'meeting_point' => $row['meeting_point'] ?? null,
                'price'         => $row['price'] ?? null,
                'minp'          => $row['min_participants'] ?? null,
                'maxp'          => $row['max_participants'] ?? null,
                'location'      => $row['location'],
                'itinerary'     => $row['itinerary'] ?? null,
                'image_url'     => $row['image_url'],
                'type'          => $row['type'],
                'map_json'      => $row['map_json'],
                'source_url'    => $row['source_url'],
            ]);
        } catch (Throwable $e) {
            // تجاهل صف لا يطابق المصدر
        }
    }

    try {
        $pdo->exec("INSERT INTO settings (key_name, key_value) VALUES ('atheer_route_ref_v5', '1') ON DUPLICATE KEY UPDATE key_value = '1'");
    } catch (Throwable $e) {
        // تجاهل
    }
}

/**
 * تحويل مسار الصورة المخزّن إلى رابط كامل (روابط مطلقة أو ملفات داخل المشروع)
 */
function resolveRouteImageUrl(?string $url): string
{
    if ($url === null) {
        return '';
    }
    $u = trim($url);
    if ($u === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $u)) {
        return $u;
    }
    return assetUrl(ltrim($u, '/'));
}

/**
 * إدراج المسارات الافتراضية السبعة عندما يكون جدول المسارات فارغاً تماماً
 */
function ensureDefaultRoutesSeeded(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    atheerEnsureSchema($pdo);

    $count = (int) $pdo->query('SELECT COUNT(*) FROM routes')->fetchColumn();
    if ($count > 0) {
        $done = true;
        return;
    }

    $mkMap = static function (array $points, ?array $center = null, int $zoom = 11): string {
        $c = $center ?? [25.3843, 49.5877];
        return json_encode([
            'center' => $c,
            'zoom'   => $zoom,
            'points' => $points,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    };

    $defaults = atheer_attach_route_maps(require __DIR__ . '/route_seed_content.php', $mkMap);

    $sql = 'INSERT INTO routes (
        name, description, detail_extra, date, time, meeting_point, price, min_participants, max_participants,
        location, itinerary, image_url, source_url, booking_url, type, map_json, is_active
    ) VALUES (
        :name, :description, :detail_extra, :date, :time, :meeting_point, :price, :minp, :maxp,
        :location, :itinerary, :image_url, :source_url, :booking_url, :type, :map_json, 1
    )';

    $stmt = $pdo->prepare($sql);
    foreach ($defaults as $row) {
        $src = $row['source_url'];
        $booking = rtrim($src, '/') . '/checkout/';
        $stmt->execute([
            'name'          => $row['name'],
            'description'   => $row['description'],
            'detail_extra'  => $row['detail_extra'],
            'date'          => $row['date'] ?? null,
            'time'          => $row['time'] ?? null,
            'meeting_point' => $row['meeting_point'] ?? null,
            'price'         => $row['price'] ?? null,
            'minp'          => $row['min_participants'] ?? null,
            'maxp'          => $row['max_participants'] ?? null,
            'location'      => $row['location'],
            'itinerary'     => $row['itinerary'] ?? null,
            'image_url'     => $row['image_url'],
            'source_url'    => $src,
            'booking_url'   => $booking,
            'type'          => $row['type'],
            'map_json'      => $row['map_json'],
        ]);
    }
    $done = true;
}

/**
 * جلب جميع المسارات النشطة مرتبة حسب التاريخ ثم الاسم
 *
 * @return array<int, array<string, mixed>>
 */
function getAllRoutes(): array
{
    $pdo = get_pdo();
    atheerEnsureSchema($pdo);
    atheerEnsureSiteBranding($pdo);
    ensureDefaultRoutesSeeded($pdo);
    atheerFixLegacyImagePaths($pdo);
    atheerApplyReferenceContentV2($pdo);
    $sql = 'SELECT * FROM routes WHERE is_active = 1 ORDER BY `date` IS NULL, `date` ASC, `name` ASC';
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * جلب مسار بالمعرف أو null
 *
 * @return array<string, mixed>|null
 */
function getRouteById(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $pdo = get_pdo();
    atheerEnsureSchema($pdo);
    atheerEnsureSiteBranding($pdo);
    ensureDefaultRoutesSeeded($pdo);
    atheerFixLegacyImagePaths($pdo);
    atheerApplyReferenceContentV2($pdo);
    $stmt = $pdo->prepare('SELECT * FROM routes WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * تنسيق التاريخ للعرض بالعربية (تقريبي)
 */
function formatDate(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
    $w = (int) date('w', $ts);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts);
    $monthName = $months[$m] ?? date('F', $ts);
    return $days[$w] . '، ' . $d . ' ' . $monthName . ' ' . $y;
}

/**
 * تنسيق الوقت للعرض (من TIME أو نص مخزّن مؤقتاً)
 */
function formatTime(?string $time): string
{
    if ($time === null || $time === '') {
        return '—';
    }
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $m)) {
        $h = (int) $m[1];
        $min = $m[2];
        $suffix = $h >= 12 ? 'مساءً' : 'صباحاً';
        $h12 = $h % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }
        return $h12 . ':' . $min . ' ' . $suffix;
    }
    return $time;
}

/**
 * اقتطاع النص مع إضافة نقاط
 */
function truncateText(?string $text, int $length = 120): string
{
    if ($text === null || $text === '') {
        return '';
    }
    $text = preg_replace('/\s+/u', ' ', trim($text));
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length, 'UTF-8') . '…';
}

/**
 * المسار الأساسي للموقع (مثل ‎/AL-Atheer‎) بدون شرطة نهائية
 */
function siteBase(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($script));
    while (in_array(basename($dir), ['admin', 'api'], true)) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '.' || $dir === '') {
        return '';
    }
    return rtrim($dir, '/');
}

/**
 * رابط مطلق من جذر الخادم لملف في المشروع
 * يُفصَل الاستعلام (?...) والجزء (#...) قبل ترميز أجزاء المسار حتى لا يُحوَّل ? إلى %3F داخل اسم الملف.
 */
function assetUrl(string $path): string
{
    $fragment = '';
    $hashPos = strpos($path, '#');
    if ($hashPos !== false) {
        $fragment = substr($path, $hashPos);
        $path = substr($path, 0, $hashPos);
    }
    $query = '';
    $qPos = strpos($path, '?');
    if ($qPos !== false) {
        $query = substr($path, $qPos);
        $path = substr($path, 0, $qPos);
    }
    $path = ltrim($path, '/');
    $parts = array_values(array_filter(explode('/', $path), static function ($s): bool {
        return $s !== '';
    }));
    $encoded = array_map(static function (string $segment): string {
        return rawurlencode($segment);
    }, $parts);
    $path = implode('/', $encoded);
    $base = siteBase();
    return ($base !== '' ? $base . '/' : '/') . $path . $query . $fragment;
}

function assetBuildVersion(): string
{
    return '4';
}

function assetStaticUrl(string $path): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (!preg_match('#\.(css|js|png|jpe?g|gif|webp|svg|woff2?)$#i', $path)) {
        return assetUrl($path);
    }
    $url = assetUrl($path);
    $sep = strpos($url, '?') !== false ? '&' : '?';
    return $url . $sep . 'v=' . assetBuildVersion();
}

/**
 * نوع المسار للعرض في الشارة
 */
function routeTypeLabel(string $type): string
{
    $map = [
        'رياضي' => 'رياضي',
        'فلكي'  => 'فلكي',
        'ثقافي' => 'ثقافي',
        'زراعي' => 'زراعي',
        'مهاجر' => 'مهاجر',
    ];
    return $map[$type] ?? $type;
}
