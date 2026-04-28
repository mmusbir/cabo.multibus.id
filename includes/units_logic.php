<?php
// includes/units_logic.php

require_once __DIR__ . '/../config/activity_log.php';
require_once __DIR__ . '/../helpers/cache.php';

// --- Ambil data unit kendaraan dari database ---
$units = [];
if (isset($conn)) {
  $res = $conn->query("SELECT * FROM units ORDER BY id DESC");
  if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
      $units[] = $row;
    }
  }
}

// --- Proses tambah unit kendaraan ---
if (isset($_POST['save_unit'])) {
  $actor = activity_log_current_actor();
  $nopol = trim($_POST['nopol'] ?? '');
  $merek = trim($_POST['merek'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $category = trim($_POST['category'] ?? 'Big Bus');
  $tahun = intval($_POST['tahun'] ?? 0);
  $kapasitas = intval($_POST['kapasitas'] ?? 0);
  $status = trim($_POST['status'] ?? 'Aktif');
  if ($nopol && $merek && $type && $category && $tahun && $kapasitas && $status) {
    $stmt = $conn->prepare("INSERT INTO units (nopol, merek, type, category, tahun, kapasitas, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nopol, $merek, $type, $category, $tahun, $kapasitas, $status]);
    if ($stmt->rowCount() > 0) {
      activity_log_write($conn, 'settings', 'unit', $conn->lastInsertId(), 'create', 'Unit kendaraan ditambahkan: ' . $nopol, $merek . ' ' . $type, $actor);
      // Invalidate units cache and schedules cache (layout/capacity may affect schedules)
      if (function_exists('cache_delete')) {
        cache_delete('getUnits');
        cache_delete_prefix('getSchedules|');
      }
    }
    header('Location: admin.php#units');
    exit;
  }
}

// --- Proses update unit kendaraan ---
if (isset($_POST['update_unit'])) {
  $actor = activity_log_current_actor();
  $unit_id = intval($_POST['unit_id'] ?? 0);
  $nopol = trim($_POST['nopol'] ?? '');
  $merek = trim($_POST['merek'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $category = trim($_POST['category'] ?? 'Big Bus');
  $tahun = intval($_POST['tahun'] ?? 0);
  $kapasitas = intval($_POST['kapasitas'] ?? 0);
  $status = trim($_POST['status'] ?? 'Aktif');
  if ($unit_id && $nopol && $merek && $type && $category && $tahun && $kapasitas && $status) {
    $oldStmt = $conn->prepare("SELECT nopol FROM units WHERE id=? LIMIT 1");
    $oldStmt->execute([$unit_id]);
    $oldNopol = (string) ($oldStmt->fetchColumn() ?: '');
    $stmt = $conn->prepare("UPDATE units SET nopol=?, merek=?, type=?, category=?, tahun=?, kapasitas=?, status=? WHERE id=?");
    $stmt->execute([$nopol, $merek, $type, $category, $tahun, $kapasitas, $status, $unit_id]);
    activity_log_write($conn, 'settings', 'unit', $unit_id, 'update', 'Unit kendaraan diperbarui: ' . $nopol, 'Sebelumnya: ' . ($oldNopol ?: '-'), $actor);
    if (function_exists('cache_delete')) {
      cache_delete('getUnits');
      cache_delete_prefix('getSchedules|');
    }
    header('Location: admin.php#units');
    exit;
  }
}

// --- Proses hapus unit kendaraan ---
if (isset($_POST['delete_unit'])) {
  $actor = activity_log_current_actor();
  $unit_id = intval($_POST['unit_id'] ?? 0);
  if ($unit_id) {
    $infoStmt = $conn->prepare("SELECT nopol FROM units WHERE id=? LIMIT 1");
    $infoStmt->execute([$unit_id]);
    $nopol = (string) ($infoStmt->fetchColumn() ?: '');
    $stmt = $conn->prepare("DELETE FROM units WHERE id=?");
    $stmt->execute([$unit_id]);
    if ($stmt->rowCount() > 0) {
      activity_log_write($conn, 'settings', 'unit', $unit_id, 'delete', 'Unit kendaraan dihapus: ' . ($nopol ?: ('ID ' . $unit_id)), '', $actor);
      if (function_exists('cache_delete')) {
        cache_delete('getUnits');
        cache_delete_prefix('getSchedules|');
      }
    }
    header('Location: admin.php#units');
    exit;
  }
}

// --- Ambil data unit untuk edit ---
$edit_unit = [];
if (isset($_GET['edit_unit'])) {
  $edit_id = intval($_GET['edit_unit']);
  $stmt = $conn->prepare("SELECT * FROM units WHERE id=? LIMIT 1");
  $stmt->execute([$edit_id]);
  if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $edit_unit = $row;
  }
}

// --- Ambil layout kursi via AJAX GET (Unit / Schedule) ---
if (isset($_GET['get_layout'])) {
  $id = intval($_GET['get_layout']);
  $type = $_GET['type'] ?? 'unit';

  $layout_json = null;

  if ($type === 'schedule') {
    // For schedule, ALWAYS get layout from the linked unit (synced approach)
    $stmt = $conn->prepare("SELECT unit_id FROM schedules WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $unit_id = $stmt->fetchColumn();

    // Get layout from the linked unit
    if ($unit_id) {
      $stmt2 = $conn->prepare("SELECT layout FROM units WHERE id=? LIMIT 1");
      $stmt2->execute([$unit_id]);
      $layout_json = $stmt2->fetchColumn();
    }
  } else {
    $stmt = $conn->prepare("SELECT layout FROM units WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $layout_json = $stmt->fetchColumn();
  }

  $layout = json_decode((string)$layout_json, true) ?: [];
  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'layout' => $layout]);
  exit;
}

// --- Simpan layout kursi via AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
  $input = json_decode(file_get_contents('php://input'), true);
  if (isset($input['save_layout'], $input['unit_id'], $input['layout'])) {
    $actor = activity_log_current_actor();
    $unit_id = intval($input['unit_id']);
    $layout_json = json_encode($input['layout']);

    // Also update kapasitas if provided
    if (isset($input['kapasitas'])) {
      $kapasitas = intval($input['kapasitas']);
      $stmt = $conn->prepare("UPDATE units SET layout=?, kapasitas=? WHERE id=?");
      $success = $stmt->execute([$layout_json, $kapasitas, $unit_id]);
    } else {
      $stmt = $conn->prepare("UPDATE units SET layout=? WHERE id=?");
      $success = $stmt->execute([$layout_json, $unit_id]);
    }

    header('Content-Type: application/json');
    if ($success) {
      activity_log_write($conn, 'settings', 'unit_layout', $unit_id, 'update', 'Layout kursi unit diperbarui', 'Unit ID ' . $unit_id, $actor);
    }
    echo json_encode(['success' => $success]);
    exit;
  }
}
?>
