<?php
/**
 * admin/ajax/luggage_actions.php - Handle status updates for luggage
 */

global $conn;

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = $_GET['action'] ?? '';

// Temporary Debug Log
file_put_contents(__DIR__ . '/../../debug_actions.log', date('Y-m-d H:i:s') . " - Action: $action, ID: $id\n", FILE_APPEND);

// ob_clean(); // Commented out to see if it reveals anything
// error_reporting(0); // Commented out to see errors
header('Content-Type: application/json');

if ($id <= 0) {
    file_put_contents(__DIR__ . '/../../debug_actions.log', "  Result: ID Invalid\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'ID tidak valid (' . $id . ')']);
    exit;
}

if ($action === 'markLuggagePaid') {
    $stmt = $conn->prepare("UPDATE luggages SET payment_status = 'Lunas' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Pembayaran lunas']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'inputLuggage') {
    $stmt = $conn->prepare("UPDATE luggages SET status = 'active' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Status barang sampai']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'cancelLuggage') {
    $stmt = $conn->prepare("UPDATE luggages SET status = 'canceled' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Pengiriman dibatalkan']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'invalid_action']);
exit;
