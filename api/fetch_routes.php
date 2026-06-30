<?php
/**
 * جلب بيانات المسارات من موقع الجمعية وتخزينها في قاعدة البيانات.
 * تحذير: يستبدل حقولاً عدة لكل مسار مطابق لـ source_url — لا يُفضّل للإنتاج.
 * المحتوى المرجعي المدروس يُدار عبر includes/route_seed_content.php ولوحة التعديل.
 * لا يوجد رابط لهذا الملف من لوحة التحكم؛ التشغيل يدوي أو CLI فقط إن رغبت.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
if (!$isCli) {
    session_start();
}
$isAdmin = !$isCli && !empty($_SESSION['atheer_admin']);

if (!$isCli && !$isAdmin) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>غير مسموح</title></head><body style="font-family:sans-serif;padding:2rem;"><p>يجب <a href="../admin/login.php">تسجيل الدخول</a> كمدير لتشغيل التحديث.</p></body></html>';
    exit;
}

$pdo = get_pdo();
atheerEnsureSchema($pdo);

/** @var array<int, array{url:string,type:string}> */
$routeSources = [
    ['url' => 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A3%D8%AB%D9%8A%D8%B1-2/', 'type' => 'فلكي'],
    ['url' => 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B1%D8%B2-%D8%A7%D9%84%D8%AD%D8%B3%D8%A7%D9%88%D9%8A/', 'type' => 'زراعي'],
    ['url' => 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A7%D8%AB%D9%8A%D8%B1/', 'type' => 'فلكي'],
    ['url' => 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D9%82%D8%A7%D8%B1%D8%A9/', 'type' => 'ثقافي'],
    ['url' => 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B7%D9%8A%D9%88%D8%B1-%D8%A7%D9%84%D9%85%D9%87%D8%A7%D8%AC%D8%B1%D8%A9/', 'type' => 'مهاجر'],
    ['url' => 'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%AB%D9%82%D8%A7%D9%81%D9%8A/', 'type' => 'ثقافي'],
    ['url' => 'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B2%D8%B1%D8%A7%D8%B9%D9%8A-%D8%B9%D9%8A%D9%86-%D8%B9%D9%84%D9%89-%D8%A7%D9%84%D8%A3%D8%AD%D8%B3%D8%A7%D8%A1-/', 'type' => 'زراعي'],
];

/**
 * جلب محتوى HTML من رابط
 */
function fetch_html_content(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; AtheerBot/1.0; +https://localhost/)',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $code >= 200 && $code < 400) {
            return $body;
        }
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 45,
            'header'  => "User-Agent: Mozilla/5.0 (compatible; AtheerBot/1.0)\r\n",
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);
    $html = @file_get_contents($url, false, $ctx);
    return $html !== false ? $html : null;
}

/**
 * تحويل أسماء الأشهر العربية إلى رقم
 *
 * @return array<string, int>
 */
function arabic_month_map(): array
{
    return [
        'يناير' => 1, 'فبراير' => 2, 'مارس' => 3, 'ابريل' => 4, 'أبريل' => 4, 'مايو' => 5,
        'يونيو' => 6, 'يوليو' => 7, 'أغسطس' => 8, 'سبتمبر' => 9, 'أكتوبر' => 10, 'نوفمبر' => 11, 'ديسمبر' => 12,
    ];
}

/**
 * استخراج تاريخ Y-m-d من نص عربي
 */
function extract_date_ymd(string $text): ?string
{
    if (preg_match('/(\d{1,2})\s*(يناير|فبراير|مارس|ابريل|أبريل|مايو|يونيو|يوليو|أغسطس|سبتمبر|أكتوبر|نوفمبر|ديسمبر)\s*(\d{4})/u', $text, $m)) {
        $d = (int) $m[1];
        $map = arabic_month_map();
        $month = $map[$m[2]] ?? null;
        $y = (int) $m[3];
        if ($month !== null && $d > 0 && $y > 2000) {
            return sprintf('%04d-%02d-%02d', $y, $month, min($d, 31));
        }
    }
    return null;
}

/**
 * تحويل أول وقت عثري في النص إلى TIME
 */
function extract_time_sql(string $text): ?string
{
    if (preg_match('/(\d{1,2}):(\d{2})\s*عصر/u', $text, $m)) {
        $h = (int) $m[1];
        if ($h < 12) {
            $h += 12;
        }
        return sprintf('%02d:%s:00', $h, $m[2]);
    }
    if (preg_match('/(\d{1,2}):(\d{2})\s*مساء/u', $text, $m)) {
        $h = (int) $m[1];
        if ($h < 12) {
            $h += 12;
        }
        return sprintf('%02d:%s:00', $h, $m[2]);
    }
    if (preg_match('/(\d{1,2}):(\d{2})\s*صباح/u', $text, $m)) {
        $h = (int) $m[1];
        if ($h === 12) {
            $h = 0;
        }
        return sprintf('%02d:%s:00', $h, $m[2]);
    }
    if (preg_match('/(\d{1,2}):(\d{2})/u', $text, $m)) {
        return sprintf('%02d:%s:00', (int) $m[1], $m[2]);
    }
    return null;
}

/**
 * استخراج سعر بالريال
 */
function extract_price(string $text): ?float
{
    if (preg_match('/(\d{1,5}(?:\.\d{1,2})?)\s*ريال/u', $text, $m)) {
        return (float) $m[1];
    }
    if (preg_match('/رسوم[^\d]{0,40}(\d{1,5})/u', $text, $m)) {
        return (float) $m[1];
    }
    return null;
}

/**
 * استخراج نقطة التجمع
 */
function extract_meeting_point(string $text): ?string
{
    if (preg_match('/نقطة التجمع\s*[：:]\s*(.+?)(?:\s*▪|احجز|برنامج|$)/us', $text, $m)) {
        $s = trim(preg_replace('/\s+/u', ' ', $m[1]));
        return $s !== '' ? mb_substr($s, 0, 500, 'UTF-8') : null;
    }
    return null;
}

/**
 * أقل/أقصى عدد مشاركين
 */
function extract_min_max(string $text): array
{
    $min = null;
    $max = null;
    if (preg_match('/أقل\s*عدد[^\d]{0,20}(\d+).*?أقصى\s*عدد[^\d]{0,20}(\d+)/us', $text, $m)) {
        $min = (int) $m[1];
        $max = (int) $m[2];
    } elseif (preg_match('/(\d+)\s*من\s*(\d+)/u', $text, $m)) {
        $max = (int) $m[2];
        $min = (int) $m[1];
    } elseif (preg_match('/المشتركين\s*(\d+)\s*من\s*(\d+)/u', $text, $m)) {
        $min = (int) $m[1];
        $max = (int) $m[2];
    }
    return [$min, $max];
}

/**
 * بناء JSON للمسار اليومي من أسطر مرقّمة أو فقرات
 *
 * @return string|null JSON
 */
function build_itinerary_json(string $longText): ?string
{
    $lines = preg_split('/\R+/u', $longText) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^[▪•\-\*]\s*(.+)$/u', $line, $m)) {
            $items[] = $m[1];
        } elseif (preg_match('/^[٠-٩0-9]+[\-–.)]\s*(.+)$/u', $line, $m)) {
            $items[] = $m[1];
        }
    }
    if ($items === []) {
        return null;
    }
    $payload = [
        'أيام' => [
            [
                'اليوم'   => 'برنامج الرحلة',
                'محطات'   => $items,
            ],
        ],
    ];
    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * استخراج وصف نظيف من DOM
 */
function extract_description(DOMXPath $xp, DOMNode $root): string
{
    $paras = $xp->query('.//p[string-length(normalize-space(.))>40]', $root);
    if ($paras !== false && $paras->length > 0) {
        $t = trim($paras->item(0)->textContent ?? '');
        if ($t !== '') {
            return preg_replace('/\s+/u', ' ', $t);
        }
    }
    $blocks = $xp->query('.//div[string-length(normalize-space(.))>80]', $root);
    if ($blocks !== false && $blocks->length > 0) {
        for ($i = 0; $i < $blocks->length; $i++) {
            $t = trim($blocks->item($i)->textContent ?? '');
            if (mb_strlen($t, 'UTF-8') > 80) {
                return preg_replace('/\s+/u', ' ', mb_substr($t, 0, 2000, 'UTF-8'));
            }
        }
    }
    return '';
}

/**
 * تحليل صفحة وإرجاع حقول المسار
 *
 * @return array<string, mixed>
 */
function parse_route_page(string $html, string $sourceUrl, string $defaultType): array
{
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $wrapped = '<?xml encoding="UTF-8">' . $html;
    $dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $xp = new DOMXPath($dom);

    $name = '';
    $h1 = $xp->query('//h1');
    if ($h1 !== false && $h1->length > 0) {
        $name = trim($h1->item(0)->textContent ?? '');
    }
    if ($name === '') {
        $og = $xp->query('//meta[@property="og:title"]/@content');
        if ($og !== false && $og->length > 0) {
            $name = trim($og->item(0)->nodeValue ?? '');
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $root = $body ?? $dom->documentElement;
    $fullText = $root ? trim(preg_replace('/\s+/u', ' ', $root->textContent ?? '')) : '';

    $description = '';
    if ($root !== null) {
        $description = extract_description($xp, $root);
    }
    if ($description === '' && $fullText !== '') {
        $description = mb_substr($fullText, 0, 1200, 'UTF-8');
    }

    $dateYmd = extract_date_ymd($fullText);
    $timeSql = extract_time_sql($fullText);
    $price = extract_price($fullText);
    $meeting = extract_meeting_point($fullText);
    [$minP, $maxP] = extract_min_max($fullText);

    $location = null;
    if (preg_match('/جودة\s*\(([^)]+)\)/u', $fullText, $m)) {
        $location = trim($m[1]);
    } elseif (preg_match('/في\s+([^\n،]{3,80})/u', $description, $m)) {
        $location = trim($m[1]);
    }

    $imageUrl = null;
    $ogImg = $xp->query('//meta[@property="og:image"]/@content');
    if ($ogImg !== false && $ogImg->length > 0) {
        $imageUrl = trim($ogImg->item(0)->nodeValue ?? '');
    }
    if ($imageUrl === null || $imageUrl === '') {
        foreach ($xp->query('//img[@src]') as $img) {
            if (!$img instanceof DOMElement) {
                continue;
            }
            $src = trim($img->getAttribute('src'));
            if ($src === '' || stripos($src, 'data:') === 0) {
                continue;
            }
            if (preg_match('/(logo|icon|avatar|spinner)/i', $src)) {
                continue;
            }
            $imageUrl = $src;
            break;
        }
    }
    if ($imageUrl !== null && $imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
        $parts = parse_url($sourceUrl);
        $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if ($imageUrl[0] === '/') {
            $imageUrl = $base . $imageUrl;
        } else {
            $path = dirname($parts['path'] ?? '/');
            $imageUrl = $base . rtrim($path, '/') . '/' . $imageUrl;
        }
    }

    $bookingUrl = null;
    $links = $xp->query('//a[@href]');
    if ($links !== false) {
        foreach ($links as $a) {
            if (!$a instanceof DOMElement) {
                continue;
            }
            $href = $a->getAttribute('href');
            if (stripos($href, 'checkout') !== false || mb_strpos($a->textContent ?? '', 'احجز', 0, 'UTF-8') !== false) {
                $bookingUrl = $href;
                break;
            }
        }
    }
    if ($bookingUrl !== null && $bookingUrl !== '' && !preg_match('#^https?://#i', $bookingUrl)) {
        $parts = parse_url($sourceUrl);
        $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if ($bookingUrl[0] === '/') {
            $bookingUrl = $base . $bookingUrl;
        } else {
            $bookingUrl = rtrim($sourceUrl, '/') . '/' . ltrim($bookingUrl, '/');
        }
    }

    $itinerary = build_itinerary_json($description !== '' ? $description : $fullText);

    $type = $defaultType;
    $fullLower = mb_strtolower($fullText . ' ' . $name, 'UTF-8');
    if (mb_strpos($fullLower, 'فلك', 0, 'UTF-8') !== false || mb_strpos($fullLower, 'نجم', 0, 'UTF-8') !== false) {
        $type = 'فلكي';
    } elseif (mb_strpos($fullLower, 'طيور', 0, 'UTF-8') !== false || mb_strpos($fullLower, 'مهاجر', 0, 'UTF-8') !== false) {
        $type = 'مهاجر';
    } elseif (mb_strpos($fullLower, 'زراع', 0, 'UTF-8') !== false || mb_strpos($fullLower, 'رز', 0, 'UTF-8') !== false) {
        $type = 'زراعي';
    } elseif (mb_strpos($fullLower, 'ثقاف', 0, 'UTF-8') !== false) {
        $type = 'ثقافي';
    }

    return [
        'name'              => $name !== '' ? $name : 'مسار بدون عنوان',
        'description'       => $description,
        'date'              => $dateYmd,
        'time'              => $timeSql,
        'meeting_point'     => $meeting,
        'price'             => $price,
        'min_participants'  => $minP,
        'max_participants'  => $maxP,
        'location'          => $location,
        'itinerary'         => $itinerary,
        'image_url'         => $imageUrl,
        'source_url'        => $sourceUrl,
        'booking_url'       => $bookingUrl,
        'type'              => $type,
    ];
}

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>تقرير جلب المسارات</title>';
echo '<style>body{font-family:Segoe UI,Tahoma,sans-serif;background:#f8f9fa;color:#1a1a1a;padding:1.5rem;line-height:1.6;} .ok{color:#1a6b3a;} .fail{color:#a33;} code{background:#eee;padding:2px 6px;border-radius:4px;}</style>';
echo '</head><body>';
echo '<h1>تقرير جلب بيانات المسارات</h1>';
echo '<p>وقت التشغيل: <code>' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></p>';
echo '<ul>';

$insertSql = 'INSERT INTO routes (
    name, description, detail_extra, date, time, meeting_point, price, min_participants, max_participants,
    location, itinerary, map_json, image_url, source_url, booking_url, type, is_active
) VALUES (
    :name, :description, NULL, :date, :time, :meeting_point, :price, :minp, :maxp,
    :location, :itinerary, NULL, :image_url, :source_url, :booking_url, :type, 1
)';

$updateSql = 'UPDATE routes SET
    name = :name, description = :description, date = :date, time = :time, meeting_point = :meeting_point,
    price = :price, min_participants = :minp, max_participants = :maxp, location = :location,
    itinerary = :itinerary, image_url = :image_url, booking_url = :booking_url, type = :type
    WHERE source_url = :source_url';

foreach ($routeSources as $item) {
    $url = $item['url'];
    $typeDefault = $item['type'];
    echo '<li><strong>المصدر:</strong> <code>' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code><br>';

    $html = fetch_html_content($url);
    if ($html === null || $html === '') {
        echo '<span class="fail">فشل: تعذّر تحميل الصفحة.</span></li>';
        continue;
    }

    $htmlUtf8 = $html;
    if (!preg_match('//u', $html)) {
        $htmlUtf8 = mb_convert_encoding($html, 'UTF-8', 'UTF-8, Windows-1256, ISO-8859-1');
    }

    $data = parse_route_page($htmlUtf8, $url, $typeDefault);

    $params = [
        'name'         => $data['name'],
        'description'  => $data['description'],
        'date'         => $data['date'],
        'time'         => $data['time'],
        'meeting_point'=> $data['meeting_point'],
        'price'        => $data['price'],
        'minp'         => $data['min_participants'],
        'maxp'         => $data['max_participants'],
        'location'     => $data['location'],
        'itinerary'    => $data['itinerary'],
        'image_url'    => $data['image_url'],
        'source_url'   => $data['source_url'],
        'booking_url'  => $data['booking_url'],
        'type'         => $data['type'],
    ];

    try {
        $check = $pdo->prepare('SELECT id FROM routes WHERE source_url = :u LIMIT 1');
        $check->execute(['u' => $url]);
        $exists = $check->fetch();

        if ($exists) {
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);
            echo '<span class="ok">تم التحديث بنجاح — المعرف: ' . (int) $exists['id'] . ' — ' . htmlspecialchars($data['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        } else {
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute($params);
            echo '<span class="ok">تم الإدراج بنجاح — ' . htmlspecialchars($data['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }
    } catch (Throwable $e) {
        echo '<span class="fail">خطأ في قاعدة البيانات: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
    }

    echo '</li>';
}

echo '</ul>';
echo '<p><a href="../admin/index.php">العودة إلى لوحة التحكم</a></p>';
echo '</body></html>';
