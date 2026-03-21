<?php
/**
 * admin/ajax/reports.php - Handle income report data
 */

global $conn;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// 1. REGULER INCOME
$sqlRegTotal = "SELECT COUNT(*) as cnt, SUM(price - COALESCE(discount, 0)) as total, SUM(COALESCE(discount, 0)) as total_discount FROM bookings WHERE status != 'canceled' AND tanggal BETWEEN ? AND ?";
$stmtRegTotal = $conn->prepare($sqlRegTotal);
$stmtRegTotal->execute([$start_date, $end_date]);
$regTotalData = $stmtRegTotal->fetch() ?: [];

$regTotal = floatval($regTotalData['total'] ?? 0);
$regCount = intval($regTotalData['cnt'] ?? 0);
$regDiscount = floatval($regTotalData['total_discount'] ?? 0);

// Detailed Reguler per Route
$sqlRegDetails = "SELECT rute, COUNT(*) as cnt, SUM(price - COALESCE(discount, 0)) as total FROM bookings WHERE status != 'canceled' AND tanggal BETWEEN ? AND ? GROUP BY rute ORDER BY total DESC";
$stmtRegDetails = $conn->prepare($sqlRegDetails);
$stmtRegDetails->execute([$start_date, $end_date]);
$resRegDetails = $stmtRegDetails->fetchAll(PDO::FETCH_ASSOC);
$regDetails = $resRegDetails ?: [];

// 2. CARTER INCOME
$sqlCarTotal = "SELECT COUNT(*) as cnt, SUM(price) as total FROM charters WHERE start_date BETWEEN ? AND ?";
$stmtCarTotal = $conn->prepare($sqlCarTotal);
$stmtCarTotal->execute([$start_date, $end_date]);
$carTotalData = $stmtCarTotal->fetch() ?: [];

$carTotal = floatval($carTotalData['total'] ?? 0);
$carCount = intval($carTotalData['cnt'] ?? 0);

// 3. BAGASI (LUGGAGE) INCOME
$sqlLugTotal = "SELECT COUNT(*) as cnt, SUM(price) as total FROM luggages WHERE status != 'canceled' AND DATE(created_at) BETWEEN ? AND ?";
$stmtLugTotal = $conn->prepare($sqlLugTotal);
$stmtLugTotal->execute([$start_date, $end_date]);
$lugTotalData = $stmtLugTotal->fetch() ?: [];

$lugTotal = floatval($lugTotalData['total'] ?? 0);
$lugCount = intval($lugTotalData['cnt'] ?? 0);

// 4. DETAILED DATA FOR TABLE
$type = isset($_GET['type']) ? $_GET['type'] : 'reguler';
$details = [];

if ($type === 'reguler') {
    $sqlDetails = "SELECT b.name, b.phone, b.rute, b.tanggal, COALESCE(b.discount, 0) as discount, (b.price - COALESCE(b.discount, 0)) as final_price FROM bookings b WHERE b.status != 'canceled' AND b.tanggal BETWEEN ? AND ? ORDER BY b.tanggal DESC";
    $stmtDetails = $conn->prepare($sqlDetails);
    $stmtDetails->execute([$start_date, $end_date]);
    $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC) ?: [];
} elseif ($type === 'bagasi') {
    $sqlDetails = "SELECT sender_name as name, sender_phone as phone, s.name as rute, DATE(l.created_at) as tanggal, 0 as discount, l.price as final_price 
                   FROM luggages l 
                   LEFT JOIN luggage_services s ON l.service_id = s.id 
                   WHERE l.status != 'canceled' AND DATE(l.created_at) BETWEEN ? AND ? 
                   ORDER BY l.created_at DESC";
    $stmtDetails = $conn->prepare($sqlDetails);
    $stmtDetails->execute([$start_date, $end_date]);
    $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC) ?: [];
} else {
    $sqlDetails = "SELECT name, phone, CONCAT(pickup_point, ' - ', drop_point) as rute, start_date as tanggal, price as final_price FROM charters WHERE start_date BETWEEN ? AND ? ORDER BY start_date DESC";
    $stmtDetails = $conn->prepare($sqlDetails);
    $stmtDetails->execute([$start_date, $end_date]);
    $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'reguler_total' => $regTotal,
    'reguler_count' => $regCount,
    'reguler_discount_total' => $regDiscount,
    'reguler_summary' => $regDetails,
    'carter_total' => $carTotal,
    'carter_count' => $carCount,
    'luggage_total' => $lugTotal,
    'luggage_count' => $lugCount,
    'details' => $details,
    'report_type' => $type
]);
exit;
