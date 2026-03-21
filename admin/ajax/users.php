<?php
/**
 * admin/ajax/users.php - Handle users page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

$resCount = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$total = ($resCount && $rc = $resCount->fetch()) ? intval($rc['cnt']) : 0;
$stmt = $conn->prepare("SELECT id,username,fullname,created_at FROM users ORDER BY id LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (count($rows) === 0) {
    echo '<tr><td colspan="5" class="small">Belum ada user.</td></tr>';
} else {
    $no = $offset + 1;
    foreach ($rows as $u) {
        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div class="acc-title">' . htmlspecialchars($u['username']) . '</div>';
        echo '    <div class="acc-id">#' . intval($u['id']) . '</div>';
        echo '  </div>';
        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">👤</div><div class="acc-val">' . htmlspecialchars($u['fullname']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">🕒</div><div class="acc-val">' . htmlspecialchars($u['created_at']) . '</div></div>';
        echo '  </div>';
        echo '  <div class="acc-actions">';
        echo '    <a class="acc-btn" href="admin.php?edit_user=' . intval($u['id']) . '#users">Edit</a>';
        echo '    <a class="acc-btn danger" href="admin.php?delete_user=' . intval($u['id']) . '#users" onclick="event.preventDefault(); customConfirm(\'Hapus user?\', () => { window.location.href = this.href; }, \'Hapus User\', \'danger\')">Hapus</a>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'users');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
