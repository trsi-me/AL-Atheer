<?php
/**
 * تعديل بيانات مسار يدوياً
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
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM routes WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$route = $stmt->fetch();

if (!$route) {
    http_response_code(404);
    $pageTitle = 'غير موجود';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<p>المسار غير موجود.</p><p><a href="index.php">العودة</a></p>';
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$message = '';
$error = '';

$types = ['رياضي', 'فلكي', 'ثقافي', 'زراعي', 'مهاجر'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = (string) ($_POST['description'] ?? '');
    $detail_extra = (string) ($_POST['detail_extra'] ?? '');
    $date = trim((string) ($_POST['date'] ?? ''));
    $time = trim((string) ($_POST['time'] ?? ''));
    $meeting_point = trim((string) ($_POST['meeting_point'] ?? ''));
    $priceRaw = trim((string) ($_POST['price'] ?? ''));
    $minp = trim((string) ($_POST['min_participants'] ?? ''));
    $maxp = trim((string) ($_POST['max_participants'] ?? ''));
    $location = (string) ($_POST['location'] ?? '');
    $itinerary = (string) ($_POST['itinerary'] ?? '');
    $map_json = trim((string) ($_POST['map_json'] ?? ''));
    $image_url = trim((string) ($_POST['image_url'] ?? ''));
    $source_url = trim((string) ($_POST['source_url'] ?? ''));
    $booking_url = trim((string) ($_POST['booking_url'] ?? ''));
    $type = (string) ($_POST['type'] ?? 'رياضي');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        $error = 'اسم المسار مطلوب.';
    } elseif ($source_url === '') {
        $error = 'رابط المصدر مطلوب.';
    } elseif (!in_array($type, $types, true)) {
        $error = 'نوع المسار غير صالح.';
    } elseif ($map_json !== '') {
        json_decode($map_json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'حقل خريطة المواقع يجب أن يكون JSON صالحاً.';
        }
    }

    if ($error === '') {
        $dateVal = $date === '' ? null : $date;
        $timeVal = $time === '' ? null : $time;
        $priceVal = $priceRaw === '' ? null : (float) $priceRaw;
        $minVal = $minp === '' ? null : (int) $minp;
        $maxVal = $maxp === '' ? null : (int) $maxp;
        $mapVal = $map_json === '' ? null : $map_json;

        $sql = 'UPDATE routes SET
            name = :name, description = :description, detail_extra = :detail_extra, date = :date, time = :time,
            meeting_point = :meeting_point, price = :price, min_participants = :minp, max_participants = :maxp,
            location = :location, itinerary = :itinerary, map_json = :map_json, image_url = :image_url, source_url = :source_url,
            booking_url = :booking_url, type = :type, is_active = :is_active
            WHERE id = :id';

        try {
            $up = $pdo->prepare($sql);
            $up->execute([
                'name'          => $name,
                'description'   => $description === '' ? null : $description,
                'detail_extra'    => $detail_extra === '' ? null : $detail_extra,
                'date'          => $dateVal,
                'time'          => $timeVal,
                'meeting_point' => $meeting_point === '' ? null : $meeting_point,
                'price'         => $priceVal,
                'minp'          => $minVal,
                'maxp'          => $maxVal,
                'location'      => $location === '' ? null : $location,
                'itinerary'     => $itinerary === '' ? null : $itinerary,
                'map_json'      => $mapVal,
                'image_url'     => $image_url === '' ? null : $image_url,
                'source_url'    => $source_url,
                'booking_url'   => $booking_url === '' ? null : $booking_url,
                'type'          => $type,
                'is_active'     => $is_active,
                'id'            => $id,
            ]);
            $message = 'تم حفظ التعديلات بنجاح.';
            $stmt->execute(['id' => $id]);
            $route = $stmt->fetch();
        } catch (Throwable $e) {
            $error = 'تعذّر الحفظ: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'تعديل مسار';
$bodyClass = 'page-admin';
$extraHeadLinks = [
    ['rel' => 'stylesheet', 'href' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'],
];
$extraCss = ['assets/css/admin-map.css'];

require dirname(__DIR__) . '/includes/header.php';

function v($r, string $k): string
{
    $x = $r[$k] ?? '';
    return htmlspecialchars((string) $x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$defaultMapJson = '{"center":[25.3843,49.5877],"zoom":11,"points":[],"followRoads":true}';
$mapField = isset($route['map_json']) && is_string($route['map_json']) && trim($route['map_json']) !== ''
    ? (string) $route['map_json']
    : $defaultMapJson;
?>

<section class="admin-edit">
    <h1 class="admin-edit__title">تعديل المسار</h1>
    <?php if ($message !== ''): ?>
        <p class="admin-edit__ok" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="admin-edit__err" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form class="admin-form" method="post" action="">
        <label>اسم المسار
            <input type="text" name="name" required value="<?php echo v($route, 'name'); ?>">
        </label>
        <label>الوصف المختصر
            <textarea name="description" rows="5"><?php echo v($route, 'description'); ?></textarea>
        </label>
        <label>تفاصيل إضافية (قوائم ونقاط)
            <textarea name="detail_extra" rows="6" placeholder="سطر لكل نقطة أو فقرة"><?php echo v($route, 'detail_extra'); ?></textarea>
        </label>
        <label>التاريخ (YYYY-MM-DD)
            <input type="text" name="date" placeholder="2025-04-25" value="<?php echo v($route, 'date'); ?>">
        </label>
        <label>الوقت (HH:MM:SS)
            <input type="text" name="time" placeholder="15:00:00" value="<?php echo v($route, 'time'); ?>">
        </label>
        <label>نقطة التجمع
            <input type="text" name="meeting_point" value="<?php echo v($route, 'meeting_point'); ?>">
        </label>
        <label>الرسوم (ريال)
            <input type="text" name="price" value="<?php echo v($route, 'price'); ?>">
        </label>
        <label>الحد الأدنى للمشاركين
            <input type="number" name="min_participants" value="<?php echo v($route, 'min_participants'); ?>">
        </label>
        <label>الحد الأقصى للمشاركين
            <input type="number" name="max_participants" value="<?php echo v($route, 'max_participants'); ?>">
        </label>
        <label>المنطقة الجغرافية
            <textarea name="location" rows="2"><?php echo v($route, 'location'); ?></textarea>
        </label>
        <label>خريطة المسار اليومية (JSON)
            <textarea name="itinerary" rows="6" placeholder='{"أيام":[{"اليوم":"...","محطات":["..."]}]}'><?php echo v($route, 'itinerary'); ?></textarea>
        </label>
        <label>رابط الصورة (أو مسار داخل المشروع مثل assets/images/photo.jpg)
            <input type="text" name="image_url" value="<?php echo v($route, 'image_url'); ?>">
        </label>
        <label>رابط المصدر
            <input type="url" name="source_url" required value="<?php echo v($route, 'source_url'); ?>">
        </label>
        <label>رابط الحجز
            <input type="url" name="booking_url" value="<?php echo v($route, 'booking_url'); ?>">
        </label>
        <label>نوع المسار
            <select name="type">
            <?php foreach ($types as $t): ?>
                <option value="<?php echo htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"<?php echo ($route['type'] === $t) ? ' selected' : ''; ?>><?php echo htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></option>
            <?php endforeach; ?>
            </select>
        </label>

        <div class="admin-map-block">
            <h2 class="admin-map-block__title">خريطة المواقع (OpenStreetMap)</h2>
            <p class="admin-map-block__hint">فعّل «إضافة نقطة» ثم انقر على الخريطة بالترتيب (من الانطلاق إلى الوصول). يظهر خط متصل بين النقاط. في صفحة المسار يُحاول الخط اتباع طرق المشي عبر الخريطة المفتوحة؛ لإجبار خط مستقيم فقط ضع في JSON: <code>"followRoads": false</code>. اسحب العلامات لتعديل الموقع.</p>
            <div class="admin-map-block__tools">
                <button type="button" class="btn btn--secondary" id="admin-map-add-mode">إضافة نقطة بالنقر على الخريطة</button>
                <button type="button" class="btn btn--muted" id="admin-map-fit">ملاءمة النقاط في الإطار</button>
            </div>
            <div id="admin-route-map" class="admin-route-map" role="application" aria-label="خريطة تعديل المواقع"></div>
            <label>بيانات الخريطة (JSON)
                <textarea name="map_json" id="map_json" rows="12"><?php echo htmlspecialchars($mapField, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
            </label>
        </div>

        <label class="admin-form__check">
            <input type="checkbox" name="is_active" value="1"<?php echo !empty($route['is_active']) ? ' checked' : ''; ?>> مسار نشط
        </label>
        <div class="admin-form__actions">
            <button class="btn btn--primary" type="submit">حفظ</button>
            <a class="btn btn--secondary" href="index.php">إلغاء</a>
        </div>
    </form>
</section>

<?php
$extraJs = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'assets/js/admin-route-map.js',
];
require dirname(__DIR__) . '/includes/footer.php';
