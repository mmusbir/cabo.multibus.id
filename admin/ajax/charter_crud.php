<?php
/**
 * admin/ajax/charter_crud.php - Handle charter CRUD operations
 */

global $conn;
require_once __DIR__ . '/../../config/activity_log.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$actor = activity_log_current_actor();

function charter_parse_currency_input(string $value): float
{
    $normalized = preg_replace('/[^0-9,.-]/', '', $value);
    $normalized = str_replace('.', '', $normalized);
    $normalized = str_replace(',', '.', $normalized);
    return (float) $normalized;
}

function charter_parse_route_text(string $value): array
{
    $value = trim(preg_replace('/\s+/', ' ', $value));
    if ($value === '') {
        return ['', ''];
    }

    foreach (['->', ' - ', ' -- ', ' to ', ' ke '] as $separator) {
        if (stripos($value, $separator) !== false) {
            $parts = preg_split('/' . preg_quote($separator, '/') . '/i', $value, 2);
            return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
        }
    }

    if (strpos($value, '-') !== false) {
        $parts = explode('-', $value, 2);
        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }

    return [$value, ''];
}

if ($action === 'create_charter' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper(trim($_POST['name'] ?? ''));
    $phone = preg_replace('/\s+/', '', trim($_POST['phone'] ?? ''));
    $pickupPoint = trim($_POST['pickup_point'] ?? '');
    $dropPoint = trim($_POST['drop_point'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    // Accept end_date directly; fallback to duration_days if not provided
    $endDateRaw = trim($_POST['end_date'] ?? '');
    $durationDays = max(1, (int) ($_POST['duration_days'] ?? 1));
    $departureTime = trim($_POST['departure_time'] ?? '08:30') ?: '08:30';
    $busType = trim($_POST['bus_type'] ?? 'Big Bus') ?: 'Big Bus';
    $unitId = intval($_POST['unit_id'] ?? 0);
    $driverName = trim($_POST['driver_name'] ?? '');
    $price = charter_parse_currency_input((string) ($_POST['price'] ?? '0'));
    $bopPrice = charter_parse_currency_input((string) ($_POST['bop_price'] ?? '0'));
    $downPayment = charter_parse_currency_input((string) ($_POST['down_payment'] ?? '0'));
    $paymentStatus = trim($_POST['payment_status'] ?? 'Belum Bayar');
    $perusahaan = trim($_POST['perusahaan'] ?? '');
    $duration = trim($_POST['duration'] ?? ''); // Jenis Layanan (e.g. DROP OFF, 2D1N)

    $errors = [];
    if ($name === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if ($phone === '') {
        $errors[] = 'Nomor telepon wajib diisi.';
    }
    if ($pickupPoint === '') {
        $errors[] = 'Lokasi penjemputan wajib diisi.';
    }
    if ($dropPoint === '') {
        $errors[] = 'Tujuan destinasi wajib diisi.';
    }
    if ($startDate === '') {
        $errors[] = 'Tanggal keberangkatan wajib diisi.';
    }
    if ($unitId <= 0) {
        $errors[] = 'Unit kendaraan wajib dipilih.';
    }

    if ($errors) {
        $_SESSION['charter_create_errors'] = $errors;
        $_SESSION['charter_create_old'] = $_POST;
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
        } else {
            header('Location: admin.php?open=charter-create#charter-create');
        }
        exit;
    }

    // Compute end date: prefer explicit end_date field, else use start_date + duration
    if ($endDateRaw !== '' && strtotime($endDateRaw)) {
        $endDate = date('Y-m-d', strtotime($endDateRaw));
    } else {
        $endDate = date('Y-m-d', strtotime($startDate . ' +' . max(0, $durationDays - 1) . ' days'));
    }

    // Validate allowed payment statuses
    $allowedStatuses = ['Lunas', 'DP', 'Belum Bayar'];
    if (!in_array($paymentStatus, $allowedStatuses)) {
        $paymentStatus = 'Belum Bayar';
    }

    $stmt = $conn->prepare("INSERT INTO charters (\"name\", \"company_name\", \"phone\", \"start_date\", \"end_date\", \"departure_time\", \"pickup_point\", \"drop_point\", \"unit_id\", \"driver_name\", \"price\", \"layanan\", \"bop_price\", \"down_payment\", \"payment_status\", \"duration\", \"created_at\") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    try {
        $stmt->execute([
            $name,
            $perusahaan,
            $phone,
            $startDate,
            $endDate,
            $departureTime,
            $pickupPoint,
            $dropPoint,
            $unitId,
            $driverName,
            $price,
            $busType,
            $bopPrice,
            $downPayment,
            $paymentStatus,
            $duration,
        ]);
        $newCharterId = $conn->lastInsertId();
        activity_log_write($conn, 'charter', 'charter', $newCharterId, 'create', 'Carter ditambahkan: ' . $name, $pickupPoint . ' -> ' . $dropPoint . ' | ' . $startDate . ' ' . $departureTime, $actor);

        // Auto-save new route to master_carter if it doesn't exist
        try {
            $routeCheck = $conn->prepare("SELECT id FROM master_carter WHERE origin ILIKE ? AND destination ILIKE ? LIMIT 1");
            $routeCheck->execute([$pickupPoint, $dropPoint]);
            if (!$routeCheck->fetchColumn()) {
                $routeName = $pickupPoint . ' - ' . $dropPoint;
                $serviceType = $duration !== '' ? $duration : ($durationDays . ' Hari');
                $conn->prepare("INSERT INTO master_carter (name, origin, destination, duration, rental_price, bop_price) VALUES (?, ?, ?, ?, ?, ?)")
                     ->execute([$routeName, $pickupPoint, $dropPoint, $serviceType, $price, $bopPrice]);
            }
        } catch (Throwable $e) {
            // Non-fatal, continue
        }

        // Upsert customer into customer_charter table
        try {
            $existCheck = $conn->prepare("SELECT id FROM customer_charter WHERE no_hp = ? LIMIT 1");
            $existCheck->execute([$phone]);
            $existCustomer = $existCheck->fetchColumn();
            if ($existCustomer) {
                // Update name/perusahaan if exists
                $conn->prepare("UPDATE customer_charter SET nama = ?, perusahaan = COALESCE(NULLIF(?, ''), perusahaan) WHERE id = ?")
                     ->execute([$name, $perusahaan, $existCustomer]);
            } else {
                $conn->prepare("INSERT INTO customer_charter (nama, perusahaan, no_hp, alamat) VALUES (?, ?, ?, ?)")
                     ->execute([$name, $perusahaan, $phone, '']);
            }
        } catch (Throwable $ce) {
            // Non-fatal: continue even if customer upsert fails
        }

        $_SESSION['booking_msg'] = 'Data carter berhasil disimpan.';
        unset($_SESSION['charter_create_errors'], $_SESSION['charter_create_old']);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'id' => $newCharterId, 'message' => 'Data carter berhasil disimpan.']);
        } else {
            header('Location: admin.php?booking_mode=charters#bookings');
        }
        exit;
    } catch (PDOException $e) {
        $_SESSION['charter_create_errors'] = ['Gagal menyimpan carter: ' . $e->getMessage()];
        $_SESSION['charter_create_old'] = $_POST;
        header('Location: admin.php?open=charter-create#charter-create');
        exit;
    }
}

