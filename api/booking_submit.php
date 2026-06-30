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
    $routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
    $route = getRouteById($routeId);

    if ($route === null || !isRouteBookingOpen($route)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'المسار غير متاح للحجز.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $validated = validateBookingInput($route, $_POST);
    if (count($validated['errors']) > 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => implode(' ', $validated['errors'])], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = get_pdo();
    $result = createBooking($pdo, $route, $validated['data']);

    echo json_encode([
        'ok' => true,
        'message' => 'تم تأكيد الحجز والدفع بنجاح.',
        'booking_ref' => $result['booking_ref'],
        'redirect' => assetUrl('booking-success.php?ref=' . rawurlencode($result['booking_ref'])),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'تعذّر إتمام الحجز. حاول مرة أخرى.'], JSON_UNESCAPED_UNICODE);
}
