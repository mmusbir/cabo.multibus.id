<?php
/**
 * admin/ajax/cancellations.php - Audit logs table for admin
 */

global $conn;
require_once __DIR__ . '/../../config/activity_log.php';

activity_log_ensure_table($conn);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';

$allowedTypes = ['booking', 'charter', 'luggage', 'settings'];
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(summary ILIKE ? OR details ILIKE ? OR actor ILIKE ? OR action ILIKE ? OR category ILIKE ? OR entity_type ILIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($type !== '' && in_array($type, $allowedTypes, true)) {
    $where[] = "category = ?";
    $params[] = $type;
}

$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM activity_logs" . $whereSql);
$countStmt->execute($params);
$total = intval(($countStmt->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

$sql = "
    SELECT id, category, entity_type, entity_id, action, summary, details, actor, created_at
    FROM activity_logs
    {$whereSql}
    ORDER BY created_at DESC, id DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$queryParams = $params;
$queryParams[] = $per_page;
$queryParams[] = $offset;
$stmt->execute($queryParams);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryLabels = [
    'booking' => 'Booking',
    'charter' => 'Carter',
    'luggage' => 'Bagasi',
    'settings' => 'Pengaturan',
];

$actionLabels = [
    'create' => 'Tambah',
    'update' => 'Ubah',
    'delete' => 'Hapus',
    'cancel' => 'Batal',
    'mark_paid' => 'Lunas',
    'mark_all_paid' => 'Lunas Semua',
    'bop_done' => 'BOP Selesai',
    'import' => 'Import',
    'activate' => 'Aktif',
];

ob_start();
if (empty($rows)) {
    echo '<tr><td colspan="6" class="report-table-empty">Belum ada logs yang tercatat.</td></tr>';
} else {
    foreach ($rows as $row) {
        $category = strtolower(trim((string) ($row['category'] ?? '')));
        $action = strtolower(trim((string) ($row['action'] ?? '')));
        $tone = activity_log_tone($category, $action);
        $summary = trim((string) ($row['summary'] ?? '-'));
        $details = trim((string) ($row['details'] ?? ''));
        $actor = trim((string) ($row['actor'] ?? ''));
        $source = 'ADMIN';
        if ($actor === '' && in_array($category, ['booking', 'charter', 'luggage'], true)) {
            $source = 'WEB';
            $actor = 'Web Booking';
        } elseif (in_array(strtolower($actor), ['web', 'public'], true)) {
            $source = 'WEB';
            $actor = 'Web Booking';
        } elseif (in_array(strtolower($actor), ['system', 'cron'], true) || $actor === '') {
            $source = 'SYSTEM';
            $actor = 'System';
        }
        $absoluteTime = !empty($row['created_at']) ? date('d M Y H:i', strtotime((string) $row['created_at'])) . ' WITA' : '-';

        echo '<tr class="admin-log-row tone-' . htmlspecialchars($tone) . '">';
        echo '  <td class="admin-log-time-cell">';
        echo '    <div class="admin-log-time-main">' . htmlspecialchars(activity_log_relative_time($row['created_at'] ?? '')) . '</div>';
        echo '    <div class="admin-log-time-sub">' . htmlspecialchars($absoluteTime) . '</div>';
        echo '  </td>';
        echo '  <td><span class="admin-log-badge source-' . htmlspecialchars(strtolower($source)) . '">' . htmlspecialchars($source) . '</span></td>';
        echo '  <td><span class="admin-log-badge category-' . htmlspecialchars($category) . '">' . htmlspecialchars($categoryLabels[$category] ?? ucfirst($category)) . '</span></td>';
        echo '  <td><span class="admin-log-badge action-' . htmlspecialchars($tone) . '">' . htmlspecialchars($actionLabels[$action] ?? ucwords(str_replace('_', ' ', $action))) . '</span></td>';
        echo '  <td class="admin-log-summary-cell">';
        echo '    <div class="admin-log-summary">' . htmlspecialchars($summary) . '</div>';
        if ($details !== '') {
            echo '    <div class="admin-log-details">' . htmlspecialchars($details) . '</div>';
        }
        echo '  </td>';
        echo '  <td class="admin-log-actor-cell">' . htmlspecialchars($actor) . '</td>';
        echo '</tr>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'cancellations');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
