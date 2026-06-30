<?php

declare(strict_types=1);

$databaseLocal = __DIR__ . '/database.local.php';

if (!is_file($databaseLocal)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>إعداد ناقص</title></head><body style="font-family:sans-serif;padding:2rem;">';
    echo '<h1>لم يُضبط ملف قاعدة البيانات</h1>';
    echo '<p>انسخ <code>config/database.example.php</code> إلى <code>config/database.local.php</code> وعدّل بيانات الاتصال.</p>';
    echo '</body></html>';
    exit;
}

require $databaseLocal;

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_CHARSET')) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>إعداد خاطئ</title></head><body style="font-family:sans-serif;padding:2rem;">';
    echo '<h1>ملف قاعدة البيانات غير مكتمل</h1>';
    echo '<p>تحقق من تعريف جميع ثوابت الاتصال داخل <code>config/database.local.php</code>.</p>';
    echo '</body></html>';
    exit;
}

function get_pdo(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>خطأ في الاتصال</title></head><body style="font-family:sans-serif;padding:2rem;">';
        echo '<h1>تعذّر الاتصال بقاعدة البيانات</h1>';
        echo '<p>تحقق من إعدادات الملف <code>config/database.local.php</code> وتأكد من تشغيل خادم MySQL وإنشاء قاعدة البيانات.</p>';
        echo '<p style="color:#666;">تفاصيل تقنية (للمطور): ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        echo '</body></html>';
        exit;
    }
}
