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

if ($action === 'inputLuggageRaw') {
    $sender_name = trim($_POST['sender_name'] ?? '');
    $sender_phone = trim($_POST['sender_phone'] ?? '');
    $sender_address = trim($_POST['sender_address'] ?? '');
    $receiver_name = trim($_POST['receiver_name'] ?? '');
    $receiver_phone = trim($_POST['receiver_phone'] ?? '');
    $receiver_address = trim($_POST['receiver_address'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $notes = trim($_POST['notes'] ?? '');
    $price = floatval($_POST['price'] ?? 0);

    if (!$sender_name || !$sender_phone || !$receiver_name || !$receiver_phone || !$service_id) {
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO luggages (sender_name, sender_phone, sender_address, receiver_name, receiver_phone, receiver_address, service_id, quantity, notes, price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$sender_name, $sender_phone, $sender_address, $receiver_name, $receiver_phone, $receiver_address, $service_id, $quantity, $notes, $price]);
        echo json_encode(['success' => true, 'message' => 'Bagasi berhasil ditambahkan']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'updateLuggageSimple') {
    $status = $_POST['status'] ?? '';
    if (!$status) {
        echo json_encode(['success' => false, 'error' => 'Status tidak valid']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE luggages SET status = ? WHERE id = ?");
    try {
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'invalid_action']);
exit;
