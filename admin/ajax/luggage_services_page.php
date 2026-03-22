<?php
/**
 * admin/ajax/luggage_services_page.php - List luggage services
 */

global $conn;

$res = $conn->query("SELECT * FROM luggage_services ORDER BY name ASC");
$services = [];
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $services[] = $row;
}

ob_start();
if (empty($services)) {
    echo '<div class="small admin-empty-state admin-grid-message">Belum ada layanan bagasi.</div>';
} else {
    foreach ($services as $s) {
        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div>';
        echo '      <div class="acc-title">' . htmlspecialchars($s['name']) . '</div>';
        echo '      <div class="admin-card-subtitle">Master layanan bagasi</div>';
        echo '    </div>';
        echo '    <div class="admin-card-tags">';
        echo '      <div class="acc-id">#' . intval($s['id']) . '</div>';
        echo '      <span class="admin-status-pill warning">Aktif</span>';
        echo '    </div>';
        echo '  </div>';
        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Harga</div><div class="acc-val"><span class="admin-price-main">Rp ' . number_format($s['price'], 0, ',', '.') . '</span></div></div>';
        echo '  </div>';
        echo '  <div class="acc-actions">';
        echo '    <a href="#" class="acc-btn luggage-service-action" data-action="edit" data-id="' . intval($s['id']) . '" data-name="' . htmlspecialchars($s['name']) . '" data-price="' . htmlspecialchars($s['price']) . '" title="Edit layanan">Edit</a>';
        echo '    <a href="#" class="acc-btn danger luggage-service-action" data-action="delete" data-id="' . intval($s['id']) . '" title="Hapus layanan">Hapus</a>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'total' => count($services)]);
exit;
