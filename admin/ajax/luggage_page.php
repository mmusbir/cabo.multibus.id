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

// Base filter: today OR pending (Need Input) OR Belum Lunas
$baseWhere = "(l.status = 'pending' OR l.payment_status = 'Belum Lunas' OR DATE(l.created_at) = CURDATE())";

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM luggages l WHERE $baseWhere AND (l.sender_name LIKE ? OR l.receiver_name LIKE ? OR l.sender_phone LIKE ?)");
    $stmtc->execute([$like, $like, $like]);
    $total = $stmtc->fetch()['cnt'];

    $stmt = $conn->prepare("SELECT l.*, s.name as service_name FROM luggages l LEFT JOIN luggage_services s ON l.service_id = s.id WHERE $baseWhere AND (l.sender_name LIKE ? OR l.receiver_name LIKE ? OR l.sender_phone LIKE ?) ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $params = [$like, $like, $like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM luggages l WHERE $baseWhere");
    $total = ($resCount) ? $resCount->fetch()['cnt'] : 0;

    $stmt = $conn->prepare("SELECT l.*, s.name as service_name FROM luggages l LEFT JOIN luggage_services s ON l.service_id = s.id WHERE $baseWhere ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

$stmt->execute($params ?? []);

ob_start();
while ($l = $stmt->fetch()) {
    $status = $l['status']; // pending, arrived, canceled
    $payment = $l['payment_status']; // Belum Lunas, Lunas

    // Status Display Logic
    $statusLabel = 'Need Input';
    $statusClass = 'warning';
    
    if ($status === 'active') {
        $statusLabel = 'Diproses'; // Changed from 'Arrived' because we updated enum to 'active'
        $statusClass = 'active';
    } elseif ($status === 'canceled') {
        $statusLabel = 'Dibatalkan';
        $statusClass = 'canceled';
    }

    $payColor = ($payment === 'Lunas') ? '#10b981' : '#f59e0b';

    echo '<div class="admin-card-compact">';
    echo '  <div class="acc-header">';
    echo '    <div class="acc-title">' . htmlspecialchars($l['sender_name']) . ' → ' . htmlspecialchars($l['receiver_name']) . '</div>';
    echo '    <div style="display:flex;align-items:center;gap:6px">';
    echo '      <div class="acc-id">#LUG' . str_pad($l['id'], 5, '0', STR_PAD_LEFT) . '</div>';
    echo '      <span style="font-size:10px;padding:2px 6px;border-radius:4px;color:#fff;background:' . $payColor . '">' . htmlspecialchars($payment) . '</span>';
    echo '    </div>';
    echo '  </div>';

    echo '  <div class="acc-body">';
    echo '    <div class="acc-row"><div class="acc-label">📌 Layanan</div><div class="acc-val"><strong>' . htmlspecialchars($l['service_name']) . '</strong> (Qty: ' . $l['quantity'] . ')</div></div>';
    echo '    <div class="acc-row"><div class="acc-label">💰 Total</div><div class="acc-val" style="font-weight:700;color:#10b981">Rp ' . number_format($l['price'], 0, ',', '.') . '</div></div>';
    echo '    <div class="acc-row"><div class="acc-label">📞 Pengirim</div><div class="acc-val">' . htmlspecialchars($l['sender_phone']) . '</div></div>';
    echo '    <div class="acc-row"><div class="acc-label">📞 Penerima</div><div class="acc-val">' . htmlspecialchars($l['receiver_phone']) . '</div></div>';
    echo '    <div class="acc-row"><div class="acc-label">📝 Catatan</div><div class="acc-val">' . htmlspecialchars($l['notes'] ?: '-') . '</div></div>';
    echo '    <div class="acc-row"><div class="acc-label">📋 Status</div><div class="acc-val"><span class="bc-status ' . $statusClass . '">' . $statusLabel . '</span></div></div>';
    echo '  </div>';

    echo '  <div class="acc-actions">';
    if ($status === 'pending') {
        echo '    <a href="#" class="acc-btn luggage-action" data-action="inputLuggage" data-id="' . $l['id'] . '" title="Tandai Sudah Sampai/Input">📬 Input</a>';
    }
    if ($payment !== 'Lunas') {
        echo '    <a href="#" class="acc-btn luggage-action" data-action="markLuggagePaid" data-id="' . $l['id'] . '" title="Bayar">💰 Bayar</a>';
    }
    echo '    <a href="#" class="acc-btn danger luggage-action" data-action="cancelLuggage" data-id="' . $l['id'] . '" title="Batalkan">❌ Batal</a>';
    echo '  </div>';
    echo '</div>';
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'luggage');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
