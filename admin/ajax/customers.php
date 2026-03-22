<?php
/**
 * admin/ajax/customers.php - Handle customers page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    try {
        $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM customers WHERE name LIKE ?");
        $stmtc->execute([$like]);
        $rc = $stmtc->fetch();
        $total = intval($rc['cnt']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
        exit;
    }
    $stmt = $conn->prepare("SELECT id,name,phone,address,pickup_point,created_at FROM customers WHERE name LIKE ? ORDER BY name LIMIT ? OFFSET ?");
    $params = [$like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM customers");
    $total = ($resCount && $rc = $resCount->fetch()) ? intval($rc['cnt']) : 0;
    $stmt = $conn->prepare("SELECT id,name,phone,address,pickup_point,created_at FROM customers ORDER BY name LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

try {
    $stmt->execute($params ?? []);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
    exit;
}

ob_start();
if (empty($rows)) {
    echo '<div class="small" style="grid-column:1/-1;text-align:center;padding:20px;opacity:0.6;">Data tidak ditemukan</div>';
} else {
    foreach ($rows as $c) {
        $fmtId = formatCustomerId($c['id'], $c['created_at']);
        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div class="acc-title">' . htmlspecialchars($c['name']) . '</div>';
        echo '    <div class="acc-id">#' . $fmtId . '</div>';
        echo '  </div>';
        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Telepon</div><div class="acc-val">' . htmlspecialchars($c['phone']) . '</div></div>';
        if (!empty($c['pickup_point'])) {
            echo '    <div class="acc-row"><div class="acc-label">Pickup</div><div class="acc-val">' . htmlspecialchars($c['pickup_point']) . '</div></div>';
        }
        if (!empty($c['address'])) {
            echo '    <div class="acc-row"><div class="acc-label">Maps</div><div class="acc-val" title="' . htmlspecialchars($c['address']) . '">' . htmlspecialchars($c['address']) . '</div></div>';
        }
        echo '  </div>';
        echo '  <div class="acc-actions">';
        echo '    <a class="acc-btn" href="admin.php?edit_customer=' . intval($c['id']) . '#customers">Edit</a>';
        echo '    <a class="acc-btn danger" href="admin.php?delete_customer=' . intval($c['id']) . '#customers" onclick="event.preventDefault(); customConfirm(\'Hapus penumpang?\', () => { window.location.href = this.href; }, \'Hapus Penumpang\', \'danger\')">Hapus</a>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'customers');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;