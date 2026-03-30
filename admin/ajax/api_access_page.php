<?php
/**
 * admin/ajax/api_access_page.php - Handle external API key settings data
 */

global $conn;

external_api_ensure_table($conn);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = trim((string) ($_GET['search'] ?? ''));

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmtCount = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM external_api_keys
        WHERE name ILIKE ? OR COALESCE(created_by_username, '') ILIKE ? OR COALESCE(api_key_prefix, '') ILIKE ?
    ");
    $stmtCount->execute([$like, $like, $like]);
    $total = (int) (($stmtCount->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

    $stmt = $conn->prepare("
        SELECT id, name, api_key_prefix, status, created_by_username, last_used_at, created_at
        FROM external_api_keys
        WHERE name ILIKE ? OR COALESCE(created_by_username, '') ILIKE ? OR COALESCE(api_key_prefix, '') ILIKE ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$like, $like, $like, $per_page, $offset]);
} else {
    $total = (int) ($conn->query("SELECT COUNT(*) FROM external_api_keys")->fetchColumn() ?? 0);
    $stmt = $conn->prepare("
        SELECT id, name, api_key_prefix, status, created_by_username, last_used_at, created_at
        FROM external_api_keys
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<tr><td colspan="8" class="customers-table-empty">Belum ada API key integrasi</td></tr>';
} else {
    foreach ($rows as $row) {
        $status = strtolower(trim((string) ($row['status'] ?? 'inactive')));
        $badgeClass = $status === 'active' ? 'actor-admin' : 'actor-system';
        $statusLabel = $status === 'active' ? 'Aktif' : 'Nonaktif';
        echo '<tr>';
        echo '  <td><span class="customers-table-id">#' . intval($row['id']) . '</span></td>';
        echo '  <td class="customers-table-name">' . htmlspecialchars((string) ($row['name'] ?? '-')) . '</td>';
        echo '  <td><code>' . htmlspecialchars((string) ($row['api_key_prefix'] ?? '-')) . '...</code></td>';
        echo '  <td><span class="admin-log-actor-badge ' . $badgeClass . '">' . htmlspecialchars($statusLabel) . '</span></td>';
        echo '  <td>' . htmlspecialchars((string) ($row['created_by_username'] ?? 'Admin Panel')) . '</td>';
        echo '  <td>' . htmlspecialchars((string) ($row['last_used_at'] ?? '-')) . '</td>';
        echo '  <td>' . htmlspecialchars((string) ($row['created_at'] ?? '-')) . '</td>';
        echo '  <td><div class="customers-table-actions">';
        echo '      <a class="acc-btn" href="admin.php?edit_api_key=' . intval($row['id']) . '#api_access">Edit</a>';
        echo '      <a class="acc-btn danger" href="admin.php?delete_api_key=' . intval($row['id']) . '#api_access" onclick="event.preventDefault(); customConfirm(\'Hapus API key ini?\', () => { window.location.href = this.href; }, \'Hapus API Key\', \'danger\')">Hapus</a>';
        echo '  </div></td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'api_access');

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'rows' => $rows_html,
    'pagination' => $pag_html,
    'total' => $total,
]);
exit;
