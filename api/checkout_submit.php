<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'الطريقة غير مسموحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__) . '/includes/functions.php';

try {
    $lines = cartGetLines();
    if (count($lines) === 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'سلة التسوق فارغة.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $validated = validateCheckoutInput($_POST);
    if (count($validated['errors']) > 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => implode(' ', $validated['errors'])], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = get_pdo();
    $result = createOrderFromCart($pdo, $lines, $validated['data']);
    cartClear();

    echo json_encode([
        'ok' => true,
        'message' => 'تم تأكيد الطلب والدفع بنجاح.',
        'order_ref' => $result['order_ref'],
        'redirect' => assetUrl('booking-success.php?order=' . rawurlencode($result['order_ref'])),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'تعذّر إتمام الطلب. حاول مرة أخرى.'], JSON_UNESCAPED_UNICODE);
}
