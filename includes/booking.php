<?php

declare(strict_types=1);

function atheerEnsureBookingsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_ref VARCHAR(24) NOT NULL,
        order_ref VARCHAR(24) NULL,
        route_id INT UNSIGNED NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(255) NULL,
        participants INT UNSIGNED NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        payment_method VARCHAR(32) NOT NULL,
        payment_status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        paid_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_booking_ref (booking_ref),
        KEY idx_route_id (route_id),
        KEY idx_created_at (created_at),
        KEY idx_order_ref (order_ref)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $pdo->exec('ALTER TABLE bookings ADD COLUMN order_ref VARCHAR(24) NULL');
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate column') === false && stripos($msg, 'duplicate column name') === false) {
            throw $e;
        }
    }
}

function routeBookingUrl(int $routeId): string
{
    return cartAddUrl($routeId);
}

function isRouteBookingOpen(array $route): bool
{
    return !empty($route['is_active']);
}

function routeUnitPrice(array $route): float
{
    $price = $route['price'] ?? null;
    if ($price === null || $price === '') {
        return 0.0;
    }
    return max(0.0, (float) $price);
}

function formatMoney(float $amount): string
{
    if ($amount <= 0) {
        return 'مجاني';
    }
    return number_format($amount, 0, '.', ',') . ' ريال';
}

function paymentMethodsCatalog(): array
{
    return [
        'mada' => [
            'label' => 'مدى',
            'hint' => 'بطاقة مدى السعودية',
            'badge' => 'مدى',
        ],
        'card' => [
            'label' => 'فيزا / ماستركارد',
            'hint' => 'بطاقات ائتمانية دولية',
            'badge' => 'VISA',
        ],
        'apple_pay' => [
            'label' => 'Apple Pay',
            'hint' => 'دفع سريع من آيفون',
            'badge' => 'Pay',
        ],
        'stc_pay' => [
            'label' => 'STC Pay',
            'hint' => 'محفظة stc pay',
            'badge' => 'stc',
        ],
        'tamara' => [
            'label' => 'تمارا',
            'hint' => 'قسّم على 4 دفعات بدون فوائد',
            'badge' => 'تمارا',
        ],
    ];
}

function paymentMethodLabel(string $method): string
{
    $catalog = paymentMethodsCatalog();
    return $catalog[$method]['label'] ?? $method;
}

function generateBookingRef(PDO $pdo): string
{
    do {
        $ref = 'ATH-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $chk = $pdo->prepare('SELECT id FROM bookings WHERE booking_ref = :r LIMIT 1');
        $chk->execute(['r' => $ref]);
        $exists = $chk->fetch();
    } while ($exists);
    return $ref;
}

function normalizePhone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '966')) {
        $digits = '0' . substr($digits, 3);
    }
    return $digits;
}

