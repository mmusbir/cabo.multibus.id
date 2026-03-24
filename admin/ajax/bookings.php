<?php
/**
 * admin/ajax/bookings.php - Handle bookings page data grouped by departure trip
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$baseFrom = "
    FROM bookings b
    LEFT JOIN trip_assignments ta
      ON ta.rute = b.rute
     AND ta.tanggal = b.tanggal
     AND ta.jam = b.jam
     AND ta.unit = b.unit
    LEFT JOIN drivers d ON d.id = ta.driver_id
    WHERE b.status != 'canceled' AND b.tanggal >= CURRENT_DATE
";
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $baseFrom .= "
      AND (
        b.rute LIKE ?
        OR COALESCE(d.nama, '') LIKE ?
        OR b.name LIKE ?
        OR b.phone LIKE ?
        OR b.tanggal LIKE ?
        OR b.jam LIKE ?
      )
    ";
    $params = [$like, $like, $like, $like, $like, $like];
}

$countSql = "SELECT COUNT(*) AS cnt FROM (
    SELECT b.rute, b.tanggal, b.jam, b.unit
    $baseFrom
    GROUP BY b.rute, b.tanggal, b.jam, b.unit
) trips";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$total = intval(($stmtCount->fetch(PDO::FETCH_ASSOC))['cnt'] ?? 0);

$sql = "SELECT
    b.rute,
    b.tanggal,
    b.jam,
    b.unit,
    COUNT(*) AS total_pax,
    SUM(CASE WHEN b.pembayaran = 'Lunas' THEN 1 ELSE 0 END) AS paid_count,
    SUM(CASE WHEN b.pembayaran <> 'Lunas' OR b.pembayaran IS NULL THEN 1 ELSE 0 END) AS unpaid_count,
    MAX(COALESCE(d.nama, '')) AS driver_name
    $baseFrom
    GROUP BY b.rute, b.tanggal, b.jam, b.unit
    ORDER BY
      CASE
        WHEN b.tanggal = CURRENT_DATE AND b.jam < CURRENT_TIME THEN 1
        ELSE 0
      END ASC,
      b.tanggal ASC,
      b.jam ASC,
      b.unit ASC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$queryParams = $params;
$queryParams[] = $per_page;
$queryParams[] = $offset;
$stmt->execute($queryParams);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Trip booking tidak ditemukan</div>';
} else {
    foreach ($rows as $trip) {
        $tanggal = $trip['tanggal'] ?? '';
        $jam = substr((string) ($trip['jam'] ?? ''), 0, 5);
        $unit = intval($trip['unit'] ?? 1);
        $tripCode = 'BUS-' . date('Ymd', strtotime($tanggal)) . '-' . str_replace(':', '', $jam) . '-U' . $unit;
        $tripDate = !empty($tanggal) ? strtoupper(date('d M', strtotime($tanggal))) : '-';
        $tripHour = $jam !== '' ? $jam : '--:--';
        $driverName = trim($trip['driver_name'] ?? '') !== '' ? trim($trip['driver_name']) : '-';
        $totalPax = intval($trip['total_pax'] ?? 0);
        $paidCount = intval($trip['paid_count'] ?? 0);
        $unpaidCount = intval($trip['unpaid_count'] ?? 0);
        $statusLabel = $driverName !== '-' ? 'CONFIRMED' : 'PENDING';
        $stateClass = $driverName !== '-' ? 'ready' : 'warning';

        echo '<div class="admin-card-compact kinetic-trip-card">';
        echo '  <div class="kinetic-trip-card-inner">';
        echo '    <div class="kinetic-trip-time">';
        echo '      <span class="kinetic-trip-date">' . htmlspecialchars($tripDate) . '</span>';
        echo '      <span class="kinetic-trip-hour">' . htmlspecialchars($tripHour) . '</span>';
        echo '      <span class="kinetic-trip-zone">WITA</span>';
        echo '    </div>';
        echo '    <div class="kinetic-trip-main">';
        echo '      <div>';
        echo '        <div class="kinetic-trip-meta">';
        echo '          <span class="kinetic-trip-state ' . $stateClass . '"><span class="status-dot"></span>' . htmlspecialchars($statusLabel) . '</span>';
        echo '          <span class="kinetic-trip-id">' . htmlspecialchars($tripCode) . '</span>';
        echo '        </div>';
        echo '        <h4 class="kinetic-trip-title">' . htmlspecialchars($trip['rute']) . '</h4>';
        echo '        <div class="kinetic-trip-subtitle">Keberangkatan ' . htmlspecialchars(date('d M Y', strtotime($tanggal)) . ' - ' . $tripHour) . ' / Unit ' . $unit . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">person</span>Driver: <strong>' . htmlspecialchars($driverName) . '</strong></div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">groups</span>Total booking customer: <strong>' . $totalPax . ' penumpang</strong></div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">confirmation_number</span>Lunas ' . $paidCount . ' / Belum lunas ' . $unpaidCount . '</div>';
        echo '      </div>';
        echo '      <div class="kinetic-trip-actions">';
        echo '        <a href="#" class="kinetic-trip-action" data-rute="' . htmlspecialchars($trip['rute']) . '" data-tanggal="' . htmlspecialchars($tanggal) . '" data-jam="' . htmlspecialchars($tripHour) . '" data-unit="' . $unit . '" onclick="event.preventDefault(); copyBookingTripManifest(this);"><span class="material-symbols-outlined">content_copy</span>Copy Data</a>';
        echo '        <a href="#" class="kinetic-trip-action primary" data-rute="' . htmlspecialchars($trip['rute']) . '" data-tanggal="' . htmlspecialchars($tanggal) . '" data-jam="' . htmlspecialchars($tripHour) . '" data-unit="' . $unit . '" onclick="event.preventDefault(); openBookingTripDetail(this);"><span class="material-symbols-outlined">list_alt</span>Detail</a>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'bookings');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;

