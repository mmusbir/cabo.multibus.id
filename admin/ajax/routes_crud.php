<?php
global $conn;
require_once __DIR__ . '/../../config/activity_log.php';

$subAction = $_REQUEST['subAction'] ?? '';
$type = $_REQUEST['type'] ?? 'reguler';
header('Content-Type: application/json');

try {
    $actor = activity_log_current_actor($auth ?? null);

    if ($subAction === 'get') {
        $id = intval($_GET['id'] ?? 0);
            if ($type === 'carter') {
                $stmt = $conn->prepare("SELECT id, name, origin, destination, duration, rental_price, bop_price, notes FROM master_carter WHERE id=? LIMIT 1");
        } else {
            $stmt = $conn->prepare("SELECT id, name, origin, destination, distance_km, duration_minutes, created_at FROM routes WHERE id=? LIMIT 1");
        }
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($subAction === 'save') {
        $route_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $origin = trim($_POST['origin'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $name = "$origin - $destination";

        if (!$origin || !$destination) {
            echo json_encode(['success' => false, 'error' => 'Asal dan Tujuan wajib diisi']);
            exit;
        }

        if ($type === 'carter') {
            $duration = trim($_POST['duration'] ?? '');
            $rental = floatval($_POST['rental_price'] ?? 0);
            $bop = floatval($_POST['bop_price'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');

            if ($route_id > 0) {
                $oldStmt = $conn->prepare("SELECT name, origin, destination FROM master_carter WHERE id=? LIMIT 1");
                $oldStmt->execute([$route_id]);
                $oldRoute = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $stmt = $conn->prepare("UPDATE master_carter SET name=?, origin=?, destination=?, duration=?, rental_price=?, bop_price=?, notes=? WHERE id=?");
                $stmt->execute([$name, $origin, $destination, $duration, $rental, $bop, $notes, $route_id]);
                activity_log_write($conn, 'settings', 'master_carter', $route_id, 'update', 'Master carter diperbarui: ' . $name, 'Sebelumnya: ' . trim(($oldRoute['name'] ?? '-') . ' | ' . ($oldRoute['origin'] ?? '-') . ' -> ' . ($oldRoute['destination'] ?? '-')), $actor);
                echo json_encode(['success' => true, 'message' => 'Rute Carter berhasil diperbarui']);
            } else {
                $stmt = $conn->prepare("INSERT INTO master_carter(name, origin, destination, duration, rental_price, bop_price, notes) VALUES(?,?,?,?,?,?,?)");
                $stmt->execute([$name, $origin, $destination, $duration, $rental, $bop, $notes]);
                $newId = $conn->lastInsertId();
                activity_log_write($conn, 'settings', 'master_carter', $newId, 'create', 'Master carter ditambahkan: ' . $name, $origin . ' -> ' . $destination, $actor);
                echo json_encode(['success' => true, 'message' => 'Rute Carter berhasil ditambahkan']);
            }
        } else {
            if ($route_id > 0) {
                $stmtOld = $conn->prepare("SELECT name FROM routes WHERE id=? LIMIT 1");
                $stmtOld->execute([$route_id]);
                $oldName = ($r = $stmtOld->fetch()) ? $r['name'] : '';
                
                $stmt = $conn->prepare("UPDATE routes SET name=?, origin=?, destination=? WHERE id=?");
                $stmt->execute([$name, $origin, $destination, $route_id]);
                activity_log_write($conn, 'settings', 'route', $route_id, 'update', 'Rute diperbarui: ' . $name, 'Sebelumnya: ' . ($oldName ?: '-'), $actor);
                
                if ($oldName && $oldName !== $name) {
                    $conn->prepare("UPDATE bookings SET rute=? WHERE rute=?")->execute([$name, $oldName]);
                    $conn->prepare("UPDATE schedules SET rute=? WHERE rute=?")->execute([$name, $oldName]);
                }
                echo json_encode(['success' => true, 'message' => 'Rute Reguler berhasil diperbarui']);
            } else {
                $stmt = $conn->prepare("INSERT INTO routes(name, origin, destination) VALUES(?,?,?)");
                $stmt->execute([$name, $origin, $destination]);
                $newId = $conn->lastInsertId();
                activity_log_write($conn, 'settings', 'route', $newId, 'create', 'Rute ditambahkan: ' . $name, $origin . ' -> ' . $destination, $actor);
                echo json_encode(['success' => true, 'message' => 'Rute Reguler berhasil ditambahkan']);
            }
        }
        exit;
    }

    if ($subAction === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($type === 'carter') {
            $stmtInfo = $conn->prepare("SELECT name FROM master_carter WHERE id=? LIMIT 1");
            $stmtInfo->execute([$id]);
            $name = (string) ($stmtInfo->fetchColumn() ?: '');
            $stmt = $conn->prepare("DELETE FROM master_carter WHERE id=?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                activity_log_write($conn, 'settings', 'master_carter', $id, 'delete', 'Rute Carter dihapus: ' . ($name ?: ('ID ' . $id)), '', $actor);
                echo json_encode(['success' => true, 'message' => 'Rute Carter berhasil dihapus']);
            }
        } else {
            $stmtInfo = $conn->prepare("SELECT name FROM routes WHERE id=? LIMIT 1");
            $stmtInfo->execute([$id]);
            $name = (string) ($stmtInfo->fetchColumn() ?: '');
            $stmt = $conn->prepare("DELETE FROM routes WHERE id=?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                activity_log_write($conn, 'settings', 'route', $id, 'delete', 'Rute Reguler dihapus: ' . ($name ?: ('ID ' . $id)), '', $actor);
                echo json_encode(['success' => true, 'message' => 'Rute Reguler berhasil dihapus']);
            }
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
