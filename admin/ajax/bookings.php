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
        $payClass = 'warning';
        if ($payStatus === 'Lunas') {
            $payClass = 'paid';
        } elseif (in_array($payStatus, ['Redbus', 'Traveloka'], true)) {
            $payClass = 'info';
        }

        $statusClass = $b['status'] === 'active' ? 'active' : 'canceled';
        $statusLabel = $b['status'] === 'active' ? 'Aktif' : 'Dibatalkan';
        $pickupPoint = trim($b['pickup_point'] ?? '');
        $segmentName = trim($b['segment_rute'] ?? '');
        $price = floatval($b['price'] ?? 0);
        $disc = floatval($b['discount'] ?? 0);
        $finalPrice = $price - $disc;

        echo '<div class="admin-card-compact">';
        echo '  <div class="acc-header">';
        echo '    <div>';
        echo '      <div class="acc-title">' . htmlspecialchars($b['name']) . '</div>';
        echo '      <div class="admin-card-subtitle">' . htmlspecialchars($b['rute']) . '</div>';
        echo '    </div>';
        echo '    <div class="admin-card-tags">';
        echo '      <div class="acc-id">' . $fmtId . '</div>';
        echo '      <span class="admin-status-pill ' . $payClass . '">' . htmlspecialchars($payStatus) . '</span>';
        echo '    </div>';
        echo '  </div>';

        echo '  <div class="acc-body">';
        echo '    <div class="acc-row"><div class="acc-label">Telepon</div><div class="acc-val">' . htmlspecialchars($b['phone']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Jadwal</div><div class="acc-val">' . htmlspecialchars($b['tanggal']) . ' - ' . htmlspecialchars(substr($b['jam'], 0, 5)) . '</div></div>';
        if ($pickupPoint !== '') {
            echo '    <div class="acc-row"><div class="acc-label">Pickup</div><div class="acc-val">' . htmlspecialchars($pickupPoint) . '</div></div>';
        }
        if ($segmentName !== '' || $price > 0) {
            echo '    <div class="acc-row"><div class="acc-label">Tarif</div><div class="acc-val admin-value-stack">';
            if ($segmentName !== '') {
                echo '      <div>' . htmlspecialchars($segmentName) . '</div>';
            }
            if ($price > 0) {
                echo '      <div class="admin-price-main">Rp ' . number_format($finalPrice, 0, ',', '.') . '</div>';
                if ($disc > 0) {
                    echo '      <div class="admin-price-note">Harga awal Rp ' . number_format($price, 0, ',', '.') . ' - diskon Rp ' . number_format($disc, 0, ',', '.') . '</div>';
                }
            }
            echo '    </div></div>';
        }
        echo '    <div class="acc-row"><div class="acc-label">Unit / Kursi</div><div class="acc-val">Unit ' . intval($b['unit']) . ' / Kursi ' . htmlspecialchars($b['seat']) . '</div></div>';
        echo '    <div class="acc-row"><div class="acc-label">Status</div><div class="acc-val"><span class="bc-status ' . $statusClass . '">' . $statusLabel . '</span></div></div>';
        echo '  </div>';

        echo '  <div class="acc-actions">';
        if ($b['status'] !== 'canceled') {
            echo '    <a href="#" class="acc-btn edit-booking-btn" data-id="' . $b['id'] . '" data-unit="' . htmlspecialchars($b['unit']) . '" data-rute="' . htmlspecialchars($b['rute']) . '" data-tanggal="' . htmlspecialchars($b['tanggal']) . '" data-jam="' . htmlspecialchars(substr($b['jam'], 0, 5)) . '" data-seat="' . htmlspecialchars($b['seat']) . '" data-pickup="' . htmlspecialchars($pickupPoint) . '" data-segment-id="' . intval($b['segment_id']) . '" data-price="' . floatval($b['price']) . '" data-discount="' . floatval($b['discount']) . '" title="Edit booking">Edit</a>';
            if (!$isPaid) {
                echo '    <a href="#" class="acc-btn success mark-paid" data-id="' . $b['id'] . '" title="Tandai lunas">Bayar</a>';
            }
            echo '    <a href="#" class="acc-btn danger cancel-link" data-id="' . $b['id'] . '" data-name="' . htmlspecialchars($b['name']) . '" data-phone="' . htmlspecialchars($b['phone']) . '" data-seat="' . htmlspecialchars($b['seat']) . '" data-tanggal="' . htmlspecialchars($b['tanggal']) . '" data-jam="' . htmlspecialchars(substr($b['jam'], 0, 5)) . '" title="Batalkan booking">Batalkan</a>';
        } else {
            echo '    <span class="small muted">Booking sudah dibatalkan</span>';
        }
        echo '  </div>';
        echo '</div>';
    }
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'bookings');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
