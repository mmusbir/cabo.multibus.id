<?php
/**
 * admin/ajax/get_schedule_seats.php - Fetch seat layout and occupied seats
 */

global $conn;

$rute = $_GET['rute'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$jam = $_GET['jam'] ?? '';
$unit = isset($_GET['unit']) ? intval($_GET['unit']) : 1;

if (empty($rute) || empty($tanggal) || empty($jam)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// 1. Get Layout from Unit
$dow = date('w', strtotime($tanggal));
$stmt = $conn->prepare("SELECT s.unit_id, u.layout FROM schedules s LEFT JOIN units u ON s.unit_id = u.id WHERE s.rute = ? AND s.dow = ? AND s.jam LIKE ? LIMIT 1");
$jam_like = $jam . '%';
$stmt->execute([$rute, $dow, $jam_like]);
$sData = $stmt->fetch();

$layout = [];
if ($sData && !empty($sData['layout'])) {
    $layout = json_decode($sData['layout'], true);
}

// 2. Get Occupied Seats
$occupied = [];
$stmt = $conn->prepare("SELECT seat FROM bookings WHERE rute=? AND tanggal=? AND jam LIKE ? AND unit=? AND status!='canceled'");
$stmt->execute([$rute, $tanggal, $jam_like, $unit]);
while ($row = $stmt->fetch()) {
    $occupied[] = (string) $row['seat'];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'layout' => $layout,
    'occupied' => $occupied
]);
exit;
