<?php
// example-api-with-router.php
// Contoh implementasi API menggunakan Router class

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Setup CORS & headers
$allowed_origins = ['http://localhost', 'http://127.0.0.1', 'https://seat.multibus.id'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    if (getenv('APP_ENV') !== 'production') {
        header("Access-Control-Allow-Origin: *");
    }
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

// Initialize Router
$router = new Router();

// ===== GET ROUTES =====
$router->get('getRoutes', function () use ($conn) {
    $res = $conn->query("SELECT name FROM routes ORDER BY id");
    $out = [];
    while ($r = $res->fetch())
        $out[] = $r['name'];
    echo json_encode(['success' => true, 'routes' => $out]);
});

$router->get('getCharterRoutes', function () use ($conn) {
    $res = $conn->query("SELECT id, name, origin, destination, duration, rental_price FROM master_carter ORDER BY name");
    $out = [];
    while ($r = $res->fetch())
        $out[] = $r;
    echo json_encode(['success' => true, 'routes' => $out]);
});

$router->get('getSegments', function () use ($conn) {
    $routeName = $_GET['route_name'] ?? '';
    if ($routeName) {
        $stmt = $conn->prepare("SELECT s.id, s.rute, s.harga FROM segments s JOIN routes r ON s.route_id = r.id WHERE r.name=? ORDER BY s.rute");
        $stmt->execute([$routeName]);
    } else {
        $stmt = $conn->query("SELECT id, rute, harga FROM segments ORDER BY rute");
    }
    $out = [];
    while ($r = $stmt->fetch())
        $out[] = $r;
    echo json_encode(['success' => true, 'segments' => $out]);
});

$router->get('getSchedules', function () use ($conn) {
    $rute = $_GET['rute'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    if (!$rute || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_params']);
        return;
    }
    
    $dow = (int) date('w', strtotime($tanggal));
    $stmt = $conn->prepare("
        SELECT s.jam, s.units, s.seats, s.layout, s.unit_id, u.kapasitas, u.nopol, u.layout AS unit_layout 
        FROM schedules s 
        LEFT JOIN units u ON s.unit_id = u.id 
        WHERE s.rute=? AND s.dow=? 
        ORDER BY s.jam
    ");
    $stmt->execute([$rute, $dow]);
    
    $out = [];
    while ($r = $stmt->fetch()) {
        $seatCount = intval($r['seats']);
        if ($seatCount <= 0 && $r['unit_id'] && $r['kapasitas']) {
            $seatCount = intval($r['kapasitas']);
        }
        
        $layoutData = [];
        if ($r['unit_id'] && $r['unit_layout']) {
            $layoutData = json_decode($r['unit_layout'], true);
        }
        
        $out[] = [
            'jam' => substr($r['jam'], 0, 5),
            'units' => intval($r['units']),
            'seats' => $seatCount,
            'layout' => is_array($layoutData) ? $layoutData : [],
            'unit_id' => intval($r['unit_id'] ?? 0),
            'nopol' => $r['nopol'] ?? ''
        ];
    }
    
    echo json_encode(['success' => true, 'schedules' => $out]);
});

$router->get('getUnits', function () use ($conn) {
    $res = $conn->query("SELECT id, nopol, merek, type, kapasitas FROM units WHERE status='Aktif' ORDER BY nopol");
    $out = [];
    while ($r = $res->fetch()) {
        $out[] = $r;
    }
    echo json_encode(['success' => true, 'units' => $out]);
});

$router->get('getDrivers', function () use ($conn) {
    $res = $conn->query("SELECT id, nama, phone FROM drivers ORDER BY nama");
    $out = [];
    while ($r = $res->fetch()) {
        $out[] = $r;
    }
    echo json_encode(['success' => true, 'drivers' => $out]);
});

$router->get('getLuggageServices', function () use ($conn) {
    $res = $conn->query("SELECT id, name, price FROM luggage_services ORDER BY price ASC");
    $out = [];
    while ($r = $res->fetch()) {
        $out[] = $r;
    }
    echo json_encode(['success' => true, 'services' => $out]);
});

// ===== POST ROUTES =====
$router->post('submitBooking', function () use ($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    // Implement booking logic here
    echo json_encode(['success' => true, 'message' => 'Booking submitted']);
});

$router->post('submitCharter', function () use ($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    // Implement charter logic here
    echo json_encode(['success' => true, 'message' => 'Charter submitted']);
});

// ===== DISPATCH REQUEST =====
$router->dispatch();
?>
