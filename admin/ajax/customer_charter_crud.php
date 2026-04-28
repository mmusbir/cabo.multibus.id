<?php
/**
 * admin/ajax/customer_charter_crud.php - CRUD for customer charter
 */

global $conn;
require_once __DIR__ . '/../../config/activity_log.php';

$subAction = $_POST['subAction'] ?? $_GET['subAction'] ?? '';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$perusahaan = trim($_POST['perusahaan'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$actor = activity_log_current_actor();

header('Content-Type: application/json');

if ($subAction === 'save') {
    if (empty($nama) || empty($no_hp)) {
        echo json_encode(['success' => false, 'error' => 'Nama dan No. HP wajib diisi']);
        exit;
    }

    if ($id > 0) {
        $oldStmt = $conn->prepare("SELECT nama FROM customer_charter WHERE id=? LIMIT 1");
        $oldStmt->execute([$id]);
        $oldName = (string) ($oldStmt->fetchColumn() ?: '');
        $stmt = $conn->prepare("UPDATE customer_charter SET nama=?, perusahaan=?, no_hp=?, alamat=? WHERE id=?");
        $params = [$nama, $perusahaan, $no_hp, $alamat, $id];
    } else {
        $stmt = $conn->prepare("INSERT INTO customer_charter (nama, perusahaan, no_hp, alamat) VALUES (?, ?, ?, ?)");
        $params = [$nama, $perusahaan, $no_hp, $alamat];
    }

    try {
        $stmt->execute($params);
        $currentId = ($id > 0) ? $id : $conn->lastInsertId();
        
        if ($id > 0) {
            activity_log_write($conn, 'settings', 'customer_charter', $id, 'update', 'Customer carter diperbarui: ' . $nama, 'Sebelumnya: ' . ($oldName ?? '-'), $actor);
        } else {
            activity_log_write($conn, 'settings', 'customer_charter', $currentId, 'create', 'Customer carter ditambahkan: ' . $nama, 'Perusahaan: ' . ($perusahaan ?: '-'), $actor);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($subAction === 'delete') {
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
        exit;
    }

    $infoStmt = $conn->prepare("SELECT nama FROM customer_charter WHERE id=? LIMIT 1");
    $infoStmt->execute([$id]);
    $custName = (string) ($infoStmt->fetchColumn() ?: '');
    $stmt = $conn->prepare("DELETE FROM customer_charter WHERE id=?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'settings', 'customer_charter', $id, 'delete', 'Customer carter dihapus: ' . ($custName ?: ('ID ' . $id)), '', $actor);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($subAction === 'get') {
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
        exit;
    }
    try {
        $stmt = $conn->prepare("SELECT id, nama, perusahaan, no_hp, alamat FROM customer_charter WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Data tidak ditemukan']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'invalid_sub_action']);
exit;
