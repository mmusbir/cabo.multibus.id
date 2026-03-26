<?php
/**
 * admin/ajax/luggage_services_page.php - List luggage services
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

$resCount = $conn->query("SELECT COUNT(*) AS cnt FROM luggage_services");
$total = ($resCount && $countRow = $resCount->fetch(PDO::FETCH_ASSOC)) ? intval($countRow['cnt'] ?? 0) : 0;

$stmt = $conn->prepare("SELECT * FROM luggage_services ORDER BY name ASC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($services)) {
    echo '<tr><td colspan="5" class="customers-table-empty">Belum ada layanan bagasi.</td></tr>';
} else {
    foreach ($services as $s) {
        echo '<tr>';
        echo '  <td><span class="customers-table-id">#' . intval($s['id']) . '</span></td>';
        echo '  <td class="customers-table-name">' . htmlspecialchars($s['name']) . '</td>';
        echo '  <td class="customers-table-phone">Rp ' . number_format($s['price'], 0, ',', '.') . '</td>';
        echo '  <td><span class="admin-status-pill warning">Aktif</span></td>';
        echo '  <td>';
        echo '    <div class="customers-table-actions">';
        echo '      <a href="#" class="acc-btn luggage-service-action" data-action="edit" data-id="' . intval($s['id']) . '" data-name="' . htmlspecialchars($s['name']) . '" data-price="' . htmlspecialchars($s['price']) . '" title="Edit layanan">Edit</a>';
        echo '      <a href="#" class="acc-btn danger luggage-service-action" data-action="delete" data-id="' . intval($s['id']) . '" title="Hapus layanan">Hapus</a>';
        echo '    </div>';
        echo '  </td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'ls');

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
