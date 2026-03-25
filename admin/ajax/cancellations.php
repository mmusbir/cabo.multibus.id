<?php
/**
 * admin/ajax/cancellations.php - Logs activity feed for admin
 */

global $conn;

if (!function_exists('format_admin_relative_time')) {
    function format_admin_relative_time($datetime)
    {
        if (empty($datetime)) return '-';
        $timestamp = strtotime((string) $datetime);
        if (!$timestamp) return '-';

        $diff = time() - $timestamp;
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 172800) return 'Kemarin';
        if ($diff < 2592000) return floor($diff / 86400) . ' hari lalu';

        return date('d M Y - H:i', $timestamp) . ' WITA';
    }
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

$activitySql = "
    SELECT *
    FROM (
        SELECT
            b.created_at,
            'booking' AS type,
            'Booking: ' || COALESCE(NULLIF(b.name, ''), 'Customer') AS title,
            COALESCE(NULLIF(b.rute, ''), '-') AS meta,
            CASE
                WHEN COALESCE(b.pembayaran, 'Belum Lunas') IN ('Lunas', 'Redbus', 'Traveloka') THEN 'success'
                ELSE 'warning'
            END AS tone,
            UPPER(COALESCE(NULLIF(b.pembayaran, ''), 'Belum Lunas')) AS tag
        FROM bookings b

        UNION ALL

        SELECT
            c.created_at,
            'charter' AS type,
            'Charter: ' || COALESCE(NULLIF(c.name, ''), 'Customer') AS title,
            COALESCE(NULLIF(c.pickup_point, ''), '?') || ' -> ' || COALESCE(NULLIF(c.drop_point, ''), '?') AS meta,
            'primary' AS tone,
            'CHARTER' AS tag
        FROM charters c

        UNION ALL

        SELECT
            l.created_at,
            'luggage' AS type,
            'Paket: ' || COALESCE(NULLIF(l.sender_name, ''), 'Sender') AS title,
            'Pengiriman Barang/Dokumen' AS meta,
            'info' AS tone,
            'BAGASI' AS tag
        FROM luggages l
    ) logs
";

$params = [];
if ($search !== '') {
    $activitySql .= " WHERE title ILIKE ? OR meta ILIKE ? OR tag ILIKE ? ";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

if ($type !== '' && in_array($type, ['booking', 'charter', 'luggage'], true)) {
    $activitySql .= ($search !== '' ? " AND " : " WHERE ") . " type = ? ";
    $params[] = $type;
}

$countSql = "SELECT COUNT(*) AS cnt FROM (" . $activitySql . ") activity_count";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$total = intval(($stmtCount->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

$sql = $activitySql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$queryParams = $params;
$queryParams[] = $per_page;
$queryParams[] = $offset;
$stmt->execute($queryParams);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Belum ada logs activity.</div>';
} else {
    foreach ($rows as $log) {
        $type = strtolower(trim((string) ($log['type'] ?? 'activity')));
        $tone = trim((string) ($log['tone'] ?? 'warning')) ?: 'warning';
        $typeLabel = strtoupper($type);
        $icon = 'history';
        if ($type === 'booking') $icon = 'confirmation_number';
        if ($type === 'charter') $icon = 'airport_shuttle';
        if ($type === 'luggage') $icon = 'inventory_2';

        $createdAt = trim((string) ($log['created_at'] ?? ''));
        $timeLabel = format_admin_relative_time($createdAt);

        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div>';
        echo '      <div class="acc-title">' . htmlspecialchars($log['title'] ?? '-') . '</div>';
        echo '      <div class="admin-card-subtitle">' . htmlspecialchars($log['meta'] ?? '-') . '</div>';
        echo '    </div>';
        echo '    <div class="admin-card-tags">';
        echo '      <div class="acc-id"><span class="material-symbols-outlined" style="font-size:0.95rem;vertical-align:middle;">' . htmlspecialchars($icon) . '</span> ' . htmlspecialchars($typeLabel) . '</div>';
        echo '      <span class="admin-status-pill ' . htmlspecialchars($tone) . '">' . htmlspecialchars($log['tag'] ?? '-') . '</span>';
        echo '    </div>';
        echo '  </div>';
        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Kategori</div><div class="acc-val">' . htmlspecialchars($typeLabel) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Waktu</div><div class="acc-val">' . htmlspecialchars($timeLabel) . '</div></div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'cancellations');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
