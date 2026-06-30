<?php

declare(strict_types=1);

$authLocal = __DIR__ . '/auth.local.php';

if (!is_file($authLocal)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>إعداد ناقص</title></head><body style="font-family:sans-serif;padding:2rem;">';
    echo '<h1>لم يُضبط ملف بيانات المدير</h1>';
    echo '<p>انسخ <code>config/auth.example.php</code> إلى <code>config/auth.local.php</code> وعدّل اسم المستخدم وكلمة المرور.</p>';
    echo '</body></html>';
    exit;
}

require $authLocal;

if (!defined('ADMIN_USER') || !defined('ADMIN_PASS')) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>إعداد خاطئ</title></head><body style="font-family:sans-serif;padding:2rem;">';
    echo '<h1>ملف بيانات المدير غير مكتمل</h1>';
    echo '<p>يجب تعريف <code>ADMIN_USER</code> و<code>ADMIN_PASS</code> داخل <code>config/auth.local.php</code>.</p>';
    echo '</body></html>';
    exit;
}
