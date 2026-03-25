<?php
/**
 * admin/ajax/luggage_service_crud.php - CRUD for luggage services
 */

global $conn;
require_once __DIR__ . '/../../config/activity_log.php';

$subAction = $_POST['subAction'] ?? '';
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$actor = activity_log_current_actor();

header('Content-Type: application/json');

if ($subAction === 'save') {
    if (empty($name)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Nama layanan tidak boleh kosong']);
        exit;
    }

    if ($id > 0) {
        // Update
        $oldStmt = $conn->prepare("SELECT name FROM luggage_services WHERE id=? LIMIT 1");
        $oldStmt->execute([$id]);
        $oldName = (string) ($oldStmt->fetchColumn() ?: '');
        $stmt = $conn->prepare("UPDATE luggage_services SET name=?, price=? WHERE id=?");
        $params = [$name, $price, $id];
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO luggage_services (name, price) VALUES (?, ?)");
        $params = [$name, $price];
    }

    ob_clean();
    try {
        $stmt->execute($params);
        if ($id > 0) {
            activity_log_write($conn, 'settings', 'luggage_service', $id, 'update', 'Layanan bagasi diperbarui: ' . $name, 'Sebelumnya: ' . ($oldName ?? '-'), $actor);
        } else {
            activity_log_write($conn, 'settings', 'luggage_service', $conn->lastInsertId(), 'create', 'Layanan bagasi ditambahkan: ' . $name, 'Harga: ' . $price, $actor);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($subAction === 'delete') {
    if ($id <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
        exit;
    }

    $infoStmt = $conn->prepare("SELECT name FROM luggage_services WHERE id=? LIMIT 1");
    $infoStmt->execute([$id]);
    $serviceName = (string) ($infoStmt->fetchColumn() ?: '');
    $stmt = $conn->prepare("DELETE FROM luggage_services WHERE id=?");
    ob_clean();
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'settings', 'luggage_service', $id, 'delete', 'Layanan bagasi dihapus: ' . ($serviceName ?: ('ID ' . $id)), '', $actor);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'error' => 'invalid_sub_action']);
exit;
