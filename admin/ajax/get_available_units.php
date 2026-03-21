<?php
/**
 * admin/ajax/get_available_units.php - Fetch scheduled units count
 */

global $conn;

$rute = $_GET['rute'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$jam = $_GET['jam'] ?? '';

if (empty($rute) || empty($tanggal) || empty($jam)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Convert date to day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
$dow = date('w', strtotime($tanggal));

$stmt = $conn->prepare("SELECT units FROM schedules WHERE rute = ? AND dow = ? AND jam LIKE ? LIMIT 1");
$jam_like = $jam . '%';
$stmt->execute([$rute, $dow, $jam_like]);
$row = $stmt->fetch();
$units_count = $row ? intval($row['units']) : 1; // Default to 1 if not found

header('Content-Type: application/json');
echo json_encode(['success' => true, 'units' => $units_count]);
exit;
