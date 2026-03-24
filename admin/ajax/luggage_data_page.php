<?php
/**
 * admin/ajax/luggage_data_page.php - AJAX handler for luggage data section (SPA)
 * Returns card-based HTML for the admin dashboard
 */

global $conn;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : 'all';

$whereClauses = ["1=1"];
$params = [];

if ($status_filter !== 'all' && $status_filter !== '') {
    $whereClauses[] = "l.status = ?";
    $params[] = $status_filter;
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $whereClauses[] = "(l.sender_name LIKE ? OR l.receiver_name LIKE ? OR l.sender_phone LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(" AND ", $whereClauses);
$countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM luggages l WHERE $whereSql");
$countStmt->execute($params);
$total = intval($countStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

$stmt = $conn->prepare("SELECT l.*, s.name as service_name FROM luggages l LEFT JOIN luggage_services s ON l.service_id = s.id WHERE $whereSql ORDER BY l.created_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Tidak ada data bagasi ditemukan</div>';
} else {
    foreach ($rows as $l) {
        $status = $l['status'];
        $payment = $l['payment_status'];

        $statusLabel = 'PENDING';
        $stateClass = 'warning';
        if ($status === 'active') {
            $statusLabel = 'TERANGKUT';
            $stateClass = 'ready';
        } elseif ($status === 'canceled') {
            $statusLabel = 'DIBATALKAN';
            $stateClass = 'danger';
        } elseif ($status === 'finished') {
            $statusLabel = 'SELESAI';
            $stateClass = '';
        }

        $serviceName = $l['service_name'] ?: '-';
        $tripDate = !empty($l['created_at']) ? strtoupper(date('d M', strtotime($l['created_at']))) : '-';
        $tripHour = !empty($l['created_at']) ? date('H:i', strtotime($l['created_at'])) : '--:--';

        echo '<div class="admin-card-compact kinetic-trip-card">';
        echo '  <div class="kinetic-trip-card-inner">';
        echo '    <div class="kinetic-trip-time">';
        echo '      <span class="kinetic-trip-date">' . htmlspecialchars($tripDate) . '</span>';
        echo '      <span class="kinetic-trip-hour">' . htmlspecialchars($tripHour) . '</span>';
        echo '      <span class="kinetic-trip-zone">LOG</span>';
        echo '    </div>';
        echo '    <div class="kinetic-trip-main">';
        echo '      <div>';
        echo '        <div class="kinetic-trip-meta">';
        echo '          <span class="kinetic-trip-state ' . $stateClass . '"><span class="status-dot"></span>' . htmlspecialchars($statusLabel) . '</span>';
        echo '          <span class="kinetic-trip-id">#LUG' . str_pad($l['id'], 5, '0', STR_PAD_LEFT) . '</span>';
        echo '        </div>';
        echo '        <h4 class="kinetic-trip-title">' . htmlspecialchars($l['sender_name']) . ' → ' . htmlspecialchars($l['receiver_name']) . '</h4>';
        echo '        <div class="kinetic-trip-subtitle">' . htmlspecialchars($serviceName) . ' / Qty ' . intval($l['quantity']) . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">call</span><strong>' . htmlspecialchars($l['sender_phone']) . '</strong></div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">call_received</span>' . htmlspecialchars($l['receiver_phone']) . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">payments</span>Rp ' . number_format($l['price'], 0, ',', '.') . ' — ' . htmlspecialchars($payment) . '</div>';

        $noteExtra = trim($l['notes'] ?? '');
        if ($noteExtra) {
            echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">notes</span>' . htmlspecialchars($noteExtra) . '</div>';
        }
        echo '      </div>';

        // Actions
        echo '      <div class="kinetic-trip-actions">';
        if ($status === 'pending') {
            echo '        <a href="#" class="kinetic-trip-action" onclick="event.preventDefault();luggageUpdateStatus(' . intval($l['id']) . ', \'active\')" title="Terangkut"><span class="material-symbols-outlined">local_shipping</span>Angkut</a>';
        }
        if ($status === 'active') {
            echo '        <a href="#" class="kinetic-trip-action success" onclick="event.preventDefault();luggageUpdateStatus(' . intval($l['id']) . ', \'finished\')" title="Selesai"><span class="material-symbols-outlined">done_all</span>Selesai</a>';
        }
        if ($status !== 'canceled' && $status !== 'finished') {
            echo '        <a href="#" class="kinetic-trip-action danger" onclick="event.preventDefault();luggageUpdateStatus(' . intval($l['id']) . ', \'canceled\')" title="Batalkan"><span class="material-symbols-outlined">block</span>Batal</a>';
        }
        echo '      </div>';

        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'total' => $total]);
exit;
