<?php
/**
 * admin/ajax/luggage_service_crud.php - CRUD for luggage services
 */

global $conn;

$subAction = $_POST['subAction'] ?? '';
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);

header('Content-Type: application/json');

if ($subAction === 'save') {
    if (empty($name)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Nama layanan tidak boleh kosong']);
        exit;
    }

    if ($id > 0) {
        // Update
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

    $stmt = $conn->prepare("DELETE FROM luggage_services WHERE id=?");
    ob_clean();
    try {
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'error' => 'invalid_sub_action']);
exit;