// delete_charter
if ($action === 'delete_charter') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $infoStmt = $conn->prepare("SELECT name, pickup_point, drop_point FROM charters WHERE id = ? LIMIT 1");
    $infoStmt->execute([$id]);
    $charterInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("DELETE FROM charters WHERE id = ?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'charter', 'charter', $id, 'delete', 'Carter dihapus: ' . ($charterInfo['name'] ?? ('ID ' . $id)), ($charterInfo['pickup_point'] ?? '-') . ' -> ' . ($charterInfo['drop_point'] ?? '-'), $actor);
        }
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
    $company_name = trim($_POST['perusahaan'] ?? $_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $pickup_point = trim($_POST['pickup_point'] ?? '');
    $drop_point = trim($_POST['drop_point'] ?? '');
    $unit_id = intval($_POST['unit_id'] ?? 0);
    $driver_name = trim($_POST['driver_name'] ?? '');
    $price = charter_parse_currency_input((string) ($_POST['price'] ?? '0'));
    $down_payment = charter_parse_currency_input((string) ($_POST['down_payment'] ?? '0'));
    $payment_status = trim($_POST['payment_status'] ?? 'Belum Bayar');
    $layanan = trim($_POST['bus_type'] ?? $_POST['layanan'] ?? 'Big Bus');
    $duration = trim($_POST['duration'] ?? ''); // Jenis Layanan
    $bop_price = floatval($_POST['bop_price'] ?? 0);

    $oldStmt = $conn->prepare("SELECT name, pickup_point, drop_point FROM charters WHERE id=? LIMIT 1");
    $oldStmt->execute([$id]);
    $oldCharter = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE charters SET \"name\"=?, \"company_name\"=?, \"phone\"=?, \"start_date\"=?, \"end_date\"=?, \"departure_time\"=?, \"pickup_point\"=?, \"drop_point\"=?, \"unit_id\"=?, \"driver_name\"=?, \"price\"=?, \"layanan\"=?, \"bop_price\"=?, \"down_payment\"=?, \"payment_status\"=?, \"duration\"=? WHERE \"id\"=?");
    try {
        $stmt->execute([$name, $company_name, $phone, $start_date, $end_date, $departure_time, $pickup_point, $drop_point, $unit_id, $driver_name, $price, $layanan, $bop_price, $down_payment, $payment_status, $duration, $id]);
        
        // Auto-upsert to customer_charter (MySQL-compatible)
        $actor = $_SESSION['username'] ?? 'system';
        try {
            $existStmt = $conn->prepare("SELECT id FROM customer_charter WHERE no_hp = ? LIMIT 1");
            $existStmt->execute([$phone]);
            $existId = $existStmt->fetchColumn();
            if ($existId) {
                $conn->prepare("UPDATE customer_charter SET nama = ?, perusahaan = ? WHERE id = ?")
                     ->execute([$name, $company_name, $existId]);
            } else {
                $conn->prepare("INSERT INTO customer_charter (nama, no_hp, perusahaan, created_at) VALUES (?, ?, ?, NOW())")
                     ->execute([$name, $phone, $company_name]);
            }
        } catch (Throwable $ce) {
            // Non-fatal
        }

        // Auto-save new route to master_carter if it doesn't exist
        try {
            $routeCheck = $conn->prepare("SELECT id FROM master_carter WHERE origin ILIKE ? AND destination ILIKE ? LIMIT 1");
            $routeCheck->execute([$pickup_point, $drop_point]);
            if (!$routeCheck->fetchColumn()) {
                $routeName = $pickup_point . ' - ' . $drop_point;
                $durDays = max(1, round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);
                $serviceType = $duration !== '' ? $duration : ($durDays . ' Hari');
                $conn->prepare("INSERT INTO master_carter (name, origin, destination, duration, rental_price, bop_price) VALUES (?, ?, ?, ?, ?, ?)")
                     ->execute([$routeName, $pickup_point, $drop_point, $serviceType, $price, $bop_price]);
            }
        } catch (Throwable $e) {
            // Non-fatal, continue
        }

        activity_log_write($conn, 'charter', 'charter', $id, 'update', 'Carter diperbarui: ' . $name, 'Sebelumnya: ' . ($oldCharter['name'] ?? '-') . ' | ' . ($oldCharter['pickup_point'] ?? '-') . ' -> ' . ($oldCharter['drop_point'] ?? '-'), $actor);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Data carter berhasil diperbarui.']);
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
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'charter', 'charter', $id, 'bop_done', 'Status BOP carter ditandai selesai', 'Charter ID ' . $id, $actor);
        }
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
