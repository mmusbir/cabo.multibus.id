<?php
/**
 * admin/ajax/luggage_service_crud.php - CRUD for luggage services and price mapping
 */

global $conn;
require_once __DIR__ . '/../../config/activity_log.php';
require_once __DIR__ . '/../../helpers/cache.php';

$subAction = $_POST['subAction'] ?? '';
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$actor = activity_log_current_actor();

$rute_id = intval($_POST['rute_id'] ?? 0);
$layanan_id = intval($_POST['layanan_id'] ?? 0);
$harga = floatval($_POST['harga'] ?? 0);

header('Content-Type: application/json');

if ($subAction === 'save') {
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Nama layanan tidak boleh kosong']);
        exit;
    }

    if ($id > 0) {
        $oldStmt = $conn->prepare("SELECT name FROM luggage_services WHERE id=? LIMIT 1");
        $oldStmt->execute([$id]);
        $oldName = (string) ($oldStmt->fetchColumn() ?: '');
        $stmt = $conn->prepare("UPDATE luggage_services SET name=?, price=? WHERE id=?");
        $params = [$name, $price, $id];
    } else {
        $stmt = $conn->prepare("INSERT INTO luggage_services (name, price) VALUES (?, ?)");
        $params = [$name, $price];
    }

    try {
        $stmt->execute($params);
        $currentId = ($id > 0) ? $id : $conn->lastInsertId();
        
        if ($id > 0) {
            activity_log_write($conn, 'settings', 'luggage_service', $id, 'update', 'Layanan bagasi diperbarui: ' . $name, 'Sebelumnya: ' . ($oldName ?? '-'), $actor);
        } else {
            activity_log_write($conn, 'settings', 'luggage_service', $currentId, 'create', 'Layanan bagasi ditambahkan: ' . $name, 'Harga: ' . $price, $actor);
        }

        // Jika rute_id dipilih, simpan juga ke pemetaan harga
        if ($rute_id > 0) {
            $stmtMap = $conn->prepare("INSERT INTO harga_bagasi (rute_id, layanan_id, harga) VALUES (?, ?, ?) ON CONFLICT (rute_id, layanan_id) DO UPDATE SET harga = EXCLUDED.harga");
            $stmtMap->execute([$rute_id, $currentId, $price]);
        }

        // Invalidate luggage services cache
        if (function_exists('cache_delete')) cache_delete('getLuggageServices');
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

    $infoStmt = $conn->prepare("SELECT name FROM luggage_services WHERE id=? LIMIT 1");
    $infoStmt->execute([$id]);
    $serviceName = (string) ($infoStmt->fetchColumn() ?: '');
    $stmt = $conn->prepare("DELETE FROM luggage_services WHERE id=?");
    try {
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            activity_log_write($conn, 'settings', 'luggage_service', $id, 'delete', 'Layanan bagasi dihapus: ' . ($serviceName ?: ('ID ' . $id)), '', $actor);
            if (function_exists('cache_delete')) cache_delete('getLuggageServices');
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($subAction === 'saveMapping') {
    if ($rute_id <= 0 || $layanan_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Rute dan Layanan harus dipilih']);
        exit;
    }
    try {
        $stmt = $conn->prepare("INSERT INTO harga_bagasi (rute_id, layanan_id, harga) VALUES (?, ?, ?) ON CONFLICT (rute_id, layanan_id) DO UPDATE SET harga = EXCLUDED.harga");
        $stmt->execute([$rute_id, $layanan_id, $harga]);
        activity_log_write($conn, 'settings', 'harga_bagasi', $rute_id . '_' . $layanan_id, 'upsert', 'Mapping harga diperbarui', 'Rute: ' . $rute_id . ', Layanan: ' . $layanan_id . ', Harga: ' . $harga, $actor);
        if (function_exists('cache_delete')) cache_delete('getLuggageServices');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($subAction === 'deleteMapping') {
    if ($rute_id <= 0 || $layanan_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM harga_bagasi WHERE rute_id = ? AND layanan_id = ?");
        $stmt->execute([$rute_id, $layanan_id]);
        activity_log_write($conn, 'settings', 'harga_bagasi', $rute_id . '_' . $layanan_id, 'delete', 'Mapping harga dihapus', '', $actor);
        if (function_exists('cache_delete')) cache_delete('getLuggageServices');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (($_GET['action'] ?? '') === 'luggagePriceMappingPage') {
    try {
        $stmt = $conn->query("SELECT h.rute_id, h.layanan_id, h.harga, r.name as rute_name, l.name as layanan_name 
                             FROM harga_bagasi h 
                             JOIN routes r ON h.rute_id = r.id 
                             JOIN luggage_services l ON h.layanan_id = l.id 
                             ORDER BY r.name ASC, l.name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_start();
        if (empty($rows)) {
            echo '<tr><td colspan="4" class="text-center py-5 text-muted">Belum ada pemetaan harga khusus rute.</td></tr>';
        } else {
            foreach ($rows as $h) {
                echo '<tr>';
                echo '  <td><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-route text-muted"></i> ' . htmlspecialchars($h['rute_name'] ?? '') . '</div></td>';
                echo '  <td><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">' . htmlspecialchars($h['layanan_name'] ?? '') . '</span></td>';
                echo '  <td class="text-end fw-bold text-primary">Rp ' . number_format($h['harga'], 0, ',', '.') . '</td>';
                echo '  <td class="text-center">';
                echo '    <button class="kinetic-icon-btn sm danger delete-mapping" data-rute="' . $h['rute_id'] . '" data-layanan="' . $h['layanan_id'] . '" title="Hapus Pemetaan"><i class="fa-solid fa-trash-can"></i></button>';
                echo '  </td>';
                echo '</tr>';
            }
        }
        $html = ob_get_clean();
        echo json_encode(['success' => true, 'rows' => $html]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'invalid_sub_action']);
exit;
