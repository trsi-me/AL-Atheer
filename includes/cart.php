<?php

declare(strict_types=1);

function cartEnsureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['atheer_cart']) || !is_array($_SESSION['atheer_cart'])) {
        $_SESSION['atheer_cart'] = [];
    }
}

function cartAdd(int $routeId, int $participants = 1): bool
{
    if ($routeId < 1) {
        return false;
    }
    $route = getRouteById($routeId);
    if ($route === null || !isRouteBookingOpen($route)) {
        return false;
    }
    cartEnsureSession();
    $participants = cartClampParticipants($route, $participants);
    $_SESSION['atheer_cart'][(string) $routeId] = [
        'route_id' => $routeId,
        'participants' => $participants,
    ];
    return true;
}

function cartClampParticipants(array $route, int $participants): int
{
    $minP = isset($route['min_participants']) && $route['min_participants'] !== '' ? (int) $route['min_participants'] : 1;
    $maxP = isset($route['max_participants']) && $route['max_participants'] !== '' ? (int) $route['max_participants'] : 0;
    $participants = max(max(1, $minP), $participants);
    if ($maxP > 0 && $participants > $maxP) {
        $participants = $maxP;
    }
    return $participants;
}

function cartUpdate(int $routeId, int $participants): bool
{
    cartEnsureSession();
    $key = (string) $routeId;
    if (!isset($_SESSION['atheer_cart'][$key])) {
        return false;
    }
    $route = getRouteById($routeId);
    if ($route === null || !isRouteBookingOpen($route)) {
        cartRemove($routeId);
        return false;
    }
    $_SESSION['atheer_cart'][$key]['participants'] = cartClampParticipants($route, $participants);
    return true;
}

function cartRemove(int $routeId): void
{
    cartEnsureSession();
    unset($_SESSION['atheer_cart'][(string) $routeId]);
}

function cartClear(): void
{
    cartEnsureSession();
    $_SESSION['atheer_cart'] = [];
}

function cartCount(): int
{
    cartEnsureSession();
    return count($_SESSION['atheer_cart']);
}

function cartGetRaw(): array
{
    cartEnsureSession();
    return array_values($_SESSION['atheer_cart']);
}

function cartGetLines(): array
{
    $lines = [];
    foreach (cartGetRaw() as $item) {
        $routeId = (int) ($item['route_id'] ?? 0);
        if ($routeId < 1) {
            continue;
        }
        $route = getRouteById($routeId);
        if ($route === null || !isRouteBookingOpen($route)) {
            cartRemove($routeId);
            continue;
        }
        $participants = (int) ($item['participants'] ?? 1);
        $participants = cartClampParticipants($route, $participants);
        $unit = routeUnitPrice($route);
        $lineTotal = round($unit * $participants, 2);
        $lines[] = [
            'route_id' => $routeId,
            'participants' => $participants,
            'route' => $route,
            'unit_price' => $unit,
            'line_total' => $lineTotal,
            'min_participants' => max(1, (int) ($route['min_participants'] ?? 1) ?: 1),
            'max_participants' => (int) ($route['max_participants'] ?? 0) ?: 99,
        ];
    }
    return $lines;
}

function cartSubtotal(): float
{
    $total = 0.0;
    foreach (cartGetLines() as $line) {
        $total += $line['line_total'];
    }
    return round($total, 2);
}

function cartAddUrl(int $routeId): string
{
    return assetUrl('cart.php?add=' . $routeId);
}

function cartPageUrl(): string
{
    return assetUrl('cart.php');
}

function checkoutPageUrl(): string
{
    return assetUrl('checkout.php');
}

function checkoutCompleteUrl(): string
{
    return assetUrl('checkout_complete.php');
}

function checkoutSetFlashError(string $message): void
{
    cartEnsureSession();
    $_SESSION['checkout_flash_error'] = $message;
}

function checkoutPullFlashError(): string
{
    cartEnsureSession();
    $msg = (string) ($_SESSION['checkout_flash_error'] ?? '');
    unset($_SESSION['checkout_flash_error']);
    return $msg;
}
