<?php
global $conn, $auth;
if (!isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'db_not_connected']);
    exit;
}

$subAction = $_REQUEST['subAction'] ?? '';
header('Content-Type: application/json');

try {
    $actor = activity_log_current_actor($auth ?? null);

    if ($subAction === 'get') {
        $id = intval($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM customer_bagasi WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($subAction === 'save') {
        $cid = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nama = trim($_POST['nama'] ?? '');
        $nama = strtoupper($nama);
        $hp = trim($_POST['hp'] ?? '');
        $hp = preg_replace('/\D/', '', $hp);
        if (substr($hp, 0, 2) === '62') $hp = '0' . substr($hp, 2);
        if (substr($hp, 0, 1) === '8') $hp = '0' . $hp;
        if (strlen($hp) > 13) $hp = substr($hp, 0, 13);
        $alamat = trim($_POST['alamat'] ?? '');
        $tipe = trim($_POST['tipe'] ?? 'keduanya');

        if (!$nama || !$hp) {
            echo json_encode(['success' => false, 'error' => 'Nama dan No HP wajib diisi']);
            exit;
        }

        if ($cid > 0) {
            $stmt = $conn->prepare("UPDATE customer_bagasi SET nama=?, no_hp=?, alamat=?, tipe=? WHERE id=?");
            $stmt->execute([$nama, $hp, $alamat, $tipe, $cid]);
            activity_log_write($conn, 'settings', 'customer_bagasi', $cid, 'update', 'Customer Bagasi diperbarui: ' . $nama, '', $actor);
            echo json_encode(['success' => true, 'message' => 'Customer Bagasi berhasil diperbarui']);
        } else {
            $stmt = $conn->prepare("INSERT INTO customer_bagasi (nama, no_hp, alamat, tipe) VALUES (?,?,?,?)");
            $stmt->execute([$nama, $hp, $alamat, $tipe]);
            $newId = $conn->lastInsertId();
            activity_log_write($conn, 'settings', 'customer_bagasi', $newId, 'create', 'Customer Bagasi ditambahkan: ' . $nama, '', $actor);
            echo json_encode(['success' => true, 'message' => 'Customer Bagasi berhasil ditambahkan']);
        }
        exit;
    }

    if ($subAction === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmtInfo = $conn->prepare("SELECT nama FROM customer_bagasi WHERE id=? LIMIT 1");
        $stmtInfo->execute([$id]);
        $name = (string) ($stmtInfo->fetchColumn() ?: '');
        $stmt = $conn->prepare("DELETE FROM customer_bagasi WHERE id=?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'settings', 'customer_bagasi', $id, 'delete', 'Customer Bagasi dihapus: ' . ($name ?: ('ID ' . $id)), '', $actor);
            echo json_encode(['success' => true, 'message' => 'Customer Bagasi berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Data tidak ditemukan']);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