function validateBookingInput(array $route, array $input): array
{
    $errors = [];
    $name = trim((string) ($input['full_name'] ?? ''));
    $phone = normalizePhone((string) ($input['phone'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $participants = (int) ($input['participants'] ?? 1);
    $method = (string) ($input['payment_method'] ?? '');
    $notes = trim((string) ($input['notes'] ?? ''));

    if ($name === '' || mb_strlen($name, 'UTF-8') < 3) {
        $errors[] = 'أدخل الاسم الكامل (3 أحرف على الأقل).';
    }
    if (!preg_match('/^05\d{8}$/', $phone)) {
        $errors[] = 'أدخل رقم جوال سعودي صحيح (مثال: 05xxxxxxxx).';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }

    $minP = isset($route['min_participants']) && $route['min_participants'] !== '' ? (int) $route['min_participants'] : 1;
    $maxP = isset($route['max_participants']) && $route['max_participants'] !== '' ? (int) $route['max_participants'] : 0;
    if ($participants < max(1, $minP)) {
        $errors[] = 'الحد الأدنى للمشاركين هو ' . max(1, $minP) . '.';
    }
    if ($maxP > 0 && $participants > $maxP) {
        $errors[] = 'الحد الأقصى للمشاركين هو ' . $maxP . '.';
    }

    if (!array_key_exists($method, paymentMethodsCatalog())) {
        $errors[] = 'اختر طريقة دفع صالحة.';
    }

    if (in_array($method, ['mada', 'card'], true)) {
        $cardNumber = preg_replace('/\D+/', '', (string) ($input['card_number'] ?? '')) ?? '';
        $expiry = trim((string) ($input['card_expiry'] ?? ''));
        $cvv = trim((string) ($input['card_cvv'] ?? ''));
        if (strlen($cardNumber) < 15) {
            $errors[] = 'رقم البطاقة غير مكتمل.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = 'تاريخ الانتهاء بصيغة MM/YY.';
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'رمز الأمان CVV غير صالح.';
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email === '' ? null : $email,
            'participants' => $participants,
            'payment_method' => $method,
            'notes' => $notes === '' ? null : $notes,
        ],
    ];
}

function createBooking(PDO $pdo, array $route, array $data): array
{
    atheerEnsureBookingsSchema($pdo);
    $unit = routeUnitPrice($route);
    $total = round($unit * (int) $data['participants'], 2);
    $ref = generateBookingRef($pdo);

    $sql = 'INSERT INTO bookings (
        booking_ref, route_id, full_name, phone, email, participants,
        unit_price, total_amount, payment_method, payment_status, notes, paid_at
    ) VALUES (
        :booking_ref, :route_id, :full_name, :phone, :email, :participants,
        :unit_price, :total_amount, :payment_method, :payment_status, :notes, :paid_at
    )';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'booking_ref' => $ref,
        'route_id' => (int) $route['id'],
        'full_name' => $data['full_name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'participants' => (int) $data['participants'],
        'unit_price' => $unit,
        'total_amount' => $total,
        'payment_method' => $data['payment_method'],
        'payment_status' => 'paid',
        'notes' => $data['notes'],
        'paid_at' => date('Y-m-d H:i:s'),
    ]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'booking_ref' => $ref,
        'total_amount' => $total,
        'unit_price' => $unit,
        'participants' => (int) $data['participants'],
        'payment_method' => $data['payment_method'],
    ];
}

function getBookingByRef(PDO $pdo, string $ref): ?array
{
    atheerEnsureBookingsSchema($pdo);
    $stmt = $pdo->prepare('SELECT b.*, r.name AS route_name, r.type AS route_type
        FROM bookings b
        INNER JOIN routes r ON r.id = b.route_id
        WHERE b.booking_ref = :ref
        LIMIT 1');
    $stmt->execute(['ref' => $ref]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function validateCheckoutInput(array $input): array
{
    $errors = [];
    $name = trim((string) ($input['full_name'] ?? ''));
    $phone = normalizePhone((string) ($input['phone'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $method = (string) ($input['payment_method'] ?? '');
    $notes = trim((string) ($input['notes'] ?? ''));

    if ($name === '' || mb_strlen($name, 'UTF-8') < 3) {
        $errors[] = 'أدخل الاسم الكامل (3 أحرف على الأقل).';
    }
    if (!preg_match('/^05\d{8}$/', $phone)) {
        $errors[] = 'أدخل رقم جوال سعودي صحيح (مثال: 05xxxxxxxx).';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }
    if (!array_key_exists($method, paymentMethodsCatalog())) {
        $errors[] = 'اختر طريقة دفع صالحة.';
    }
    if (in_array($method, ['mada', 'card'], true)) {
        $cardNumber = preg_replace('/\D+/', '', (string) ($input['card_number'] ?? '')) ?? '';
        $expiry = trim((string) ($input['card_expiry'] ?? ''));
        $cvv = trim((string) ($input['card_cvv'] ?? ''));
        if (strlen($cardNumber) < 15) {
            $errors[] = 'رقم البطاقة غير مكتمل.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = 'تاريخ الانتهاء بصيغة MM/YY.';
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'رمز الأمان CVV غير صالح.';
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email === '' ? null : $email,
            'payment_method' => $method,
            'notes' => $notes === '' ? null : $notes,
        ],
    ];
}

function generateOrderRef(PDO $pdo): string
{
    do {
        $ref = 'ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $chk = $pdo->prepare('SELECT id FROM bookings WHERE order_ref = :r LIMIT 1');
        $chk->execute(['r' => $ref]);
        $exists = $chk->fetch();
    } while ($exists);
    return $ref;
}

function createOrderFromCart(PDO $pdo, array $cartLines, array $customer): array
{
    atheerEnsureBookingsSchema($pdo);
    if (count($cartLines) === 0) {
        throw new RuntimeException('السلة فارغة');
    }

    $orderRef = generateOrderRef($pdo);
    $grandTotal = 0.0;
    $items = [];
    $lineNo = 0;

    $sql = 'INSERT INTO bookings (
        booking_ref, order_ref, route_id, full_name, phone, email, participants,
        unit_price, total_amount, payment_method, payment_status, notes, paid_at
    ) VALUES (
        :booking_ref, :order_ref, :route_id, :full_name, :phone, :email, :participants,
        :unit_price, :total_amount, :payment_method, :payment_status, :notes, :paid_at
    )';

    $stmt = $pdo->prepare($sql);
    $paidAt = date('Y-m-d H:i:s');

    foreach ($cartLines as $line) {
        $lineNo++;
        $route = $line['route'];
        $participants = (int) $line['participants'];
        $unit = (float) $line['unit_price'];
        $lineTotal = round($unit * $participants, 2);
        $bookingRef = $orderRef . '-L' . $lineNo;

        $stmt->execute([
            'booking_ref' => $bookingRef,
            'order_ref' => $orderRef,
            'route_id' => (int) $route['id'],
            'full_name' => $customer['full_name'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
            'participants' => $participants,
            'unit_price' => $unit,
            'total_amount' => $lineTotal,
            'payment_method' => $customer['payment_method'],
            'payment_status' => 'paid',
            'notes' => $customer['notes'],
            'paid_at' => $paidAt,
        ]);

        $grandTotal += $lineTotal;
        $items[] = [
            'booking_ref' => $bookingRef,
            'route_id' => (int) $route['id'],
            'route_name' => (string) $route['name'],
            'participants' => $participants,
            'line_total' => $lineTotal,
        ];
    }

    return [
        'order_ref' => $orderRef,
        'total_amount' => round($grandTotal, 2),
        'payment_method' => $customer['payment_method'],
        'items' => $items,
        'full_name' => $customer['full_name'],
        'phone' => $customer['phone'],
    ];
}

function getOrderByRef(PDO $pdo, string $orderRef): ?array
{
    atheerEnsureBookingsSchema($pdo);
    $stmt = $pdo->prepare('SELECT b.*, r.name AS route_name, r.type AS route_type
        FROM bookings b
        INNER JOIN routes r ON r.id = b.route_id
        WHERE b.order_ref = :ref
        ORDER BY b.id ASC');
    $stmt->execute(['ref' => $orderRef]);
    $rows = $stmt->fetchAll();
    if (count($rows) === 0) {
        return null;
    }
    $first = $rows[0];
    $total = 0.0;
    $items = [];
    foreach ($rows as $row) {
        $total += (float) $row['total_amount'];
        $items[] = $row;
    }
    return [
        'order_ref' => $orderRef,
        'full_name' => $first['full_name'],
        'phone' => $first['phone'],
        'email' => $first['email'],
        'payment_method' => $first['payment_method'],
        'created_at' => $first['created_at'],
        'total_amount' => round($total, 2),
        'items' => $items,
    ];
}

function getAllBookings(PDO $pdo, int $limit = 100): array
{
    atheerEnsureBookingsSchema($pdo);
    $stmt = $pdo->prepare('SELECT b.*, r.name AS route_name
        FROM bookings b
        INNER JOIN routes r ON r.id = b.route_id
        ORDER BY b.created_at DESC
        LIMIT :lim');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
