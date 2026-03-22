<?php
/**
 * admin/ajax/cancellations.php - Handle cancellations page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM cancellations c JOIN bookings b ON c.booking_id = b.id WHERE b.name LIKE ? OR b.phone LIKE ?");
    $stmtc->execute([$like, $like]);
    $rc = $stmtc->fetch(PDO::FETCH_ASSOC);
    $total = intval($rc['cnt'] ?? 0);
    $stmt = $conn->prepare("SELECT c.id, c.booking_id, c.admin_user, c.reason, c.created_at, b.name, b.phone, b.created_at AS booking_created_at FROM cancellations c JOIN bookings b ON c.booking_id = b.id WHERE b.name LIKE ? OR b.phone LIKE ? ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
    $params = [$like, $like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM cancellations");
    $total = ($resCount && $rc = $resCount->fetch(PDO::FETCH_ASSOC)) ? intval($rc['cnt']) : 0;
    $stmt = $conn->prepare("SELECT c.id, c.booking_id, c.admin_user, c.reason, c.created_at, b.name, b.phone, b.created_at AS booking_created_at FROM cancellations c JOIN bookings b ON c.booking_id = b.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

ob_start();
$stmt->execute($params ?? []);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo '<div class="small admin-empty-state" style="grid-column:1/-1;text-align:center;padding:20px;">Belum ada log pembatalan</div>';
} else {
    foreach ($rows as $c) {
        $fmtId = formatBookingId($c['booking_id'], $c['booking_created_at']);
        $reason = trim($c['reason'] ?? '');

        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div>';
        echo '      <div class="acc-title">' . htmlspecialchars($c['name']) . '</div>';
        echo '      <div class="admin-card-subtitle">Log pembatalan booking</div>';
        echo '    </div>';
        echo '    <div class="admin-card-tags">';
        echo '      <div class="acc-id">' . $fmtId . '</div>';
        echo '      <span class="admin-status-pill danger">Canceled</span>';
        echo '    </div>';
        echo '  </div>';
        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Telepon</div><div class="acc-val">' . htmlspecialchars($c['phone']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Admin</div><div class="acc-val">' . htmlspecialchars($c['admin_user']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Alasan</div><div class="acc-val">' . htmlspecialchars($reason !== '' ? $reason : '-') . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Waktu</div><div class="acc-val">' . htmlspecialchars($c['created_at']) . '</div></div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'cancellations');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
