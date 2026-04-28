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
        $stmt = $conn->prepare("SELECT id, name, phone, pickup_point, address FROM customers WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($subAction === 'save') {
        $cid = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $name = strtoupper($name);
        $phone = trim($_POST['phone'] ?? '');
        $phone = preg_replace('/\D/', '', $phone);
        if (substr($phone, 0, 2) === '62') $phone = '0' . substr($phone, 2);
        if (substr($phone, 0, 1) === '8') $phone = '0' . $phone;
        if (strlen($phone) > 13) $phone = substr($phone, 0, 13);
        $pickup = trim($_POST['pickup_point'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!$name || !$phone) {
            echo json_encode(['success' => false, 'error' => 'Nama dan No HP wajib diisi']);
            exit;
        }

        // Check duplicate phone
        $stmtC = $conn->prepare("SELECT id, name FROM customers WHERE phone=? LIMIT 1");
        $stmtC->execute([$phone]);
        $existing = $stmtC->fetch();

        if ($existing) {
            if (($cid > 0 && $existing['id'] != $cid) || $cid == 0) {
                echo json_encode(['success' => false, 'error' => "No HP $phone sudah terdaftar atas nama " . $existing['name']]);
                exit;
            }
        }

        if ($cid > 0) {
            $oldStmt = $conn->prepare("SELECT name, phone FROM customers WHERE id=? LIMIT 1");
            $oldStmt->execute([$cid]);
            $old = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, pickup_point=?, address=? WHERE id=?");
            $stmt->execute([$name, $phone, $pickup, $address, $cid]);
            activity_log_write($conn, 'settings', 'customer', $cid, 'update', 'Customer diperbarui: ' . $name, 'Sebelumnya: ' . ($old['name'] ?? '-') . ' | ' . ($old['phone'] ?? '-'), $actor);
            echo json_encode(['success' => true, 'message' => 'Customer berhasil diperbarui']);
        } else {
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, address, pickup_point) VALUES (?,?,?,?)");
            $stmt->execute([$name, $phone, $address, $pickup]);
            $newId = $conn->lastInsertId();
            activity_log_write($conn, 'settings', 'customer', $newId, 'create', 'Customer ditambahkan: ' . $name, $phone, $actor);
            echo json_encode(['success' => true, 'message' => 'Customer berhasil ditambahkan']);
        }
        exit;
    }

    if ($subAction === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmtInfo = $conn->prepare("SELECT name, phone FROM customers WHERE id=? LIMIT 1");
        $stmtInfo->execute([$id]);
        $info = $stmtInfo->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'settings', 'customer', $id, 'delete', 'Customer dihapus: ' . ($info['name'] ?? ('ID ' . $id)), $info['phone'] ?? '', $actor);
            echo json_encode(['success' => true, 'message' => 'Customer berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Data tidak ditemukan']);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
}
