<?php
/**
 * AJAX Handler: assignDriver
 * Assigns a driver to a trip
 */

global $conn;

$rute = $_POST['rute'] ?? '';
$tanggal = $_POST['tanggal'] ?? '';
$jam = $_POST['jam'] ?? '';
$unit = isset($_POST['unit']) ? intval($_POST['unit']) : 0;
$driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;

header('Content-Type: application/json');

if (!$rute || !$tanggal || !$jam || $unit <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_params']);
    exit;
}

// Insert or Update assignment
$stmt = $conn->prepare("INSERT INTO trip_assignments (rute, tanggal, jam, unit, driver_id) VALUES (?, ?, ?, ?, ?) ON CONFLICT (rute, tanggal, jam, unit) DO UPDATE SET driver_id = EXCLUDED.driver_id");
try {
    $stmt->execute([$rute, $tanggal, $jam, $unit, $driver_id]);
    $driverName = '-';
    if ($driver_id > 0) {
        $stmtD = $conn->prepare("SELECT nama FROM drivers WHERE id = ?");
        $stmtD->execute([$driver_id]);
        $resD = $stmtD->fetch();
        if ($resD)
            $driverName = $resD['nama'];
    }
    echo json_encode(['success' => true, 'driver_name' => $driverName]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
