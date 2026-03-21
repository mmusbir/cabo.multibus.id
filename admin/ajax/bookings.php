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
    // Check if search term implies an ID search
    $idSearch = null;
    if (preg_match('/^#?CBP\d{2}(\d+)$/i', $search, $m)) {
        // Matches full format #CBP2600001 or CBP2600001
        $idSearch = intval($m[1]);
    } elseif (preg_match('/^#?CBP/i', $search)) {
        // Starts with CBP but incomplete
    } elseif (is_numeric($search)) {
        $idSearch = intval($search);
    }

    if ($idSearch) {
        // Precise ID search
        try {
            $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE id = ?");
            $stmtc->execute([$idSearch]);
            $rc = $stmtc->fetch();
            $total = intval($rc['cnt']);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
            exit;
        }
        $stmt = $conn->prepare("SELECT b.id,b.rute,b.tanggal,b.jam,b.unit,b.seat,b.name,b.phone,b.status,b.pembayaran,b.pickup_point,b.created_at,b.segment_id,b.price,b.discount,s.rute AS segment_rute FROM bookings b LEFT JOIN segments s ON b.segment_id = s.id WHERE b.id = ?");
        $params = [$idSearch];
    } else {
        // Name/Phone search
        $like = '%' . $search . '%';
        try {
            $stmtc = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE (name LIKE ? OR phone LIKE ?) AND tanggal >= CURRENT_DATE");
            $stmtc->execute([$like, $like]);
            $rc = $stmtc->fetch();
            $total = intval($rc['cnt']);
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
    $total = ($resCountB && $rb = $resCountB->fetch()) ? intval($rb['cnt']) : 0;
    $stmt = $conn->prepare("SELECT b.id,b.rute,b.tanggal,b.jam,b.unit,b.seat,b.name,b.phone,b.status,b.pembayaran,b.pickup_point,b.created_at,b.segment_id,b.price,b.discount,s.rute AS segment_rute FROM bookings b LEFT JOIN segments s ON b.segment_id = s.id WHERE b.tanggal >= CURRENT_DATE ORDER BY b.tanggal ASC, b.jam ASC, CAST(b.seat AS INTEGER) ASC LIMIT ? OFFSET ?");
    $params = [$per_page, $offset];
}

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}
$stmt->execute($params ?? []);

ob_start();
$no = $offset + 1;
while ($b = $stmt->fetch()) {
    $fmtId = formatBookingId($b['id'], $b['created_at']);
    $isPaid = ($b['pembayaran'] === 'Lunas');
    $statusClass = $b['status'] === 'active' ? 'booked' : 'canceled';

    echo '<div class="admin-card-compact">';

    // Header: Name + Booking ID + Payment Badge
    echo '  <div class="acc-header">';
    echo '    <div class="acc-title">' . htmlspecialchars($b['name']) . '</div>';

    $payStatus = $b['pembayaran'] ?? 'Belum Lunas';
    $payColor = '#f59e0b'; // Default Orange (Belum Lunas)
    if ($payStatus === 'Lunas')
        $payColor = '#10b981'; // Green
    elseif ($payStatus === 'Redbus')
        $payColor = '#ef4444'; // Red

    echo '    <div style="display:flex;align-items:center;gap:6px">';
    echo '      <div class="acc-id">' . $fmtId . '</div>';
    echo '      <span style="font-size:10px;padding:2px 6px;border-radius:4px;color:#fff;background:' . $payColor . '">' . htmlspecialchars($payStatus) . '</span>';
    echo '    </div>';
    echo '  </div>';

    // Body: Details
    echo '  <div class="acc-body">';

    // Phone
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">No. HP</div>';
    echo '      <div class="acc-val">' . htmlspecialchars($b['phone']) . '</div>';
    echo '    </div>';

    // Route
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Rute</div>';
    echo '      <div class="acc-val">' . htmlspecialchars($b['rute']) . '</div>';
    echo '    </div>';

    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Jadwal</div>';
    echo '      <div class="acc-val">' . $b['tanggal'] . ' • ' . substr($b['jam'], 0, 5) . '</div>';
    echo '    </div>';

    // Segment & Price
    if ($b['segment_id'] > 0 || $b['price'] > 0) {
        $segName = htmlspecialchars($b['segment_rute'] ?? '-');
        $price = floatval($b['price']);
        $disc = floatval($b['discount']);
        $final = $price - $disc;
        echo '    <div class="acc-row">';
        echo '      <div class="acc-label">Segment</div>';
        echo '      <div class="acc-val">';
        echo '        <div>' . $segName . '</div>';
        echo '        <div style="font-weight:600;color:#10b981">Rp ' . number_format($final, 0, ',', '.') . '</div>';
        if ($disc > 0)
            echo '        <div class="small muted" style="text-decoration:line-through;font-size:11px">Rp ' . number_format($price, 0, ',', '.') . ' (Disc: ' . number_format($disc, 0, ',', '.') . ')</div>';
        echo '      </div>';
        echo '    </div>';
    }

    // Unit & Seat
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Unit / Kursi</div>';
    echo '      <div class="acc-val">Unit ' . $b['unit'] . ' • Kursi ' . htmlspecialchars($b['seat']) . '</div>';
    echo '    </div>';

    // Booking Status
    echo '    <div class="acc-row">';
    echo '      <div class="acc-label">Status</div>';
    echo '      <div class="acc-val">';
    echo '        <span class="bc-status ' . $statusClass . '">' . ucfirst($b['status']) . '</span>';
    echo '      </div>';
    echo '    </div>';

    echo '  </div>'; // acc-body

    // Actions
    echo '  <div class="acc-actions">';
    if ($b['status'] !== 'canceled') {
        echo '    <a href="#" class="acc-btn edit-booking-btn" data-id="' . $b['id'] . '" data-unit="' . htmlspecialchars($b['unit']) . '" data-rute="' . htmlspecialchars($b['rute']) . '" data-tanggal="' . $b['tanggal'] . '" data-jam="' . substr($b['jam'], 0, 5) . '" data-seat="' . htmlspecialchars($b['seat']) . '" data-pickup="' . htmlspecialchars($b['pickup_point'] ?? '') . '" data-segment-id="' . intval($b['segment_id']) . '" data-price="' . floatval($b['price']) . '" data-discount="' . floatval($b['discount']) . '" title="Edit">✏️ Edit</a>';
        if (!$isPaid) {
            echo '    <a href="#" class="acc-btn mark-paid" data-id="' . $b['id'] . '" title="Lunas">💰 Bayar</a>';
        }
        echo '    <a href="#" class="acc-btn danger cancel-link" data-id="' . $b['id'] . '" data-name="' . htmlspecialchars($b['name']) . '" data-phone="' . htmlspecialchars($b['phone']) . '" data-seat="' . htmlspecialchars($b['seat']) . '" data-tanggal="' . $b['tanggal'] . '" data-jam="' . substr($b['jam'], 0, 5) . '" title="Batal">❌ Batal</a>';
    } else {
        echo '    <span class="small muted">Dibatalkan</span>';
    }
    echo '  </div>'; // acc-actions

    echo '</div>'; // admin-card-compact
}
$rows_html = ob_get_clean();
$pag_html = render_pagination_ajax($total, $per_page, $page, 'bookings');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'rows' => $rows_html, 'pagination' => $pag_html, 'total' => $total]);
exit;
