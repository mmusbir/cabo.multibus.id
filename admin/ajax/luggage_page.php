<?php
/**
 * admin/ajax/luggage_page.php - Handle luggage shipment listing
 * Only shows: today's data + status='pending' (Need Input) + payment='Belum Lunas'
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
    echo '<div class="small admin-empty-state" style="grid-column:1/-1;text-align:center;padding:20px;">Data bagasi tidak ditemukan</div>';
} else {
    foreach ($rows as $l) {
        $status = $l['status'];
        $payment = $l['payment_status'];

        $statusLabel = 'Need Input';
        $statusClass = 'warning';
        if ($status === 'active') {
            $statusLabel = 'Diproses';
            $statusClass = 'active';
        } elseif ($status === 'canceled') {
            $statusLabel = 'Dibatalkan';
            $statusClass = 'canceled';
        }

        $paymentClass = ($payment === 'Lunas') ? 'paid' : 'warning';
        $serviceName = $l['service_name'] ?: '-';
        $noteText = trim($l['notes'] ?? '');

        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div>';
        echo '      <div class="acc-title">' . htmlspecialchars($l['sender_name']) . ' -> ' . htmlspecialchars($l['receiver_name']) . '</div>';
        echo '      <div class="admin-card-subtitle">Layanan bagasi</div>';
        echo '    </div>';
        echo '    <div class="admin-card-tags">';
        echo '      <div class="acc-id">#LUG' . str_pad($l['id'], 5, '0', STR_PAD_LEFT) . '</div>';
        echo '      <span class="admin-status-pill ' . $paymentClass . '">' . htmlspecialchars($payment) . '</span>';
        echo '    </div>';
        echo '  </div>';

        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Layanan</div><div class="acc-val admin-value-stack"><div><strong>' . htmlspecialchars($serviceName) . '</strong></div><div class="admin-card-subtitle">Qty: ' . intval($l['quantity']) . '</div></div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Total</div><div class="acc-val"><span class="admin-price-main">Rp ' . number_format($l['price'], 0, ',', '.') . '</span></div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Pengirim</div><div class="acc-val">' . htmlspecialchars($l['sender_phone']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Penerima</div><div class="acc-val">' . htmlspecialchars($l['receiver_phone']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Catatan</div><div class="acc-val">' . htmlspecialchars($noteText !== '' ? $noteText : '-') . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Status</div><div class="acc-val"><span class="bc-status ' . $statusClass . '">' . $statusLabel . '</span></div></div>';
        echo '  </div>';

        echo '  <div class="acc-actions">';
        if ($status === 'pending') {
            echo '    <a href="#" class="acc-btn luggage-action" data-action="inputLuggage" data-id="' . intval($l['id']) . '" title="Input bagasi">Input</a>';
        }
        if ($payment !== 'Lunas') {
            echo '    <a href="#" class="acc-btn success luggage-action" data-action="markLuggagePaid" data-id="' . intval($l['id']) . '" title="Tandai lunas">Bayar</a>';
        }
        echo '    <a href="#" class="acc-btn danger luggage-action" data-action="cancelLuggage" data-id="' . intval($l['id']) . '" title="Batalkan bagasi">Batalkan</a>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'luggage');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;