<?php
/**
 * admin/ajax/luggage_page.php - Handle luggage shipment listing
 * Only shows: today's data + status='pending' + payment='Belum Lunas'
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$scope = isset($_GET['scope']) ? $_GET['scope'] : 'active';

if ($scope === 'history') {
    // History: status done/canceled/finished, ATAU active+Lunas (data lama sebelum fitur markDone)
    $baseWhere = "(l.status IN ('done', 'canceled', 'finished') OR (l.status = 'active' AND l.payment_status = 'Lunas'))";
} else {
    // Aktif: pending atau active yang belum lunas
    $baseWhere = "(l.status = 'pending' OR (l.status = 'active' AND l.payment_status != 'Lunas'))";
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM luggages l WHERE $baseWhere AND (l.sender_name LIKE ? OR l.receiver_name LIKE ? OR l.sender_phone LIKE ?)");
    $stmtc->execute([$like, $like, $like]);
    $total = intval(($stmtc->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

    $stmt = $conn->prepare("SELECT l.*, s.name as service_name FROM luggages l LEFT JOIN luggage_services s ON l.service_id = s.id WHERE $baseWhere AND (l.sender_name LIKE ? OR l.receiver_name LIKE ? OR l.sender_phone LIKE ?) ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $params = [$like, $like, $like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM luggages l WHERE $baseWhere");
    $row = $resCount ? $resCount->fetch(PDO::FETCH_ASSOC) : null;
    $total = intval($row['cnt'] ?? 0);

    $stmt = $conn->prepare("SELECT l.*, s.name as service_name FROM luggages l LEFT JOIN luggage_services s ON l.service_id = s.id WHERE $baseWhere ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

$stmt->execute($params ?? []);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Data bagasi tidak ditemukan</div>';
} else {
    foreach ($rows as $l) {
        $status = $l['status'];
        $payment = $l['payment_status'];

        $statusLabel = 'NEED INPUT';
        $stateClass = 'warning';
        $noteText = 'Menunggu proses bagasi';
        $noteClass = 'warning';

        if ($status === 'active') {
            $statusLabel = 'READY';
            $stateClass = 'ready';
            $noteText = 'Bagasi sedang diproses';
            $noteClass = '';
        } elseif ($status === 'canceled') {
            $statusLabel = 'CANCELED';
            $stateClass = 'danger';
            $noteText = 'Pengiriman dibatalkan';
            $noteClass = 'danger';
        } elseif ($status === 'done') {
            $statusLabel = 'SELESAI';
            $stateClass = 'success';
        }

        $serviceName = $l['service_name'] ?: '-';
        $noteExtra = trim($l['notes'] ?? '');
        $tripDate = !empty($l['created_at']) ? strtoupper(date('d M', strtotime($l['created_at']))) : '-';
        $tripHour = !empty($l['created_at']) ? date('H:i', strtotime($l['created_at'])) : '--:--';

        echo '<div class="luggage-card luggage-grid-item">';
        echo '  <div class="luggage-meta-row">';
        echo '    <span class="luggage-resi-badge">' . htmlspecialchars($l['kode_resi'] ?: ('#LUG' . str_pad($l['id'], 5, '0', STR_PAD_LEFT))) . '</span>';
        echo '    <span class="luggage-status-badge luggage-status-' . ($status === 'canceled' ? 'canceled' : ($payment === 'Lunas' ? 'paid' : 'pending')) . '">';
        echo '      <span class="status-dot"></span>' . htmlspecialchars($statusLabel);
        echo '    </span>';
        echo '  </div>';
        
        echo '  <div class="luggage-main-content">';
        echo '    <div class="d-flex align-items-center gap-2 mb-3">';
        echo '      <div class="kinetic-trip-time" style="margin:0; padding:4px 8px; border-radius:8px;">';
        echo '        <span class="kinetic-trip-date" style="font-size:11px;">' . htmlspecialchars($tripDate) . '</span>';
        echo '        <span class="kinetic-trip-hour" style="font-size:12px;">' . htmlspecialchars($tripHour) . '</span>';
        echo '      </div>';
        echo '    </div>';
        
        echo '    <div class="row g-2 mb-3">';
        echo '      <div class="col-6">';
        echo '        <div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:2px;">Pengirim</div>';
        echo '        <div style="font-size:13px; font-weight:700; color:var(--text-main);">' . htmlspecialchars($l['sender_name']) . '</div>';
        echo '        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><i class="fa-solid fa-phone" style="margin-right:4px;"></i>' . htmlspecialchars($l['sender_phone']) . '</div>';
        echo '        <div style="font-size:11px; color:var(--text-muted); margin-top:2px; line-height:1.2;">' . htmlspecialchars($l['sender_address'] ?: '-') . '</div>';
        echo '      </div>';
        echo '      <div class="col-6" style="border-left: 1px dashed var(--border-color); padding-left: 12px;">';
        echo '        <div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:2px;">Penerima</div>';
        echo '        <div style="font-size:13px; font-weight:700; color:var(--text-main);">' . htmlspecialchars($l['receiver_name']) . '</div>';
        echo '        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><i class="fa-solid fa-phone" style="margin-right:4px;"></i>' . htmlspecialchars($l['receiver_phone']) . '</div>';
        echo '        <div style="font-size:11px; color:var(--text-muted); margin-top:2px; line-height:1.2;">' . htmlspecialchars($l['receiver_address'] ?: '-') . '</div>';
        echo '      </div>';
        echo '    </div>';
        
        echo '    <div class="small mb-1"><i class="fa-solid fa-cube fa-icon" style="margin-right:8px;"></i>' . htmlspecialchars($serviceName) . ' (' . intval($l['quantity']) . ' Koli)</div>';
        echo '    <div class="small mb-2"><i class="fa-solid fa-location-dot fa-icon" style="margin-right:8px;"></i>' . htmlspecialchars($l['notes'] ?: 'Tidak ada catatan tambahan') . '</div>';
        echo '  </div>';

        echo '  <div class="luggage-footer-row d-flex justify-content-between align-items-end mt-auto pt-3 border-top" style="border-top-style:dashed !important;">';
        echo '    <div class="luggage-price-display">';
        echo '      <span style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:-4px;">Total Biaya</span>';
        echo '      Rp ' . number_format($l['price'], 0, ',', '.');
        echo '    </div>';
        echo '    <div class="d-flex gap-1 flex-wrap justify-content-end">';
        if ($scope !== 'history') {
            // Tab Aktif: tampilkan tombol aksi
            if ($status === 'active') {
                echo '      <button class="kinetic-trip-action success luggage-action" data-action="markLuggageDone" data-id="' . intval($l['id']) . '" style="padding: 4px 10px; font-size: 11px;"><i class="fa-solid fa-box-open fa-icon"></i>Selesai</button>';
            }
            if ($status === 'pending') {
                echo '      <button class="kinetic-trip-action luggage-action" data-action="inputLuggage" data-id="' . intval($l['id']) . '" style="padding: 4px 10px; font-size: 11px;"><i class="fa-solid fa-pen-to-square fa-icon"></i>Input</button>';
            }
            if ($payment !== 'Lunas') {
                echo '      <button class="kinetic-trip-action primary luggage-action" data-action="markLuggagePaid" data-id="' . intval($l['id']) . '" style="padding: 4px 10px; font-size: 11px;"><i class="fa-solid fa-check-double fa-icon"></i>Lunas</button>';
            }
            echo '      <button class="kinetic-trip-action luggage-action" data-action="trackBagasi" data-resi="' . htmlspecialchars($l['kode_resi']) . '" style="padding: 4px 10px; font-size: 11px;"><i class="fa-solid fa-truck-fast fa-icon"></i>Lacak</button>';
            echo '      <button class="kinetic-trip-action danger luggage-action" data-action="cancelLuggage" data-id="' . intval($l['id']) . '" style="padding: 4px 10px; font-size: 11px; background: #fee2e2; color: #b91c1c; border-color: #fca5a5;"><i class="fa-solid fa-xmark fa-icon"></i>Batal</button>';
        } else {
            // Tab History: hanya tombol Lacak
            echo '      <button class="kinetic-trip-action luggage-action" data-action="trackBagasi" data-resi="' . htmlspecialchars($l['kode_resi']) . '" style="padding: 4px 10px; font-size: 11px;"><i class="fa-solid fa-truck-fast fa-icon"></i>Lacak</button>';
        }
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'luggage');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
