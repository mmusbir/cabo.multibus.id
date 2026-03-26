<?php
/**
 * admin/ajax/users.php - Handle users page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE username LIKE ? OR fullname LIKE ?");
    $stmtc->execute([$like, $like]);
    $row = $stmtc->fetch(PDO::FETCH_ASSOC);
    $total = intval($row['cnt'] ?? 0);
    $stmt = $conn->prepare("SELECT id, username, fullname, created_at FROM users WHERE username LIKE ? OR fullname LIKE ? ORDER BY id LIMIT ? OFFSET ?");
    $params = [$like, $like, $per_page, $offset];
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS cnt FROM users");
    $total = ($resCount && $rc = $resCount->fetch()) ? intval($rc['cnt']) : 0;
    $stmt = $conn->prepare("SELECT id, username, fullname, created_at FROM users ORDER BY id LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

$stmt->execute($params ?? []);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<tr><td colspan="5" class="customers-table-empty">Data user tidak ditemukan</td></tr>';
} else {
    foreach ($rows as $u) {
        echo '<tr>';
        echo '  <td><span class="customers-table-id">#' . intval($u['id']) . '</span></td>';
        echo '  <td class="customers-table-name">' . htmlspecialchars($u['username']) . '</td>';
        echo '  <td>' . htmlspecialchars($u['fullname'] ?? '-') . '</td>';
        echo '  <td>' . htmlspecialchars($u['created_at']) . '</td>';
        echo '  <td>';
        echo '    <div class="customers-table-actions">';
        echo '      <a class="acc-btn" href="admin.php?edit_user=' . intval($u['id']) . '#users">Edit</a>';
        echo '      <a class="acc-btn danger" href="admin.php?delete_user=' . intval($u['id']) . '#users" onclick="event.preventDefault(); customConfirm(\'Hapus user?\', () => { window.location.href = this.href; }, \'Hapus User\', \'danger\')">Hapus</a>';
        echo '    </div>';
        echo '  </td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'users');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
