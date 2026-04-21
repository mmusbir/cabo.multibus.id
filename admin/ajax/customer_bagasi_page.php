<?php
/**
 * admin/ajax/customer_bagasi_page.php - Handle customer bagasi page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    try {
        $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM customer_bagasi WHERE nama ILIKE ? OR no_hp LIKE ? OR alamat ILIKE ?");
        $stmtc->execute([$like, $like, $like]);
        $rc = $stmtc->fetch(PDO::FETCH_ASSOC);
        $total = intval($rc['cnt'] ?? 0);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
        exit;
    }
    $stmt = $conn->prepare("SELECT id,nama,no_hp,alamat,tipe,created_at FROM customer_bagasi WHERE nama ILIKE ? OR no_hp LIKE ? OR alamat ILIKE ? ORDER BY nama LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $like, PDO::PARAM_STR);
    $stmt->bindValue(3, $like, PDO::PARAM_STR);
    $stmt->bindValue(4, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
} else {
    try {
        $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM customer_bagasi");
        $rc = $resCount ? $resCount->fetch(PDO::FETCH_ASSOC) : null;
        $total = intval($rc['cnt'] ?? 0);
    } catch (PDOException $e) {
        $total = 0;
    }
    $stmt = $conn->prepare("SELECT id,nama,no_hp,alamat,tipe,created_at FROM customer_bagasi ORDER BY nama LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
}

try {
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
    exit;
}

ob_start();
if (empty($rows)) {
    echo '<tr><td colspan="6" class="customers-table-empty">Data tidak ditemukan</td></tr>';
} else {
    foreach ($rows as $c) {
        $fmtId = 'CBG' . date('y', strtotime($c['created_at'])) . str_pad($c['id'], 5, '0', STR_PAD_LEFT);
        $alamat = trim((string) ($c['alamat'] ?? ''));

        echo '<tr>';
        echo '  <td><span class="customers-table-id">#' . htmlspecialchars($fmtId) . '</span></td>';
        echo '  <td><span class="customers-table-name">' . htmlspecialchars($c['nama']) . '</span></td>';
        echo '  <td><span class="customers-table-phone">' . htmlspecialchars($c['no_hp']) . '</span></td>';
        echo '  <td><span class="customers-table-pickup">' . ($alamat !== '' ? htmlspecialchars($alamat) : '-') . '</span></td>';
        echo '  <td><span class="badge bg-secondary">' . htmlspecialchars(ucfirst($c['tipe'])) . '</span></td>';
        echo '  <td>';
        echo '    <div class="customers-table-actions">';
        echo '      <button type="button" class="acc-btn edit-customer-bagasi-btn" data-id="' . intval($c['id']) . '">Edit</button>';
        echo '      <button type="button" class="acc-btn danger delete-customer-bagasi-btn" data-id="' . intval($c['id']) . '">Hapus</button>';
        echo '    </div>';
        echo '  </td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'customer_bagasi');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
