<?php
/**
 * AJAX Handler: getSchedules
 * Returns schedule times for a route on a given date
 */

$rute = $_GET['rute'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

header('Content-Type: application/json');

if (!$rute || !$tanggal || !validDate($tanggal)) {
    echo json_encode(['success' => false, 'error' => 'invalid_params', 'message' => 'Rute, tanggal, atau format tanggal tidak valid']);
    exit;
}

try {
    $dow = (int) date('w', strtotime($tanggal));
    $stmt = $conn->prepare("SELECT id, jam, units FROM schedules WHERE rute=? AND dow=? ORDER BY jam");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Gagal menyiapkan query database']);
        exit;
    }
    
    $stmt->execute([$rute, $dow]);
    $out = [];
    
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out[] = [
            'id' => intval($r['id']), 
            'jam' => substr($r['jam'], 0, 5), 
            'units' => intval($r['units'])
        ];
    }
    
    // If no schedules found, return success with empty array
    // (not an error - user just doesn't have schedules for this route/date)
    echo json_encode(['success' => true, 'schedules' => $out]);
    exit;
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'db_exception', 
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
    exit;
}
?>
