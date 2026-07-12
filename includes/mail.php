<?php

declare(strict_types=1);

function mailFromAddress(): string
{
    if (defined('MAIL_FROM') && is_string(MAIL_FROM) && MAIL_FROM !== '') {
        return MAIL_FROM;
    }
    return 'noreply@' . (isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', (string) $_SERVER['HTTP_HOST']) : 'localhost');
}

function mailFromName(): string
{
    return siteDisplayName();
}

function bookingBarcodeImageUrl(string $payload): string
{
    return 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' . rawurlencode($payload) . '&scale=2&height=12&includetext';
}

function buildBookingConfirmationEmail(array $order): array
{
    $orderRef = (string) $order['order_ref'];
    $name = (string) $order['full_name'];
    $phone = (string) $order['phone'];
    $civilId = (string) ($order['civil_id'] ?? '');
    $method = paymentMethodLabel((string) $order['payment_method']);
    $total = formatMoney((float) $order['total_amount']);
    $barcodeUrl = bookingBarcodeImageUrl($orderRef);
    $site = mailFromName();

    $itemsText = '';
    $itemsHtml = '';
    foreach ($order['items'] as $item) {
        $routeName = (string) $item['route_name'];
        $participants = (int) $item['participants'];
        $lineTotal = formatMoney((float) $item['total_amount']);
        $itemsText .= "- {$routeName} | {$participants} مشارك | {$lineTotal}\n";
        $itemsHtml .= '<li style="margin:0 0 8px;"><strong>' . htmlspecialchars($routeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</strong> — ' . $participants . ' مشارك — '
            . htmlspecialchars($lineTotal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
    }

    $subject = 'تأكيد الحجز — ' . $orderRef;

    $text = "تم تأكيد حجزك بنجاح\n\n"
        . "رقم الطلب: {$orderRef}\n"
        . "الاسم: {$name}\n"
        . "الجوال: {$phone}\n"
        . ($civilId !== '' ? "السجل المدني: {$civilId}\n" : '')
        . "طريقة الدفع: {$method}\n"
        . "المبلغ: {$total}\n\n"
        . "المسارات:\n{$itemsText}\n"
        . "احتفظ برقم الطلب والباركود عند نقطة التجمع.\n"
        . $site;

    $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>'
        . htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</title></head><body style="font-family:Tahoma,Arial,sans-serif;background:#f5f7fa;margin:0;padding:24px;color:#1a2b3c;">'
        . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e2e8f0;">'
        . '<h1 style="margin:0 0 8px;font-size:22px;color:#0b5cab;">تم تأكيد حجزك</h1>'
        . '<p style="margin:0 0 20px;color:#4a5d6e;">شكراً لك — تم تسجيل طلبك لدى ' . htmlspecialchars($site, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:14px;">'
        . '<tr><td style="padding:6px 0;color:#6b7c8c;">رقم الطلب</td><td style="padding:6px 0;font-weight:700;">' . htmlspecialchars($orderRef, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#6b7c8c;">الاسم</td><td style="padding:6px 0;">' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#6b7c8c;">الجوال</td><td style="padding:6px 0;">' . htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . ($civilId !== '' ? '<tr><td style="padding:6px 0;color:#6b7c8c;">السجل المدني</td><td style="padding:6px 0;">' . htmlspecialchars($civilId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>' : '')
        . '<tr><td style="padding:6px 0;color:#6b7c8c;">طريقة الدفع</td><td style="padding:6px 0;">' . htmlspecialchars($method, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#6b7c8c;">المبلغ</td><td style="padding:6px 0;">' . htmlspecialchars($total, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '</table>'
        . '<h2 style="margin:0 0 10px;font-size:16px;">المسارات المحجوزة</h2>'
        . '<ul style="margin:0 0 24px;padding:0 18px 0 0;">' . $itemsHtml . '</ul>'
        . '<div style="text-align:center;padding:16px;background:#f0f6fc;border-radius:10px;">'
        . '<p style="margin:0 0 12px;font-size:13px;color:#4a5d6e;">باركود تفاصيل الحجز — اعرضه عند نقطة التجمع</p>'
        . '<img src="' . htmlspecialchars($barcodeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="باركود الحجز ' . htmlspecialchars($orderRef, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" width="280" height="80" style="max-width:100%;height:auto;">'
        . '</div>'
        . '<p style="margin:20px 0 0;font-size:12px;color:#8a9aab;">رسالة تلقائية من ' . htmlspecialchars($site, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
        . '</div></body></html>';

    return [
        'subject' => $subject,
        'text' => $text,
        'html' => $html,
    ];
}

function sendMailMessage(string $to, string $subject, string $text, string $html): bool
{
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (defined('RESEND_API_KEY') && is_string(RESEND_API_KEY) && RESEND_API_KEY !== '') {
        return sendMailViaResend($to, $subject, $text, $html);
    }

    $from = mailFromAddress();
    $fromName = mailFromName();
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: AL-Atheer';

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $encodedSubject, $html, implode("\r\n", $headers));
}

function sendMailViaResend(string $to, string $subject, string $text, string $html): bool
{
    $payload = [
        'from' => mailFromName() . ' <' . mailFromAddress() . '>',
        'to' => [$to],
        'subject' => $subject,
        'text' => $text,
        'html' => $html,
    ];
    $ch = curl_init('https://api.resend.com/emails');
    if ($ch === false) {
        return false;
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 300) {
        return false;
    }
    return true;
}

function sendOrderConfirmationEmail(array $order): bool
{
    $email = trim((string) ($order['email'] ?? ''));
    if ($email === '') {
        return false;
    }
    $built = buildBookingConfirmationEmail($order);
    return sendMailMessage($email, $built['subject'], $built['text'], $built['html']);
}
