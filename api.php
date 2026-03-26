<?php
/**
 * api.php - REST API dengan Router System
 * Migrasi dari if-based routing ke class-based routing
 * Version: 2.0 (Router-based)
 */

// Error reporting
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// CORS & Headers
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'https://seat.multibus.id',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (getenv('APP_ENV') !== 'production') {
    header("Access-Control-Allow-Origin: *");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load dependencies
require_once 'Router.php';
require_once 'config/db.php';
require_once 'config/activity_log.php';
require_once 'config/perf_log.php';

// Create lazy tables
$conn->exec("CREATE TABLE IF NOT EXISTS bookings (
    id SERIAL PRIMARY KEY,
    rute VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    unit INT DEFAULT 1,
    seat VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    pickup_point VARCHAR(255),
    pembayaran VARCHAR(50) DEFAULT 'Belum Lunas',
    status VARCHAR(20) DEFAULT 'active',
    segment_id INT DEFAULT 0,
    price NUMERIC(15,2) DEFAULT 0,
    discount NUMERIC(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL UNIQUE,
    address TEXT,
    pickup_point VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
)");

// Helper functions
function apiResponse($data = [], $success = true, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

function apiSuccess($data = [], $statusCode = 200)
{
    apiResponse($data, true, $statusCode);
}

function apiError($message = 'error', $statusCode = 400, $extra = [])
{
    apiResponse(array_merge(['error' => $message], $extra), false, $statusCode);
}

function isValidDate($d)
{
    return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
}

function isValidTime($t)
{
    return is_string($t) && preg_match('/^\d{2}:\d{2}$/', $t);
}

function getJsonInput()
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function dbColumnExists($conn, $table, $column)
{
    try {
        $result = $conn->query("SELECT 1 FROM $table LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Initialize Router
$router = new Router();

// ============================================
// GET ROUTES
// ============================================

$router->get('getRoutes', function () use ($conn) {
    $res = $conn->query("SELECT name FROM routes ORDER BY id");
    $routes = [];
    while ($r = $res->fetch()) {
        $routes[] = $r['name'];
    }
    apiSuccess(['routes' => $routes]);
});

$router->get('getCharterRoutes', function () use ($conn) {
    $res = $conn->query("SELECT id, name, origin, destination, duration, rental_price FROM master_carter ORDER BY name");
    $routes = [];
    while ($r = $res->fetch()) {
        $routes[] = $r;
    }
    apiSuccess(['routes' => $routes]);
});

$router->get('getSegments', function () use ($conn) {
    $routeName = $_GET['route_name'] ?? '';
    if ($routeName) {
        $stmt = $conn->prepare("SELECT s.id, s.rute, s.harga FROM segments s JOIN routes r ON s.route_id = r.id WHERE r.name=? ORDER BY s.rute");
        $stmt->execute([$routeName]);
    } else {
        $stmt = $conn->query("SELECT id, rute, harga FROM segments ORDER BY rute");
    }
    $segments = [];
    while ($r = $stmt->fetch()) {
        $segments[] = $r;
    }
    apiSuccess(['segments' => $segments]);
});

$router->get('getSegmentPrice', function () use ($conn) {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        apiError('Invalid segment ID', 400);
    }
    $stmt = $conn->prepare("SELECT harga FROM segments WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    apiSuccess(['price' => $res ? floatval($res['harga']) : 0]);
});

$router->get('getRoutesByDate', function () use ($conn) {
    $tanggal = $_GET['tanggal'] ?? '';
    if (!isValidDate($tanggal)) {
        apiError('invalid_date', 400);
    }
    $dow = (int) date('w', strtotime($tanggal));
    $stmt = $conn->prepare("SELECT DISTINCT rute FROM schedules WHERE dow = ? ORDER BY rute");
    $stmt->execute([$dow]);
    $routes = [];
    while ($r = $stmt->fetch()) {
        $routes[] = $r['rute'];
    }
    apiSuccess(['routes' => $routes]);
});

$router->get('getSchedules', function () use ($conn) {
    $perfStartedAt = perf_timer_start();
    $rute = $_GET['rute'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    if (!$rute || !isValidDate($tanggal)) {
        apiError('invalid_params', 400);
    }
    $dow = (int) date('w', strtotime($tanggal));
    $stmt = $conn->prepare("
        SELECT s.jam, s.units, s.seats, s.layout, s.unit_id, 
               u.kapasitas, u.nopol, u.layout AS unit_layout 
        FROM schedules s 
        LEFT JOIN units u ON s.unit_id = u.id 
        WHERE s.rute=? AND s.dow=? 
        ORDER BY s.jam
    ");
    $stmt->execute([$rute, $dow]);
    $schedules = [];
    while ($r = $stmt->fetch()) {
        $seatCount = intval($r['seats']);
        if ($seatCount <= 0 && $r['unit_id'] && $r['kapasitas']) {
            $seatCount = intval($r['kapasitas']);
        }
        $layoutData = [];
        if ($r['unit_id'] && $r['unit_layout']) {
            $layoutData = json_decode($r['unit_layout'], true);
            if (!is_array($layoutData)) $layoutData = [];
        }
        $schedules[] = [
            'jam' => substr($r['jam'], 0, 5),
            'units' => intval($r['units']),
            'seats' => $seatCount,
            'layout' => $layoutData,
            'unit_id' => intval($r['unit_id'] ?? 0),
            'nopol' => $r['nopol'] ?? ''
        ];
    }
    perf_finish('api.getSchedules', $perfStartedAt, [
        'rute' => $rute,
        'tanggal' => $tanggal,
        'count' => count($schedules),
    ], 100);
    apiSuccess(['schedules' => $schedules]);
});

$router->get('getBookedSeatsDetail', function () use ($conn) {
    $perfStartedAt = perf_timer_start();
    $rute = $_GET['rute'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    $jam = $_GET['jam'] ?? '';
    $unit = (int) ($_GET['unit'] ?? 1);
    if (!$rute || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !preg_match('/^\d{2}:\d{2}$/', $jam)) {
        apiError('invalid_params', 400);
    }
    $stmt = $conn->prepare("
        SELECT b.id, b.seat, b.name, b.phone, b.pembayaran,
               b.pickup_point, b.segment_id, b.price, b.discount, s.rute AS segment_name
        FROM bookings b 
        LEFT JOIN segments s ON b.segment_id = s.id 
        WHERE b.rute=? AND b.tanggal=? AND b.jam=? AND b.unit=? 
        AND b.status!='canceled'
    ");
    $stmt->execute([$rute, $tanggal, $jam, $unit]);
    $details = [];
    while ($r = $stmt->fetch()) {
        $details[(string) $r['seat']] = [
            'id' => (int) ($r['id'] ?? 0),
            'name' => $r['name'],
            'phone' => $r['phone'],
            'pembayaran' => $r['pembayaran'],
            'pickup_point' => $r['pickup_point'],
            'segment_id' => (int) ($r['segment_id'] ?? 0),
            'segment_name' => $r['segment_name'],
            'price' => floatval($r['price']),
            'discount' => floatval($r['discount'])
        ];
    }
    perf_finish('api.getBookedSeatsDetail', $perfStartedAt, [
        'rute' => $rute,
        'tanggal' => $tanggal,
        'jam' => $jam,
        'unit' => $unit,
        'count' => count($details),
    ], 80);
    apiSuccess(['details' => $details]);
});

$router->post('updateBookedSeat', function () use ($conn) {
    $data = getJsonInput();
    if (empty($data)) {
        apiError('invalid_json', 400);
    }

    $id = (int) ($data['id'] ?? 0);
    $name = strtoupper(trim((string) ($data['name'] ?? '')));
    $phone = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));
    $seat = strtoupper(trim((string) ($data['seat'] ?? '')));
    $pickupPoint = trim((string) ($data['pickup_point'] ?? ''));
    $segmentId = (int) ($data['segment_id'] ?? 0);
    $discount = max(0, (float) ($data['discount'] ?? 0));
    $pembayaran = trim((string) ($data['pembayaran'] ?? 'Belum Lunas'));

    if ($id <= 0 || $name === '' || $phone === '' || $pickupPoint === '' || $seat === '') {
        apiError('missing_fields', 400);
    }

    if (!preg_match('/^[A-Z0-9-]{1,20}$/', $seat)) {
        apiError('invalid_seat', 400);
    }

    if (substr($phone, 0, 2) === '62') $phone = '0' . substr($phone, 2);
    if (substr($phone, 0, 1) === '8') $phone = '0' . $phone;
    if (strlen($phone) > 13) $phone = substr($phone, 0, 13);

    $allowedPayments = ['Belum Lunas', 'Lunas', 'RedBus', 'Traveloka', 'QRIS', 'Transfer', 'Tunai'];
    if (!in_array($pembayaran, $allowedPayments, true)) {
        $pembayaran = 'Belum Lunas';
    }

    $stmtCurrent = $conn->prepare("SELECT id, seat, rute, tanggal, jam, unit, status FROM bookings WHERE id=? LIMIT 1");
    $stmtCurrent->execute([$id]);
    $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
    if (!$current || ($current['status'] ?? '') === 'canceled') {
        apiError('booking_not_found', 404);
    }

    $stmtConflict = $conn->prepare("
        SELECT id FROM bookings
        WHERE rute=? AND tanggal=? AND jam=? AND unit=? AND seat=? AND status!='canceled' AND id!=?
        LIMIT 1
    ");
    $stmtConflict->execute([
        $current['rute'] ?? '',
        $current['tanggal'] ?? '',
        $current['jam'] ?? '',
        $current['unit'] ?? 1,
        $seat,
        $id
    ]);
    if ($stmtConflict->fetch(PDO::FETCH_ASSOC)) {
        apiError('Kursi sudah terpakai pada keberangkatan ini', 409);
    }

    $price = 0;
    if ($segmentId > 0) {
        $stmtSegment = $conn->prepare("SELECT harga FROM segments WHERE id=? LIMIT 1");
        $stmtSegment->execute([$segmentId]);
        if ($segment = $stmtSegment->fetch(PDO::FETCH_ASSOC)) {
            $price = (float) ($segment['harga'] ?? 0);
        }
    }

    $stmt = $conn->prepare("
        UPDATE bookings
        SET seat=?, name=?, phone=?, pickup_point=?, pembayaran=?, segment_id=?, price=?, discount=?
        WHERE id=? AND status!='canceled'
    ");
    $stmt->execute([$seat, $name, $phone, $pickupPoint, $pembayaran, $segmentId ?: null, $price, $discount, $id]);

    $stmtCustomer = $conn->prepare("
        INSERT INTO customers (name, phone, pickup_point, address)
        VALUES (?, ?, ?, '')
        ON CONFLICT (phone) DO UPDATE SET
            name = EXCLUDED.name,
            pickup_point = EXCLUDED.pickup_point
    ");
    $stmtCustomer->execute([$name, $phone, $pickupPoint]);

    activity_log_write(
        $conn,
        'booking',
        'booking',
        $id,
        'update',
        'Booking diperbarui: ' . $name,
        ($current['rute'] ?? '-') . ' | ' . ($current['tanggal'] ?? '-') . ' ' . ($current['jam'] ?? '-') . ' | Unit ' . ($current['unit'] ?? '1') . ' | Kursi ' . ($current['seat'] ?? '-') . ' -> ' . $seat,
        'web'
    );

    apiSuccess(['message' => 'Booking berhasil diperbarui']);
});

$router->post('cancelBookedSeat', function () use ($conn) {
    $data = getJsonInput();
    if (empty($data)) {
        apiError('invalid_json', 400);
    }

    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0) {
        apiError('invalid_booking_id', 400);
    }

    $stmtCurrent = $conn->prepare("SELECT id, seat, rute, tanggal, jam, unit, name, status FROM bookings WHERE id=? LIMIT 1");
    $stmtCurrent->execute([$id]);
    $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
    if (!$current || ($current['status'] ?? '') === 'canceled') {
        apiError('booking_not_found', 404);
    }

    $stmt = $conn->prepare("UPDATE bookings SET status='canceled' WHERE id=? AND status!='canceled'");
    $stmt->execute([$id]);

    activity_log_write(
        $conn,
        'booking',
        'booking',
        $id,
        'cancel',
        'Booking dibatalkan: ' . ($current['name'] ?? 'Tanpa Nama'),
        ($current['rute'] ?? '-') . ' | ' . ($current['tanggal'] ?? '-') . ' ' . ($current['jam'] ?? '-') . ' | Unit ' . ($current['unit'] ?? '1') . ' | Kursi ' . ($current['seat'] ?? '-'),
        'web'
    );

    apiSuccess(['message' => 'Booking berhasil dibatalkan']);
});

$router->get('searchCustomers', function () use ($conn) {
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        apiSuccess(['customers' => []]);
    }
    $phoneQuery = preg_replace('/\D+/', '', $q);
    $like = '%' . strtolower($q) . '%';
    $phoneLike = $phoneQuery !== '' ? '%' . $phoneQuery . '%' : '';
    $stmt = $conn->prepare("
        SELECT name, phone, pickup_point, address
        FROM customers
        WHERE LOWER(COALESCE(name, '')) LIKE ?
           OR phone LIKE ?
           OR (? <> '' AND REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', '') LIKE ?)
           OR LOWER(COALESCE(pickup_point, '')) LIKE ?
        ORDER BY
            CASE
                WHEN LOWER(COALESCE(name, '')) LIKE ? THEN 0
                WHEN phone LIKE ? THEN 1
                WHEN LOWER(COALESCE(pickup_point, '')) LIKE ? THEN 2
                ELSE 3
            END,
            name
        LIMIT 20
    ");
    $stmt->execute([
        $like,
        '%' . $q . '%',
        $phoneQuery,
        $phoneLike,
        $like,
        strtolower($q) . '%',
        $q . '%',
        strtolower($q) . '%'
    ]);
    $customers = [];
    while ($r = $stmt->fetch()) {
        $customers[] = $r;
    }
    apiSuccess(['customers' => $customers]);
});

$router->get('getUnits', function () use ($conn) {
    $res = $conn->query("SELECT id, nopol, merek, type, kapasitas FROM units WHERE status='Aktif' ORDER BY nopol");
    $units = [];
    while ($r = $res->fetch()) {
        $units[] = $r;
    }
    apiSuccess(['units' => $units]);
});

$router->get('getDrivers', function () use ($conn) {
    $res = $conn->query("SELECT id, nama, phone FROM drivers ORDER BY nama");
    $drivers = [];
    while ($r = $res->fetch()) {
        $drivers[] = $r;
    }
    apiSuccess(['drivers' => $drivers]);
});

$router->get('getLuggageServices', function () use ($conn) {
    $res = $conn->query("SELECT id, name, price FROM luggage_services ORDER BY price ASC");
    $services = [];
    while ($r = $res->fetch()) {
        $services[] = $r;
    }
    apiSuccess(['services' => $services]);
});

// ============================================
// POST ROUTES
// ============================================

$router->post('addCharterRoute', function () use ($conn) {
    $data = getJsonInput();
    if (empty($data)) {
        apiError('invalid_json', 400);
    }
    $origin = strtoupper(trim($data['origin'] ?? ''));
    $destination = strtoupper(trim($data['destination'] ?? ''));
    $duration = trim($data['duration'] ?? 'Regular');
    $rental_price = floatval($data['rental_price'] ?? 0);
    $bop_price = floatval($data['bop_price'] ?? 0);
    $notes = trim($data['notes'] ?? '');

    if (!$origin || !$destination || !$rental_price) {
        apiError('missing_fields', 400);
    }

    $name = $origin . ' - ' . $destination;
    $stmt = $conn->prepare("INSERT INTO master_carter (name, origin, destination, duration, rental_price, bop_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $origin, $destination, $duration, $rental_price, $bop_price, $notes]);
    activity_log_write($conn, 'settings', 'master_carter', $conn->lastInsertId(), 'create', 'Master carter ditambahkan: ' . $name, $origin . ' -> ' . $destination, 'web');
    apiSuccess(['message' => 'Charter route added', 'route_id' => $conn->lastInsertId()], 201);
});

$router->post('submitCharter', function () use ($conn) {
    $data = getJsonInput();
    if (empty($data)) {
        apiError('invalid_json', 400);
    }
    
    $name = strtoupper(trim($data['name'] ?? ''));
    $company = strtoupper(trim($data['company_name'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $start_date = trim($data['start_date'] ?? '');
    $end_date = trim($data['end_date'] ?? '');
    $departure_time = trim($data['departure_time'] ?? '');
    $pickup = trim($data['pickup_point'] ?? '');
    $drop = trim($data['drop_point'] ?? '');
    $unit_id = intval($data['unit_id'] ?? 0);
    $driver = trim($data['driver_name'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $layanan = trim($data['layanan'] ?? 'Regular');
    $bop_price = floatval($data['bop_price'] ?? 0);

    if (!$name || !$start_date || !$end_date || !$unit_id) {
        apiError('missing_fields', 400);
    }

    // Auto-migration for new column
    if (!dbColumnExists($conn, 'charters', 'company_name')) {
        $conn->exec("ALTER TABLE charters ADD COLUMN company_name VARCHAR(255)");
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO charters (name, company_name, phone, start_date, end_date, departure_time, 
                                pickup_point, drop_point, unit_id, driver_name, price, layanan, bop_price, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $company, $phone, $start_date, $end_date, $departure_time, $pickup, $drop, $unit_id, $driver, $price, $layanan, $bop_price]);
        activity_log_write($conn, 'charter', 'charter', $conn->lastInsertId(), 'create', 'Carter baru dibuat: ' . $name, $pickup . ' -> ' . $drop . ' | ' . $start_date . ' ' . $departure_time, 'web');
        apiSuccess(['message' => 'Charter submitted successfully'], 201);
    } catch (Exception $e) {
        apiError('Database error: ' . $e->getMessage(), 500);
    }
});

$router->post('submitLuggage', function () use ($conn) {
    $data = getJsonInput();
    if (empty($data)) {
        apiError('invalid_json', 400);
    }
    
    $sender_name = strtoupper(trim($data['sender_name'] ?? ''));
    $sender_phone = trim($data['sender_phone'] ?? '');
    $sender_address = trim($data['sender_address'] ?? '');
    $receiver_name = strtoupper(trim($data['receiver_name'] ?? ''));
    $receiver_phone = trim($data['receiver_phone'] ?? '');
    $receiver_address = trim($data['receiver_address'] ?? '');
    $service_id = intval($data['service_id'] ?? 0);
    $quantity = max(1, intval($data['quantity'] ?? 1));
    $notes = trim($data['notes'] ?? '');
    $price = floatval($data['price'] ?? 0);

    if (!$sender_name || !$sender_phone || !$receiver_name || !$receiver_phone || !$service_id) {
        apiError('missing_fields', 400);
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO luggages (sender_name, sender_phone, sender_address, receiver_name, 
                                receiver_phone, receiver_address, service_id, quantity, notes, price, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$sender_name, $sender_phone, $sender_address, $receiver_name, $receiver_phone, $receiver_address, $service_id, $quantity, $notes, $price]);
        activity_log_write($conn, 'luggage', 'luggage', $conn->lastInsertId(), 'create', 'Bagasi baru dibuat: ' . $sender_name, $sender_name . ' -> ' . $receiver_name . ' | Qty ' . $quantity, 'web');
        apiSuccess(['message' => 'Luggage shipment saved successfully'], 201);
    } catch (Exception $e) {
        apiError('Database error: ' . $e->getMessage(), 500);
    }
});

// Regular Booking (POST without specific action - handled separately)
$router->post('submitBooking', function () use ($conn) {
    $data = getJsonInput();
    if (empty($data)) {
        apiError('invalid_json', 400);
    }

    $required = ['rute', 'tanggal', 'jam', 'seats', 'name', 'phone'];
    foreach ($required as $k) {
        if (!isset($data[$k]) || $data[$k] === '') {
            apiError('invalid_payload', 400, ['missing' => $k]);
        }
    }

    $rute = trim($data['rute']);
    $tanggal = trim($data['tanggal']);
    $jam = trim($data['jam']);
    $unit = isset($data['unit']) ? intval($data['unit']) : 1;
    $name = strtoupper(trim($data['name']));
    $phone = trim($data['phone']);
    $phone = preg_replace('/\D/', '', $phone);
    if (substr($phone, 0, 2) === '62') $phone = '0' . substr($phone, 2);
    if (substr($phone, 0, 1) === '8') $phone = '0' . $phone;
    if (strlen($phone) > 13) $phone = substr($phone, 0, 13);
    
    $pickup_point = trim($data['pickup_point'] ?? '');
    $address = trim($data['address'] ?? '');
    $pembayaran = trim($data['pembayaran'] ?? 'Belum Lunas');
    $seats = $data['seats'];
    $segment_id = isset($data['segment_id']) ? intval($data['segment_id']) : 0;
    $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
    $price = 0;

    if ($segment_id > 0) {
        $stmtS = $conn->prepare("SELECT harga FROM segments WHERE id=? LIMIT 1");
        $stmtS->execute([$segment_id]);
        $resS = $stmtS->fetch();
        if ($resS) {
            $price = floatval($resS['harga']);
        }
    }

    if (!is_array($seats) || count($seats) === 0) {
        apiError('no_seats', 400);
    }

    if (!isValidDate($tanggal)) {
        apiError('invalid_date_format', 400);
    }
    if (!isValidTime($jam)) {
        apiError('invalid_time_format', 400);
    }

    // Sanitize seats
    $seats = array_values(array_unique(array_map('strval', $seats)));
    $numSeats = count($seats);
    $discountPerSeat = ($numSeats > 0) ? ($discount / $numSeats) : 0;

    // Validate seat numbers
    foreach ($seats as $s) {
        if (!preg_match('/^\d+$/', $s)) {
            apiError('invalid_seat', 400);
        }
        $n = intval($s);
        if ($n < 1 || $n > 50) {
            // allow higher
        }
    }

    // Acquire lock via transaction
    $conn->beginTransaction();
    try {
        // Check conflicts
        $placeholders = implode(',', array_fill(0, count($seats), '?'));
        $sql = "SELECT seat FROM bookings WHERE rute=? AND tanggal=? AND jam=? AND unit=? AND status!='canceled' AND seat IN ($placeholders) FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $bind_params = array_merge([$rute, $tanggal, $jam, $unit], $seats);
        $stmt->execute($bind_params);
        $conflict = [];
        while ($r = $stmt->fetch()) {
            $conflict[] = (string) $r['seat'];
        }

        if (!empty($conflict)) {
            $conn->rollBack();
            apiError('conflict', 400, ['conflict' => array_values(array_unique($conflict))]);
        }

        // Insert bookings
        $insert_stmt = $conn->prepare("
            INSERT INTO bookings (rute, tanggal, jam, unit, seat, name, phone, pickup_point, pembayaran, status, created_at, segment_id, price, discount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), ?, ?, ?)
        ");

        foreach ($seats as $s) {
            $seatStr = (string) $s;
            try {
                $insert_stmt->execute([$rute, $tanggal, $jam, $unit, $seatStr, $name, $phone, $pickup_point, $pembayaran, $segment_id, $price, $discountPerSeat]);
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') {
                    $conflict[] = $seatStr;
                } else {
                    throw $e;
                }
            }
        }

        if (!empty($conflict)) {
            $conn->rollBack();
            apiError('conflict', 400, ['conflict' => array_values(array_unique($conflict))]);
        }

        // Ensure customer exists
        $stmt_c = $conn->prepare("INSERT INTO customers (name, phone, pickup_point, address) VALUES (?, ?, ?, ?) ON CONFLICT (phone) DO UPDATE SET pickup_point=EXCLUDED.pickup_point, address=EXCLUDED.address");
        $stmt_c->execute([$name, $phone, $pickup_point, $address]);

        $conn->commit();
        activity_log_write(
            $conn,
            'booking',
            'booking',
            $rute . '|' . $tanggal . '|' . $jam . '|' . $unit . '|' . implode(',', $seats),
            'create',
            'Booking baru dibuat: ' . $name,
            $rute . ' | ' . $tanggal . ' ' . $jam . ' | Unit ' . $unit . ' | Kursi ' . implode(', ', $seats) . ' | Pembayaran ' . $pembayaran,
            'web'
        );
        apiSuccess(['added' => count($seats)], 201);
    } catch (Exception $ex) {
        $conn->rollBack();
        apiError('exception', 500, ['detail' => $ex->getMessage()]);
    }
});

// Dispatch the request
$router->dispatch();
?>
