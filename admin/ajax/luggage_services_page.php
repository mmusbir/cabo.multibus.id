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
    echo '<div class="admin-card-compact" style="text-align:center;padding:20px;color:#64748b;">Belum ada layanan bagasi.</div>';
} else {
    foreach ($services as $s) {
        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div class="acc-title">' . htmlspecialchars($s['name']) . '</div>';
        echo '    <div class="acc-id">#' . $s['id'] . '</div>';
        echo '  </div>';
        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">💰 Harga</div><div class="acc-val" style="font-weight:700;color:#10b981">Rp ' . number_format($s['price'], 0, ',', '.') . '</div></div>';
        echo '  </div>';
        echo '  <div class="acc-actions">';
        echo '    <a href="#" class="acc-btn luggage-service-action" data-action="edit" data-id="' . $s['id'] . '" data-name="' . htmlspecialchars($s['name']) . '" data-price="' . $s['price'] . '" title="Edit">✏️ Edit</a>';
        echo '    <a href="#" class="acc-btn danger luggage-service-action" data-action="delete" data-id="' . $s['id'] . '" title="Hapus">❌ Hapus</a>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();

ob_clean();
header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'total' => count($services)]);
exit;
