<?php
/**
 * admin/ajax/charters.php - Handle charters page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE start_date >= CURDATE()";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE ? OR company_name LIKE ? OR phone LIKE ? OR driver_name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$countSql = "SELECT COUNT(*) AS cnt FROM charters $where";
$stmt = $conn->prepare($countSql);
$stmt->execute($params);
$total = intval(($stmt->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

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
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Data carter tidak ditemukan</div>';
} else {
    foreach ($rows as $r) {
        $priceRaw = floatval($r['price'] ?? 0);
        $price = 'Rp ' . number_format($priceRaw, 0, ',', '.');
        $vehicle = trim(($r['nopol'] ?? '-') . ' ' . ($r['merek'] ?? ''));
        $tripDate = !empty($r['start_date']) ? strtoupper(date('d M', strtotime($r['start_date']))) : '-';
        $tripHour = !empty($r['departure_time']) ? substr($r['departure_time'], 0, 5) : '--:--';
        $depTimeFormatted = formatTimeWithLabel($r['departure_time']);

        $startDate = new DateTime($r['start_date']);
        $endDate = new DateTime($r['end_date']);
        $interval = $startDate->diff($endDate);
        $durationDays = $interval->days + 1;

        $bopStatus = $r['bop_status'] ?? 'pending';
        $bopLabel = ($bopStatus === 'done') ? 'READY' : 'LOADING';
        $stateClass = ($bopStatus === 'done') ? 'ready' : 'loading';
        $layanan = $r['layanan'] ?? 'Regular';
        $routeLine = trim(($r['pickup_point'] ?? '-') . ' - ' . ($r['drop_point'] ?? '-'));
        $driverName = trim($r['driver_name'] ?? '-') !== '' ? $r['driver_name'] : '-';

        $dataAttrs = 'data-id="' . intval($r['id']) . '" ';
        $dataAttrs .= 'data-name="' . htmlspecialchars($r['name'] ?? '') . '" ';
        $dataAttrs .= 'data-company="' . htmlspecialchars($r['company_name'] ?? '') . '" ';
        $dataAttrs .= 'data-phone="' . htmlspecialchars($r['phone'] ?? '') . '" ';
        $dataAttrs .= 'data-start="' . htmlspecialchars($r['start_date'] ?? '') . '" ';
        $dataAttrs .= 'data-end="' . htmlspecialchars($r['end_date'] ?? '') . '" ';
        $dataAttrs .= 'data-deptime="' . htmlspecialchars($r['departure_time'] ? substr($r['departure_time'], 0, 5) : '') . '" ';
        $dataAttrs .= 'data-deptime-formatted="' . htmlspecialchars($depTimeFormatted) . '" ';
        $dataAttrs .= 'data-pickup="' . htmlspecialchars($r['pickup_point'] ?? '') . '" ';
        $dataAttrs .= 'data-drop="' . htmlspecialchars($r['drop_point'] ?? '') . '" ';
        $dataAttrs .= 'data-unit="' . htmlspecialchars($r['unit_id'] ?? '') . '" ';
        $dataAttrs .= 'data-driver="' . htmlspecialchars($r['driver_name'] ?? '') . '" ';
        $dataAttrs .= 'data-price="' . $priceRaw . '" ';
        $dataAttrs .= 'data-layanan="' . htmlspecialchars($layanan) . '" ';
        $dataAttrs .= 'data-bop_price="' . floatval($r['bop_price'] ?? 0) . '" ';
        $dataAttrs .= 'data-vehicle="' . htmlspecialchars($vehicle) . '" ';
        $dataAttrs .= 'data-duration="' . $durationDays . '" ';
        $dataAttrs .= 'data-bop="' . htmlspecialchars($bopStatus) . '"';

        echo '<div class="admin-card-compact charter-card kinetic-trip-card" ' . $dataAttrs . '>';
        echo '  <div class="kinetic-trip-card-inner">';
        echo '    <div class="kinetic-trip-time">';
        echo '      <span class="kinetic-trip-date">' . htmlspecialchars($tripDate) . '</span>';
        echo '      <span class="kinetic-trip-hour">' . htmlspecialchars($tripHour) . '</span>';
        echo '      <span class="kinetic-trip-zone">WIB</span>';
        echo '    </div>';
        echo '    <div class="kinetic-trip-main">';
        echo '      <div>';
        echo '        <div class="kinetic-trip-meta">';
        echo '          <span class="kinetic-trip-state ' . $stateClass . '"><span class="status-dot"></span>' . htmlspecialchars($bopLabel) . '</span>';
        echo '          <span class="kinetic-trip-id">CHARTER</span>';
        echo '        </div>';
        echo '        <h4 class="kinetic-trip-title">' . htmlspecialchars($r['name']) . '</h4>';
        echo '        <div class="kinetic-trip-subtitle">' . htmlspecialchars(!empty($r['company_name']) ? $r['company_name'] : $layanan) . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">call</span><strong>' . htmlspecialchars($r['phone']) . '</strong></div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">route</span>' . htmlspecialchars($routeLine) . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">person</span>Driver: ' . htmlspecialchars($driverName) . '</div>';
        echo '      </div>';
        echo '      <div class="kinetic-trip-stat">';
        echo '        <div class="kinetic-trip-stat-label">Fleet Snapshot</div>';
        echo '        <div class="kinetic-trip-progress">';
        echo '          <div class="kinetic-trip-progress-bar"><span class="kinetic-trip-progress-fill" style="width:' . ($bopStatus === 'done' ? '100' : '72') . '%"></span></div>';
        echo '          <div class="kinetic-trip-progress-value">' . $price . '<span> total</span></div>';
        echo '        </div>';
        echo '        <div class="kinetic-trip-note ' . ($bopStatus === 'done' ? '' : 'warning') . '"><span class="material-symbols-outlined">directions_bus</span>' . htmlspecialchars($vehicle) . ' - ' . htmlspecialchars($layanan) . ' / BOP Rp ' . number_format($r['bop_price'] ?? 0, 0, ',', '.') . '</div>';
        echo '        <div class="kinetic-trip-note muted"><span class="material-symbols-outlined">schedule</span>' . htmlspecialchars($depTimeFormatted) . ' - ' . $durationDays . ' hari</div>';
        echo '      </div>';
        echo '      <div class="kinetic-trip-actions">';
        if ($bopStatus !== 'done') {
            echo '        <a href="#" class="kinetic-trip-action success bop-done-btn" data-id="' . intval($r['id']) . '"><span class="material-symbols-outlined">task_alt</span>BOP</a>';
        }
        echo '        <a href="#" class="kinetic-trip-action copy-charter-btn" data-id="' . intval($r['id']) . '"><span class="material-symbols-outlined">content_copy</span>Copy</a>';
        echo '        <a href="#" class="kinetic-trip-action edit-charter-btn" data-id="' . intval($r['id']) . '"><span class="material-symbols-outlined">edit_square</span>Edit</a>';
        echo '        <a href="#" class="kinetic-trip-action danger delete-charter-btn" data-id="' . intval($r['id']) . '" data-name="' . htmlspecialchars($r['name']) . '"><span class="material-symbols-outlined">delete</span>Hapus</a>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'charters');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
