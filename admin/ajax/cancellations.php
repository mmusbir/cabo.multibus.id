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
    $rc = $stmtc->fetch();
    $total = intval($rc['cnt']);
    $stmt = $conn->prepare("SELECT c.id, c.booking_id, c.admin_user, c.reason, c.created_at, b.name, b.phone, b.created_at AS booking_created_at FROM cancellations c JOIN bookings b ON c.booking_id = b.id WHERE b.name LIKE ? OR b.phone LIKE ? ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
    $params = [$like, $like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM cancellations");
    $total = ($resCount && $rc = $resCount->fetch()) ? intval($rc['cnt']) : 0;
    $stmt = $conn->prepare("SELECT c.id, c.booking_id, c.admin_user, c.reason, c.created_at, b.name, b.phone, b.created_at AS booking_created_at FROM cancellations c JOIN bookings b ON c.booking_id = b.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

$stmt->execute($params ?? []);

ob_start();
$no = $offset + 1;
while ($c = $stmt->fetch()) {
    $fmtId = formatBookingId($c['booking_id'], $c['booking_created_at']);
    echo '<div class="admin-card-compact">';
    echo '  <div class="acc-header">';
    echo '    <div class="acc-title">' . htmlspecialchars($c['name']) . '</div>';
    echo '    <div class="acc-id">' . $fmtId . '</div>';
    echo '  </div>';
    echo '  <div class="acc-body">';
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">No. HP</div>';
    echo '      <div class="acc-val">' . htmlspecialchars($c['phone']) . '</div>';
    echo '    </div>';
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Admin</div>';
    echo '      <div class="acc-val">' . htmlspecialchars($c['admin_user']) . '</div>';
    echo '    </div>';
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Alasan</div>';
    echo '      <div class="acc-val">' . htmlspecialchars($c['reason']) . '</div>';
    echo '    </div>';
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Waktu</div>';
    echo '      <div class="acc-val">' . $c['created_at'] . '</div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'cancellations');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
