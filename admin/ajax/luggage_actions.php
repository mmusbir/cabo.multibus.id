<?php
/**
 * admin/ajax/luggage_actions.php - Handle status updates for luggage
 */

global $conn;
require_once __DIR__ . '/../../config/activity_log.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = $_GET['action'] ?? '';
$actor = activity_log_current_actor();



header('Content-Type: application/json');

if ($action === 'getTrackingLogs') {
    $resi = trim($_GET['resi'] ?? '');
    if (!$resi) {
        echo json_encode(['success' => false, 'error' => 'Resi tidak valid']);
        exit;
    }
    try {
        $stmt = $conn->prepare("SELECT status, notes, created_at, created_by_username FROM bagasi_logs WHERE kode_resi = ? ORDER BY created_at DESC");
        $stmt->execute([$resi]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'inputLuggageRaw') {
    $sender_name = strtoupper(trim($_POST['sender_name'] ?? ''));
    $sender_phone = preg_replace('/\D/', '', trim($_POST['sender_phone'] ?? ''));
    $sender_address = trim($_POST['sender_address'] ?? '');
    
    $receiver_name = strtoupper(trim($_POST['receiver_name'] ?? ''));
    $receiver_phone = preg_replace('/\D/', '', trim($_POST['receiver_phone'] ?? ''));
    $receiver_address = trim($_POST['receiver_address'] ?? '');
    
    // Support both field names: form uses service_id, internal alias is layanan_id
    $layanan_id = intval($_POST['service_id'] ?? $_POST['layanan_id'] ?? 0);
    $rute_id = intval($_POST['rute_id'] ?? 0);
    
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $notes = trim($_POST['notes'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $payment_status = trim($_POST['payment_status'] ?? 'Belum Lunas');
    
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $unit_id = isset($_POST['unit_id']) && $_POST['unit_id'] !== '' ? intval($_POST['unit_id']) : null;

    $missing = [];
    if (!$sender_name) $missing[] = 'Nama Pengirim';
    if (!$sender_phone) $missing[] = 'No HP Pengirim';
    if (!$receiver_name) $missing[] = 'Nama Penerima';
    if (!$receiver_phone) $missing[] = 'No HP Penerima';
    if (!$layanan_id) $missing[] = 'Jenis Layanan';
    if (!$rute_id) $missing[] = 'Rute Perjalanan';
    if (!empty($missing)) {
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap: ' . implode(', ', $missing)]);
        exit;
    }

    try {
        $conn->beginTransaction();

        // 1. Get or Create Sender
        $stmtS = $conn->prepare("SELECT id FROM customer_bagasi WHERE no_hp = ? LIMIT 1");
        $stmtS->execute([$sender_phone]);
        $sender = $stmtS->fetch();
        if ($sender) {
            $pengirim_id = $sender['id'];
        } else {
            $stmtInsS = $conn->prepare("INSERT INTO customer_bagasi (nama, no_hp, alamat, tipe) VALUES (?, ?, ?, 'pengirim')");
            $stmtInsS->execute([$sender_name, $sender_phone, $sender_address]);
            $pengirim_id = $conn->lastInsertId();
        }

        // 2. Get or Create Receiver
        $stmtR = $conn->prepare("SELECT id FROM customer_bagasi WHERE no_hp = ? LIMIT 1");
        $stmtR->execute([$receiver_phone]);
        $receiver = $stmtR->fetch();
        if ($receiver) {
            $penerima_id = $receiver['id'];
        } else {
            $stmtInsR = $conn->prepare("INSERT INTO customer_bagasi (nama, no_hp, alamat, tipe) VALUES (?, ?, ?, 'penerima')");
            $stmtInsR->execute([$receiver_name, $receiver_phone, $receiver_address]);
            $penerima_id = $conn->lastInsertId();
        }

        // 3. Generate Kode Resi (BGS-YYYYMMDD-XXXX)
        $dateStr = date('Ymd');
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM luggages WHERE kode_resi LIKE ?");
        $stmtCount->execute(["BGS-{$dateStr}-%"]);
        $countToday = $stmtCount->fetchColumn() + 1;
        $kode_resi = "BGS-{$dateStr}-" . str_pad($countToday, 4, '0', STR_PAD_LEFT);

        // Fetch rute name
        $stmtRute = $conn->prepare("SELECT name FROM routes WHERE id = ?");
        $stmtRute->execute([$rute_id]);
        $ruteName = $stmtRute->fetchColumn() ?: '';

        // 4. Insert Luggage
        $stmt = $conn->prepare("INSERT INTO luggages (
            kode_resi, pengirim_id, penerima_id, rute_id, layanan_id, service_id, 
            sender_name, sender_phone, sender_address, receiver_name, receiver_phone, receiver_address, 
            quantity, notes, price, payment_status, rute, tanggal, unit_id, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        
        $stmt->execute([
            $kode_resi, $pengirim_id, $penerima_id, $rute_id, $layanan_id, $layanan_id,
            $sender_name, $sender_phone, $sender_address, $receiver_name, $receiver_phone, $receiver_address,
            $quantity, $notes, $price, $payment_status, $ruteName, $tanggal, $unit_id
        ]);
        $luggage_id = $conn->lastInsertId();

        // 5. Insert Log
        $stmtLog = $conn->prepare("INSERT INTO bagasi_logs (kode_resi, status, notes, created_by_username) VALUES (?, 'Pending', 'Bagasi diterima', ?)");
        $stmtLog->execute([$kode_resi, 'System']);

        $conn->commit();

        activity_log_write($conn, 'luggage', 'luggage', $luggage_id, 'create', 'Bagasi ditambahkan: ' . $kode_resi, $sender_name . ' -> ' . $receiver_name, $actor);
        echo json_encode(['success' => true, 'message' => 'Bagasi berhasil ditambahkan', 'kode_resi' => $kode_resi]);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID tidak valid (' . $id . ')']);
    exit;
}

if ($action === 'markLuggagePaid') {
    $infoStmt = $conn->prepare("SELECT sender_name, receiver_name FROM luggages WHERE id = ? LIMIT 1");
    $infoStmt->execute([$id]);
    $luggageInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE luggages SET payment_status = 'Lunas' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'luggage', 'luggage', $id, 'mark_paid', 'Pembayaran bagasi ditandai lunas', ($luggageInfo['sender_name'] ?? 'Pengirim') . ' -> ' . ($luggageInfo['receiver_name'] ?? 'Penerima'), $actor);
        }
        echo json_encode(['success' => true, 'message' => 'Pembayaran lunas']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'inputLuggage') {
    $infoStmt = $conn->prepare("SELECT sender_name, receiver_name FROM luggages WHERE id = ? LIMIT 1");
    $infoStmt->execute([$id]);
    $luggageInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE luggages SET status = 'active' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'luggage', 'luggage', $id, 'activate', 'Status bagasi diinput sebagai aktif', ($luggageInfo['sender_name'] ?? 'Pengirim') . ' -> ' . ($luggageInfo['receiver_name'] ?? 'Penerima'), $actor);
        }
        echo json_encode(['success' => true, 'message' => 'Status barang sampai']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'markLuggageDone') {
    $infoStmt = $conn->prepare("SELECT sender_name, receiver_name FROM luggages WHERE id = ? LIMIT 1");
    $infoStmt->execute([$id]);
    $luggageInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE luggages SET status = 'done' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'luggage', 'luggage', $id, 'complete', 'Bagasi telah diambil oleh penerima (Selesai)', ($luggageInfo['sender_name'] ?? 'Pengirim') . ' -> ' . ($luggageInfo['receiver_name'] ?? 'Penerima'), $actor);
        }
        
        // Log ke tracking bagasi
        $infoStmt = $conn->prepare("SELECT kode_resi FROM luggages WHERE id = ? LIMIT 1");
        $infoStmt->execute([$id]);
        $kode_resi = $infoStmt->fetchColumn();
        if ($kode_resi) {
            $stmtLog = $conn->prepare("INSERT INTO bagasi_logs (kode_resi, status, notes, created_by_username) VALUES (?, ?, ?, ?)");
            $stmtLog->execute([$kode_resi, 'Selesai', 'Diambil oleh penerima', $actor]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Bagasi ditandai sudah diambil (Selesai)']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'cancelLuggage') {
    $infoStmt = $conn->prepare("SELECT sender_name, receiver_name FROM luggages WHERE id = ? LIMIT 1");
    $infoStmt->execute([$id]);
    $luggageInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE luggages SET status = 'canceled' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'luggage', 'luggage', $id, 'cancel', 'Pengiriman bagasi dibatalkan', ($luggageInfo['sender_name'] ?? 'Pengirim') . ' -> ' . ($luggageInfo['receiver_name'] ?? 'Penerima'), $actor);
        }
        echo json_encode(['success' => true, 'message' => 'Pengiriman dibatalkan']);
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
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'luggage', 'luggage', $id, 'update', 'Status bagasi diperbarui menjadi ' . $status, 'Bagasi ID ' . $id, $actor);
        }
        
        // Handle bagasi logs
        $infoStmt = $conn->prepare("SELECT kode_resi FROM luggages WHERE id = ? LIMIT 1");
        $infoStmt->execute([$id]);
        $kode_resi = $infoStmt->fetchColumn();
        if ($kode_resi) {
            $stmtLog = $conn->prepare("INSERT INTO bagasi_logs (kode_resi, status, notes, created_by_username) VALUES (?, ?, ?, ?)");
            $stmtLog->execute([$kode_resi, $status, 'Diperbarui ke ' . $status, $actor]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'invalid_action']);
exit;
