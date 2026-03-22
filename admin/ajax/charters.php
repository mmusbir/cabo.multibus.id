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
        $price = 'Rp ' . number_format($r['price'], 0, ',', '.');
        $priceRaw = floatval($r['price'] ?? 0);
        $vehicle = trim(($r['nopol'] ?? '-') . ' ' . ($r['merek'] ?? ''));
        $start = date('d/m/Y', strtotime($r['start_date']));
        $end = date('d/m/Y', strtotime($r['end_date']));
        $depTimeFormatted = formatTimeWithLabel($r['departure_time']);

        $startDate = new DateTime($r['start_date']);
        $endDate = new DateTime($r['end_date']);
        $interval = $startDate->diff($endDate);
        $durationDays = $interval->days + 1;

        $bopStatus = $r['bop_status'] ?? 'pending';
        $bopLabel = ($bopStatus === 'done') ? 'BOP Selesai' : 'BOP Pending';
        $bopClass = ($bopStatus === 'done') ? 'paid' : 'danger';
        $layanan = $r['layanan'] ?? 'Regular';
        $durationLabel = (strtoupper($layanan) === 'DROP OFF') ? '' : ' (' . $durationDays . ' hari)';

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

        echo '<div class="admin-card-compact charter-card" ' . $dataAttrs . '>';
        echo '  <div class="acc-header">';
        echo '    <div>';
        echo '      <div class="acc-title">' . htmlspecialchars($r['name']) . '</div>';
        if (!empty($r['company_name'])) {
            echo '      <div class="admin-card-subtitle">' . htmlspecialchars($r['company_name']) . '</div>';
        }
        echo '    </div>';
        echo '    <div class="admin-card-tags">';
        echo '      <span class="admin-status-pill ' . $bopClass . '">' . $bopLabel . '</span>';
        echo '      <span class="admin-status-pill info">Carter</span>';
        echo '    </div>';
        echo '  </div>';

        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Telepon</div><div class="acc-val">' . htmlspecialchars($r['phone']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Jadwal</div><div class="acc-val">' . htmlspecialchars($start . ' - ' . $end . ', ' . $depTimeFormatted . $durationLabel) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Rute</div><div class="acc-val">' . htmlspecialchars(($r['pickup_point'] ?? '-') . ' - ' . ($r['drop_point'] ?? '-')) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Armada</div><div class="acc-val admin-value-stack"><div>' . htmlspecialchars($vehicle) . '</div><div class="admin-card-subtitle">Driver: ' . htmlspecialchars($r['driver_name'] ?? '-') . '</div></div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Tarif</div><div class="acc-val admin-value-stack"><div class="admin-price-main">' . $price . '</div><div class="admin-price-note">' . htmlspecialchars($layanan) . ' / BOP Rp ' . number_format($r['bop_price'] ?? 0, 0, ',', '.') . '</div></div></div>';
        echo '  </div>';

        echo '  <div class="acc-actions">';
        if ($bopStatus !== 'done') {
            echo '    <a href="#" class="acc-btn success bop-done-btn" data-id="' . intval($r['id']) . '" title="Tandai BOP selesai">BOP</a>';
        }
        echo '    <a href="#" class="acc-btn copy-charter-btn" data-id="' . intval($r['id']) . '" title="Salin charter">Copy</a>';
        echo '    <a href="#" class="acc-btn edit-charter-btn" data-id="' . intval($r['id']) . '" title="Edit charter">Edit</a>';
        echo '    <a href="#" class="acc-btn danger delete-charter-btn" data-id="' . intval($r['id']) . '" data-name="' . htmlspecialchars($r['name']) . '" title="Hapus charter">Hapus</a>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'charters');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
