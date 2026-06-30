<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . checkoutPageUrl());
    exit;
}

try {
    $result = processCheckoutOrder();
    header('Location: ' . assetUrl('booking-success.php?order=' . rawurlencode($result['order_ref'])));
    exit;
} catch (Throwable $e) {
    checkoutSetFlashError($e->getMessage() !== '' ? $e->getMessage() : 'تعذّر إتمام الطلب. حاول مرة أخرى.');
    header('Location: ' . checkoutPageUrl());
    exit;
}
