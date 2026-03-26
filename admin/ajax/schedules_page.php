<?php
/**
 * admin/ajax/schedules_page.php - Handle schedules page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM schedules WHERE rute LIKE ?");
    $stmtc->execute([$like]);
    $rc = $stmtc->fetch();
    $total = intval($rc['cnt']);
    $stmt = $conn->prepare("SELECT s.id, s.rute, s.dow, s.jam, s.units, s.unit_id, u.nopol, u.kapasitas FROM schedules s LEFT JOIN units u ON s.unit_id = u.id WHERE s.rute LIKE ? ORDER BY s.rute, s.dow, s.jam LIMIT ? OFFSET ?");
    $params = [$like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM schedules");
    $total = ($resCount && $rc = $resCount->fetch()) ? intval($rc['cnt']) : 0;
    $stmt = $conn->prepare("SELECT s.id, s.rute, s.dow, s.jam, s.units, s.unit_id, u.nopol, u.kapasitas FROM schedules s LEFT JOIN units u ON s.unit_id = u.id ORDER BY s.rute, s.dow, s.jam LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

$stmt->execute($params ?? []);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
$days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

if (empty($rows)) {
    echo '<tr><td colspan="7" class="customers-table-empty">Jadwal tidak ditemukan</td></tr>';
} else {
    foreach ($rows as $s) {
        $nopol = $s['nopol'] ?? '-';
        $kapasitas = $s['kapasitas'] ?? '-';

        echo '<tr>';
        echo '  <td><span class="customers-table-id">#' . intval($s['id']) . '</span></td>';
        echo '  <td class="customers-table-name">' . htmlspecialchars($s['rute']) . '</td>';
        echo '  <td>' . htmlspecialchars($days[intval($s['dow'])] ?? '-') . '</td>';
        echo '  <td>' . htmlspecialchars(substr($s['jam'], 0, 5)) . '</td>';
        echo '  <td>' . intval($s['units']) . ' Unit</td>';
        echo '  <td>' . htmlspecialchars($nopol) . ' <span class="customers-table-muted">(' . htmlspecialchars($kapasitas) . ' kursi)</span></td>';
        echo '  <td>';
        echo '    <div class="customers-table-actions">';
        echo '      <button class="acc-btn view-schedule-layout" data-id="' . intval($s['id']) . '" data-rute="' . htmlspecialchars($s['rute']) . '">Layout</button>';
        echo '      <a class="acc-btn" href="admin.php?edit_schedule=' . intval($s['id']) . '#schedules">Edit</a>';
        echo '      <a class="acc-btn danger" href="admin.php?delete_schedule=' . intval($s['id']) . '#schedules" onclick="event.preventDefault(); customConfirm(\'Hapus jadwal ini?\', () => { window.location.href = this.href; }, \'Hapus Jadwal\', \'danger\')">Hapus</a>';
        echo '    </div>';
        echo '  </td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'schedules');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
