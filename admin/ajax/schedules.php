<?php
/**
 * AJAX Handler: getSchedules
 * Returns schedule times for a route on a given date
 */

$rute = $_GET['rute'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

if (!$rute || !$tanggal || !validDate($tanggal)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'invalid_params']);
    exit;
}

$dow = (int) date('w', strtotime($tanggal));
$stmt = $conn->prepare("SELECT id, jam, units FROM schedules WHERE rute=? AND dow=? ORDER BY jam");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}
$stmt->execute([$rute, $dow]);
$out = [];
while ($r = $stmt->fetch())
    $out[] = ['id' => intval($r['id']), 'jam' => substr($r['jam'], 0, 5), 'units' => intval($r['units'])];

header('Content-Type: application/json');
echo json_encode(['success' => true, 'schedules' => $out]);
exit;
