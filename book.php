<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$routeId = isset($_GET['route']) ? (int) $_GET['route'] : 0;
if ($routeId > 0 && cartAdd($routeId, 1)) {
    header('Location: ' . assetUrl('cart.php?added=1'));
    exit;
}
header('Location: ' . cartPageUrl());
exit;
