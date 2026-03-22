<?php
// api-refactored.php
// Contoh API Refactored dengan Router + Helper Functions
// Copy ini untuk menggantikan api.php Anda setelah testing

ini_set('display_errors', 1);
error_reporting(E_ALL);

// CORS & Headers
$allowed_origins = ['http://localhost', 'http://127.0.0.1', 'https://seat.multibus.id'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    if (getenv('APP_ENV') !== 'production') {
        header("Access-Control-Allow-Origin: *");
    }
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load
require_once 'Router.php';
require_once 'helpers/api-response.php';
require_once 'config/db.php';

$router = new Router();

// ============================================
// GET ROUTES - READ OPERATIONS
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
    $routeName = getQuery('route_name', '');
    
    if ($routeName) {
        $stmt = $conn->prepare("
            SELECT s.id, s.rute, s.harga 
            FROM segments s 
            JOIN routes r ON s.route_id = r.id 
            WHERE r.name=? 
            ORDER BY s.rute
        ");
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
    $id = (int) getQuery('id', 0);
    
    if ($id <= 0) {
        apiError('Invalid segment ID', 400);
    }
    
    $stmt = $conn->prepare("SELECT harga FROM segments WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    
    $price = $res ? floatval($res['harga']) : 0;
    apiSuccess(['price' => $price]);
});

$router->get('getRoutesByDate', function () use ($conn) {
    $tanggal = getQuery('tanggal', '');
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        apiError('Invalid date format. Use YYYY-MM-DD', 400);
    }
    
    // Validate actual date
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$d || $d->format('Y-m-d') !== $tanggal) {
        apiError('Invalid date', 400);
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
    $rute = getQuery('rute', '');
    $tanggal = getQuery('tanggal', '');
    
    // Validate
    $missing = [];
    if (!$rute) $missing[] = 'rute';
    if (!$tanggal) $missing[] = 'tanggal';
    
    if (!empty($missing)) {
        apiError('Missing required fields: ' . implode(', ', $missing), 400);
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        apiError('Invalid date format. Use YYYY-MM-DD', 400);
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
    
    apiSuccess(['schedules' => $schedules]);
});

$router->get('getBookedSeatsDetail', function () use ($conn) {
    $rute = getQuery('rute', '');
    $tanggal = getQuery('tanggal', '');
    $jam = getQuery('jam', '');
    $unit = (int) getQuery('unit', 1);
    
    // Validate
    if (!$rute || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !preg_match('/^\d{2}:\d{2}$/', $jam)) {
        apiError('Invalid parameters', 400);
    }
    
    $stmt = $conn->prepare("
        SELECT b.seat, b.name, b.phone, b.pembayaran, 
               b.pickup_point, b.price, b.discount, s.rute AS segment_name 
        FROM bookings b 
        LEFT JOIN segments s ON b.segment_id = s.id 
        WHERE b.rute=? AND b.tanggal=? AND b.jam=? AND b.unit=? 
        AND b.status!='canceled'
    ");
    $stmt->execute([$rute, $tanggal, $jam, $unit]);
    
    $details = [];
    while ($r = $stmt->fetch()) {
        $details[(string) $r['seat']] = [
            'name' => $r['name'],
            'phone' => $r['phone'],
            'pembayaran' => $r['pembayaran'],
            'pickup_point' => $r['pickup_point'],
            'segment_name' => $r['segment_name'],
            'price' => floatval($r['price']),
            'discount' => floatval($r['discount'])
        ];
    }
    
    apiSuccess(['details' => $details]);
});

$router->get('searchCustomers', function () use ($conn) {
    $q = trim(getQuery('q', ''));
    
    if ($q === '') {
        apiSuccess(['customers' => []]);
    }
    
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("
        SELECT name, phone, pickup_point, address 
        FROM customers 
        WHERE name LIKE ? OR phone LIKE ? 
        ORDER BY name 
        LIMIT 20
    ");
    $stmt->execute([$like, $like]);
    
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
// POST ROUTES - WRITE OPERATIONS
// ============================================

$router->post('submitBooking', function () use ($conn) {
    $input = getJsonInput();
    
    // Validate required fields
    $missing = validateRequired(['name', 'phone', 'rute', 'tanggal', 'jam', 'seat'], $input);
    if ($missing !== true) {
        apiError('Missing required fields: ' . implode(', ', $missing), 400);
    }
    
    // Insert booking
    try {
        $stmt = $conn->prepare("
            INSERT INTO bookings (name, phone, rute, tanggal, jam, seat, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            $input['name'],
            $input['phone'],
            $input['rute'],
            $input['tanggal'],
            $input['jam'],
            $input['seat']
        ]);
        
        apiSuccess([
            'booking_id' => $conn->lastInsertId(),
            'message' => 'Booking created successfully'
        ], 201);
    } catch (Exception $e) {
        apiError('Failed to create booking: ' . $e->getMessage(), 500);
    }
});

$router->post('submitCharter', function () use ($conn) {
    $input = getJsonInput();
    
    $missing = validateRequired(['name', 'phone', 'start_date', 'end_date'], $input);
    if ($missing !== true) {
        apiError('Missing required fields: ' . implode(', ', $missing), 400);
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO charters (name, phone, start_date, end_date, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $input['name'],
            $input['phone'],
            $input['start_date'],
            $input['end_date']
        ]);
        
        apiSuccess([
            'charter_id' => $conn->lastInsertId(),
            'message' => 'Charter request submitted'
        ], 201);
    } catch (Exception $e) {
        apiError('Failed to submit charter: ' . $e->getMessage(), 500);
    }
});

// ============================================
// DISPATCH
// ============================================
$router->dispatch();
?>
