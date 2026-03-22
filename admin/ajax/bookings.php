<?php
/**
 * admin/ajax/bookings.php - Handle bookings page data
 */

global $conn;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $idSearch = null;
    if (preg_match('/^#?CBP\d{2}(\d+)$/i', $search, $m)) {
        $idSearch = intval($m[1]);
    } elseif (is_numeric($search)) {
        $idSearch = intval($search);
    }

    if ($idSearch) {
        try {
            $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE id = ?");
            $stmtc->execute([$idSearch]);
            $rc = $stmtc->fetch(PDO::FETCH_ASSOC);
            $total = intval($rc['cnt'] ?? 0);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
            exit;
        }
        $stmt = $conn->prepare("SELECT b.id,b.rute,b.tanggal,b.jam,b.unit,b.seat,b.name,b.phone,b.status,b.pembayaran,b.pickup_point,b.created_at,b.segment_id,b.price,b.discount,s.rute AS segment_rute FROM bookings b LEFT JOIN segments s ON b.segment_id = s.id WHERE b.id = ?");
        $params = [$idSearch];
    } else {
        $like = '%' . $search . '%';
        try {
            $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE (name LIKE ? OR phone LIKE ?) AND tanggal >= CURRENT_DATE");
            $stmtc->execute([$like, $like]);
            $rc = $stmtc->fetch(PDO::FETCH_ASSOC);
            $total = intval($rc['cnt'] ?? 0);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
            exit;
        }
        $stmt = $conn->prepare("SELECT b.id,b.rute,b.tanggal,b.jam,b.unit,b.seat,b.name,b.phone,b.status,b.pembayaran,b.pickup_point,b.created_at,b.segment_id,b.price,b.discount,s.rute AS segment_rute FROM bookings b LEFT JOIN segments s ON b.segment_id = s.id WHERE (b.name LIKE ? OR b.phone LIKE ?) AND b.tanggal >= CURRENT_DATE ORDER BY b.tanggal ASC, b.jam ASC, CAST(b.seat AS INTEGER) ASC LIMIT ? OFFSET ?");
        $params = [$like, $like, $per_page, $offset];
    }
} else {
    $resCountB = $conn->query("SELECT COUNT(*) AS cnt FROM bookings WHERE tanggal >= CURRENT_DATE");
    $total = ($resCountB && $rb = $resCountB->fetch(PDO::FETCH_ASSOC)) ? intval($rb['cnt']) : 0;
    $stmt = $conn->prepare("SELECT b.id,b.rute,b.tanggal,b.jam,b.unit,b.seat,b.name,b.phone,b.status,b.pembayaran,b.pickup_point,b.created_at,b.segment_id,b.price,b.discount,s.rute AS segment_rute FROM bookings b LEFT JOIN segments s ON b.segment_id = s.id WHERE b.tanggal >= CURRENT_DATE ORDER BY b.tanggal ASC, b.jam ASC, CAST(b.seat AS INTEGER) ASC LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}
$stmt->execute($params ?? []);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($rows)) {
    echo '<div class="small admin-empty-state admin-grid-message">Booking tidak ditemukan</div>';
} else {
    foreach ($rows as $b) {
        $fmtId = formatBookingId($b['id'], $b['created_at']);
        $isPaid = ($b['pembayaran'] === 'Lunas');
        $payStatus = $b['pembayaran'] ?? 'Belum Lunas';
        $statusLabel = $b['status'] === 'active' ? 'READY' : 'CANCELED';
        $stateClass = $b['status'] === 'active' ? 'ready' : 'danger';
        $noteText = 'Booking Open';
        $noteClass = 'muted';

        if ($payStatus === 'Lunas') {
            $noteText = 'Manifest Verified';
            $noteClass = '';
        } elseif (in_array($payStatus, ['Redbus', 'Traveloka'], true)) {
            $noteText = 'Channel Partner';
            $noteClass = 'warning';
        } elseif ($b['status'] !== 'active') {
            $noteText = 'Booking Dibatalkan';
            $noteClass = 'danger';
        }

        $pickupPoint = trim($b['pickup_point'] ?? '');
        $segmentName = trim($b['segment_rute'] ?? '');
        $price = floatval($b['price'] ?? 0);
        $disc = floatval($b['discount'] ?? 0);
        $finalPrice = max(0, $price - $disc);
        $tripDate = !empty($b['tanggal']) ? strtoupper(date('d M', strtotime($b['tanggal']))) : '-';
        $tripHour = !empty($b['jam']) ? substr($b['jam'], 0, 5) : '--:--';
        $subtitle = $segmentName !== '' ? $segmentName : $b['rute'];
        $pickupLabel = $pickupPoint !== '' ? $pickupPoint : 'Pickup belum diisi';

        echo '<div class="admin-card-compact kinetic-trip-card">';
        echo '  <div class="kinetic-trip-card-inner">';
        echo '    <div class="kinetic-trip-time">';
        echo '      <span class="kinetic-trip-date">' . htmlspecialchars($tripDate) . '</span>';
        echo '      <span class="kinetic-trip-hour">' . htmlspecialchars($tripHour) . '</span>';
        echo '      <span class="kinetic-trip-zone">WIB</span>';
        echo '    </div>';
        echo '    <div class="kinetic-trip-main">';
        echo '      <div>';
        echo '        <div class="kinetic-trip-meta">';
        echo '          <span class="kinetic-trip-state ' . $stateClass . '"><span class="status-dot"></span>' . htmlspecialchars($statusLabel) . '</span>';
        echo '          <span class="kinetic-trip-id">' . htmlspecialchars($fmtId) . '</span>';
        echo '          <span class="status-tag">' . htmlspecialchars($payStatus) . '</span>';
        echo '        </div>';
        echo '        <h4 class="kinetic-trip-title">' . htmlspecialchars($b['name']) . '</h4>';
        echo '        <div class="kinetic-trip-subtitle">' . htmlspecialchars($subtitle) . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">call</span><strong>' . htmlspecialchars($b['phone']) . '</strong></div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">event_seat</span>Unit ' . intval($b['unit']) . ' - Kursi ' . htmlspecialchars($b['seat']) . '</div>';
        echo '        <div class="kinetic-trip-line"><span class="material-symbols-outlined">location_on</span>' . htmlspecialchars($pickupLabel) . '</div>';
        echo '      </div>';
        echo '      <div class="kinetic-trip-stat">';
        echo '        <div class="kinetic-trip-stat-label">Fare Snapshot</div>';
        echo '        <div class="kinetic-trip-progress">';
        echo '          <div class="kinetic-trip-progress-bar"><span class="kinetic-trip-progress-fill" style="width:' . ($isPaid ? '100' : '58') . '%"></span></div>';
        echo '          <div class="kinetic-trip-progress-value">Rp ' . number_format($finalPrice, 0, ',', '.') . '<span> final</span></div>';
        echo '        </div>';
        if ($price > 0 && $disc > 0) {
            echo '        <div class="kinetic-trip-note muted"><span class="material-symbols-outlined">sell</span>Harga awal Rp ' . number_format($price, 0, ',', '.') . ' - diskon Rp ' . number_format($disc, 0, ',', '.') . '</div>';
        } else {
            echo '        <div class="kinetic-trip-note ' . $noteClass . '"><span class="material-symbols-outlined">info</span>' . htmlspecialchars($noteText) . '</div>';
        }
        echo '      </div>';
        echo '      <div class="kinetic-trip-actions">';
        if ($b['status'] !== 'canceled') {
            echo '        <a href="#" class="kinetic-trip-action edit-booking-btn" data-id="' . $b['id'] . '" data-unit="' . htmlspecialchars($b['unit']) . '" data-rute="' . htmlspecialchars($b['rute']) . '" data-tanggal="' . htmlspecialchars($b['tanggal']) . '" data-jam="' . htmlspecialchars(substr($b['jam'], 0, 5)) . '" data-seat="' . htmlspecialchars($b['seat']) . '" data-pickup="' . htmlspecialchars($pickupPoint) . '" data-segment-id="' . intval($b['segment_id']) . '" data-price="' . floatval($b['price']) . '" data-discount="' . floatval($b['discount']) . '"><span class="material-symbols-outlined">edit_square</span>Edit</a>';
            if (!$isPaid) {
                echo '        <a href="#" class="kinetic-trip-action success mark-paid" data-id="' . $b['id'] . '"><span class="material-symbols-outlined">check_circle</span>Bayar</a>';
            }
            echo '        <a href="#" class="kinetic-trip-action danger cancel-link" data-id="' . $b['id'] . '" data-name="' . htmlspecialchars($b['name']) . '" data-phone="' . htmlspecialchars($b['phone']) . '" data-seat="' . htmlspecialchars($b['seat']) . '" data-tanggal="' . htmlspecialchars($b['tanggal']) . '" data-jam="' . htmlspecialchars(substr($b['jam'], 0, 5)) . '"><span class="material-symbols-outlined">block</span>Batalkan</a>';
        } else {
            echo '        <span class="kinetic-trip-action muted">Booking sudah dibatalkan</span>';
        }
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
