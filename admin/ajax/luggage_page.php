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

$baseWhere = "(l.status = 'pending' OR l.payment_status = 'Belum Lunas' OR DATE(l.created_at) = CURDATE())";

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
        } elseif ($payment === 'Lunas') {
            $statusLabel = 'LOADING';
            $stateClass = 'loading';
            $noteText = 'Pembayaran sudah masuk';
            $noteClass = '';
        }

        $serviceName = $l['service_name'] ?: '-';
        $noteExtra = trim($l['notes'] ?? '');
        $tripDate = !empty($l['created_at']) ? strtoupper(date('d M', strtotime($l['created_at']))) : '-';
        $tripHour = !empty($l['created_at']) ? date('H:i', strtotime($l['created_at'])) : '--:--';

        echo '<div class="admin-card-compact kinetic-trip-card">';
        echo '  <div class="kinetic-trip-card-inner">';
        echo '    <div class="kinetic-trip-time">';
        echo '      <span class="kinetic-trip-date">' . htmlspecialchars($tripDate) . '</span>';
        echo '      <span class="kinetic-trip-hour">' . htmlspecialchars($tripHour) . '</span>';
        echo '      <span class="kinetic-trip-zone">LOG</span>';
        echo '    </div>';
        echo '    <div class="kinetic-trip-main">';
        echo '      <div>';
        echo '        <div class="kinetic-trip-meta">';
        echo '          <span class="kinetic-trip-state ' . $stateClass . '"><span class="status-dot"></span>' . htmlspecialchars($statusLabel) . '</span>';
        echo '          <span class="kinetic-trip-id">#LUG' . str_pad($l['id'], 5, '0', STR_PAD_LEFT) . '</span>';
        echo '        </div>';
        echo '        <h4 class="kinetic-trip-title">' . htmlspecialchars($l['sender_name']) . ' -> ' . htmlspecialchars($l['receiver_name']) . '</h4>';
        echo '        <div class="kinetic-trip-subtitle">' . htmlspecialchars($serviceName) . ' / Qty ' . intval($l['quantity']) . '</div>';
        echo '        <div class="kinetic-trip-line"><i class="fa-solid fa-phone fa-icon"></i><strong>' . htmlspecialchars($l['sender_phone']) . '</strong></div>';
        echo '        <div class="kinetic-trip-line"><i class="fa-solid fa-phone-volume fa-icon"></i>' . htmlspecialchars($l['receiver_phone']) . '</div>';
        echo '        <div class="kinetic-trip-line"><i class="fa-regular fa-note-sticky fa-icon"></i>' . htmlspecialchars($noteExtra !== '' ? $noteExtra : 'Tanpa catatan') . '</div>';
        echo '      </div>';
        echo '      <div class="kinetic-trip-stat">';
        echo '        <div class="kinetic-trip-stat-label">Shipment Snapshot</div>';
        echo '        <div class="kinetic-trip-progress">';
        echo '          <div class="kinetic-trip-progress-bar"><span class="kinetic-trip-progress-fill" style="width:' . ($payment === 'Lunas' ? '100' : ($status === 'active' ? '76' : '42')) . '%"></span></div>';
        echo '          <div class="kinetic-trip-progress-value">Rp ' . number_format($l['price'], 0, ',', '.') . '<span> total</span></div>';
        echo '        </div>';
        echo '        <div class="kinetic-trip-note ' . $noteClass . '"><i class="fa-solid fa-suitcase-rolling fa-icon"></i>' . htmlspecialchars($noteText) . '</div>';
        echo '        <div class="kinetic-trip-note muted"><i class="fa-solid fa-wallet fa-icon"></i>Status pembayaran: ' . htmlspecialchars($payment) . '</div>';
        echo '      </div>';
        echo '      <div class="kinetic-trip-actions">';
        if ($status === 'pending') {
            echo '        <a href="#" class="kinetic-trip-action luggage-action" data-action="inputLuggage" data-id="' . intval($l['id']) . '" title="Input bagasi"><i class="fa-regular fa-pen-to-square fa-icon"></i>Input</a>';
        }
        if ($payment !== 'Lunas') {
            echo '        <a href="#" class="kinetic-trip-action success luggage-action" data-action="markLuggagePaid" data-id="' . intval($l['id']) . '" title="Tandai lunas"><i class="fa-solid fa-circle-check fa-icon"></i>Bayar</a>';
        }
        echo '        <a href="#" class="kinetic-trip-action danger luggage-action" data-action="cancelLuggage" data-id="' . intval($l['id']) . '" title="Batalkan bagasi"><i class="fa-solid fa-ban fa-icon"></i>Batalkan</a>';
        echo '      </div>';
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
