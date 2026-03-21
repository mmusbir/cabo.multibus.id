<?php
/**
 * admin/ajax/charters.php - Handle charters page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base SQL (only today and future)
$where = "WHERE start_date >= CURDATE()";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (name LIKE ? OR company_name LIKE ? OR phone LIKE ? OR driver_name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

// Count
$countSql = "SELECT COUNT(*) AS cnt FROM charters $where";
$stmt = $conn->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['cnt'];

// Select
$sql = "SELECT c.*, u.nopol, u.merek 
        FROM charters c 
        LEFT JOIN units u ON c.unit_id = u.id 
        $where 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);

ob_start();
while ($r = $stmt->fetch()) {
    $price = 'Rp ' . number_format($r['price'], 0, ',', '.');
    $priceRaw = $r['price'] ?? 0;
    $vehicle = htmlspecialchars(($r['nopol'] ?? '-') . ' ' . ($r['merek'] ?? ''));
    $start = date('d/m/Y', strtotime($r['start_date']));
    $end = date('d/m/Y', strtotime($r['end_date']));
    $depTimeRaw = $r['departure_time'] ? substr($r['departure_time'], 0, 5) : '-';
    $depTimeFormatted = formatTimeWithLabel($r['departure_time']);

    // Calculate duration in days
    $startDate = new DateTime($r['start_date']);
    $endDate = new DateTime($r['end_date']);
    $interval = $startDate->diff($endDate);
    $durationDays = $interval->days + 1; // Include both start and end date

    // BOP Status
    $bopStatus = $r['bop_status'] ?? 'pending';
    $bopLabel = ($bopStatus === 'done') ? 'BOP: Done' : 'BOP: Belum Diajukan';
    $bopColor = ($bopStatus === 'done') ? '#10b981' : '#ef4444';

    // Data attributes for JS
    $dataAttrs = 'data-id="' . $r['id'] . '" ';
    $dataAttrs .= 'data-name="' . htmlspecialchars($r['name'] ?? '') . '" ';
    $dataAttrs .= 'data-company="' . htmlspecialchars($r['company_name'] ?? '') . '" ';
    $dataAttrs .= 'data-phone="' . htmlspecialchars($r['phone'] ?? '') . '" ';
    $dataAttrs .= 'data-start="' . ($r['start_date'] ?? '') . '" ';
    $dataAttrs .= 'data-end="' . ($r['end_date'] ?? '') . '" ';
    $dataAttrs .= 'data-deptime="' . ($r['departure_time'] ? substr($r['departure_time'], 0, 5) : '') . '" ';
    $dataAttrs .= 'data-deptime-formatted="' . $depTimeFormatted . '" ';
    $dataAttrs .= 'data-pickup="' . htmlspecialchars($r['pickup_point'] ?? '') . '" ';
    $dataAttrs .= 'data-drop="' . htmlspecialchars($r['drop_point'] ?? '') . '" ';
    $dataAttrs .= 'data-unit="' . ($r['unit_id'] ?? '') . '" ';
    $dataAttrs .= 'data-driver="' . htmlspecialchars($r['driver_name'] ?? '') . '" ';
    $dataAttrs .= 'data-price="' . $priceRaw . '" ';
    $dataAttrs .= 'data-layanan="' . htmlspecialchars($r['layanan'] ?? 'Regular') . '" ';
    $dataAttrs .= 'data-bop_price="' . ($r['bop_price'] ?? 0) . '" ';
    $dataAttrs .= 'data-vehicle="' . $vehicle . '" ';
    $dataAttrs .= 'data-duration="' . $durationDays . '" ';
    $dataAttrs .= 'data-bop="' . $bopStatus . '"';

    echo '<div class="admin-card-compact charter-card" ' . $dataAttrs . '>';
    echo '  <div class="acc-header" style="justify-content:space-between">';
    echo '    <div>';
    echo '      <div style="font-weight:700">' . htmlspecialchars($r['name']) . '</div>';
    if (!empty($r['company_name'])) {
        echo '      <div style="font-size:11px;color:#64748b;margin-top:1px">' . htmlspecialchars($r['company_name']) . '</div>';
    }
    echo '    </div>';
    echo '    <div style="display:flex;gap:4px;align-items:center">';
    echo '      <span style="font-size:9px;padding:2px 5px;border-radius:4px;background:' . $bopColor . ';color:#fff">' . $bopLabel . '</span>';
    echo '      <span class="c-status active" style="font-size:10px;padding:2px 6px">Carter</span>';
    echo '    </div>';
    echo '  </div>';
    echo '  <div class="acc-body" style="font-size:12px;line-height:1.5;padding:8px 10px">';
    echo '    <div>' . htmlspecialchars($r['phone']) . '</div>';
    $durationLabel = (strtoupper($r['layanan'] ?? '') === 'DROP OFF') ? '' : ' (' . $durationDays . ' hari)';
    echo '    <div>' . $start . ' - ' . $end . ', ' . $depTimeFormatted . $durationLabel . '</div>';
    echo '    <div>' . htmlspecialchars($r['pickup_point']) . ' - ' . htmlspecialchars($r['drop_point']) . '</div>';
    echo '    <div>' . $vehicle . ' | ' . htmlspecialchars($r['driver_name']) . '</div>';
    echo '    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2px;">';
    echo '      <div style="color:var(--neu-success);font-weight:600">' . $price . '</div>';
    echo '      <div style="font-size:10px; color:#64748b;">' . htmlspecialchars($r['layanan'] ?? 'Regular') . ' | BOP: Rp ' . number_format($r['bop_price'] ?? 0, 0, ',', '.') . '</div>';
    echo '    </div>';
    echo '  </div>';
    // Action buttons
    echo '  <div class="acc-actions" style="padding:6px 10px;gap:6px">';
    if ($bopStatus !== 'done') {
        echo '    <a href="#" class="acc-btn bop-done-btn" data-id="' . $r['id'] . '" title="BOP" style="font-size:11px;padding:4px 8px;background:#10b981;color:#fff">BOP</a>';
    }
    echo '    <a href="#" class="acc-btn copy-charter-btn" data-id="' . $r['id'] . '" title="Copy" style="font-size:11px;padding:4px 8px">Copy</a>';
    echo '    <a href="#" class="acc-btn edit-charter-btn" data-id="' . $r['id'] . '" title="Edit" style="font-size:11px;padding:4px 8px">Edit</a>';
    echo '    <a href="#" class="acc-btn danger delete-charter-btn" data-id="' . $r['id'] . '" data-name="' . htmlspecialchars($r['name']) . '" title="Hapus" style="font-size:11px;padding:4px 8px">Hapus</a>';
    echo '  </div>';
    echo '</div>';
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'charters');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
