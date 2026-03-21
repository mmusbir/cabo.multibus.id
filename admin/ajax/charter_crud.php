<?php
/**
 * admin/ajax/charter_crud.php - Handle charter CRUD operations
 */

global $conn;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// delete_charter
if ($action === 'delete_charter') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM charters WHERE id = ?");
    try {
        $stmt->execute([$id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// get_charter
if ($action === 'get_charter') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM charters WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not found']);
    }
    exit;
}

// update_charter
if ($action === 'update_charter' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $pickup_point = trim($_POST['pickup_point'] ?? '');
    $drop_point = trim($_POST['drop_point'] ?? '');
    $unit_id = intval($_POST['unit_id'] ?? 0);
    $driver_name = trim($_POST['driver_name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $layanan = trim($_POST['layanan'] ?? 'Regular');
    $bop_price = floatval($_POST['bop_price'] ?? 0);

    $stmt = $conn->prepare("UPDATE charters SET name=?, company_name=?, phone=?, start_date=?, end_date=?, departure_time=?, pickup_point=?, drop_point=?, unit_id=?, driver_name=?, price=?, layanan=?, bop_price=? WHERE id=?");
    try {
        $stmt->execute([$name, $company_name, $phone, $start_date, $end_date, $departure_time, $pickup_point, $drop_point, $unit_id, $driver_name, $price, $layanan, $bop_price, $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// get_units (for charter edit dropdown)
if ($action === 'get_units') {
    $res = $conn->query("SELECT id, nopol, merek FROM units ORDER BY nopol");
    $units = [];
    while ($u = $res->fetch()) {
        $units[] = $u;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'units' => $units]);
    exit;
}

// get_charter_routes (for charter edit dropdown)
if ($action === 'get_charter_routes') {
    $res = $conn->query("SELECT id, name, origin, destination, duration, rental_price, bop_price FROM master_carter ORDER BY name");
    $routes = [];
    while ($r = $res->fetch()) {
        $routes[] = $r;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'routes' => $routes]);
    exit;
}

// get_drivers (for charter edit dropdown)
if ($action === 'get_drivers') {
    $res = $conn->query("SELECT id, nama FROM drivers ORDER BY nama");
    $drivers = [];
    while ($d = $res->fetch()) {
        $drivers[] = $d;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'drivers' => $drivers]);
    exit;
}

// toggle_bop - mark BOP as done
if ($action === 'toggle_bop') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE charters SET bop_status = 'done' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'unknown_action']);
exit;
