<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/functions.php';

$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
} else {
    $action = isset($_GET['action']) ? (string) $_GET['action'] : 'count';
}

try {
    if ($action === 'add') {
        $routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
        $participants = isset($_POST['participants']) ? (int) $_POST['participants'] : 1;
        if (!cartAdd($routeId, $participants)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'تعذّر إضافة المسار للسلة.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'message' => 'تمت إضافة المسار إلى السلة.',
            'count' => cartCount(),
            'subtotal' => cartSubtotal(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update') {
        $routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
        $participants = isset($_POST['participants']) ? (int) $_POST['participants'] : 1;
        if (!cartUpdate($routeId, $participants)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'تعذّر تحديث السلة.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'count' => cartCount(),
            'subtotal' => cartSubtotal(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'remove') {
        $routeId = isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0;
        cartRemove($routeId);
        echo json_encode([
            'ok' => true,
            'count' => cartCount(),
            'subtotal' => cartSubtotal(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'count' => cartCount(),
        'subtotal' => cartSubtotal(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'حدث خطأ في السلة.'], JSON_UNESCAPED_UNICODE);
}
