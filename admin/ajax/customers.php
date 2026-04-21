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
        $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM customers WHERE name ILIKE ? OR phone LIKE ? OR pickup_point ILIKE ?");
        $stmtc->execute([$like, $like, $like]);
        $rc = $stmtc->fetch();
        $total = intval($rc['cnt']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
        exit;
    }
    $stmt = $conn->prepare("SELECT id,name,phone,address,pickup_point,created_at FROM customers WHERE name ILIKE ? OR phone LIKE ? OR pickup_point ILIKE ? ORDER BY name LIMIT ? OFFSET ?");
    $params = [$like, $like, $like, $per_page, $offset];
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
    echo '<tr><td colspan="6" class="customers-table-empty">Data tidak ditemukan</td></tr>';
} else {
    foreach ($rows as $c) {
        $fmtId = formatCustomerId($c['id'], $c['created_at']);
        $address = trim((string) ($c['address'] ?? ''));
        $pickup = trim((string) ($c['pickup_point'] ?? ''));
        $mapsShort = $address !== '' ? htmlspecialchars(strlen($address) > 48 ? substr($address, 0, 45) . '...' : $address) : '-';

        echo '<tr>';
        echo '  <td><span class="customers-table-id">#' . htmlspecialchars($fmtId) . '</span></td>';
        echo '  <td><span class="customers-table-name">' . htmlspecialchars($c['name']) . '</span></td>';
        echo '  <td><span class="customers-table-phone">' . htmlspecialchars($c['phone']) . '</span></td>';
        echo '  <td><span class="customers-table-pickup">' . ($pickup !== '' ? htmlspecialchars($pickup) : '-') . '</span></td>';
        echo '  <td>';
        if ($address !== '') {
            echo '<a class="customers-table-link" href="' . htmlspecialchars($address) . '" target="_blank" rel="noopener noreferrer" title="' . htmlspecialchars($address) . '">' . $mapsShort . '</a>';
        } else {
            echo '<span class="customers-table-muted">-</span>';
        }
        echo '  </td>';
        echo '  <td>';
        echo '    <div class="customers-table-actions">';
        echo '      <button type="button" class="acc-btn edit-customer-btn" data-id="' . intval($c['id']) . '">Edit</button>';
        echo '      <button type="button" class="acc-btn danger delete-customer-btn" data-id="' . intval($c['id']) . '">Hapus</button>';
        echo '    </div>';
        echo '  </td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'customers');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
