<?php
/**
 * AJAX Handler: getPassengers
 * Returns passenger list with seat layout for a specific trip
 */

$rute = $_GET['rute'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$jam = $_GET['jam'] ?? '';
$unit = isset($_GET['unit']) ? intval($_GET['unit']) : 1;

header('Content-Type: application/json');

if (!$rute || !$tanggal || !$jam || !validDate($tanggal) || !validTime($jam)) {
    echo json_encode(['success' => false, 'error' => 'invalid_params']);
    exit;
}

// 1. Get Assigned Driver
$assignedDriverName = '-';
$assignedDriverId = 0;
$stmtD = $conn->prepare("
    SELECT ta.driver_id, d.nama 
    FROM trip_assignments ta 
    JOIN drivers d ON ta.driver_id = d.id 
    WHERE ta.rute=? AND ta.tanggal=? AND ta.jam=? AND ta.unit=?
");
if ($stmtD) {
    try {
        $stmtD->execute([$rute, $tanggal, $jam, $unit]);
        $resD = $stmtD->fetch();
        if ($resD) {
            $assignedDriverName = $resD['nama'];
            $assignedDriverId = intval($resD['driver_id']);
        }
    } catch (PDOException $e) {
        // Handle silently or log
    }
}

// 2. Get All Drivers (for dropdown)
$allDrivers = [];
$resAllD = $conn->query("SELECT id, nama FROM drivers ORDER BY nama ASC");
while ($rd = $resAllD->fetch()) {
    $allDrivers[] = $rd;
}

// 3. Get Bookings
$stmt = $conn->prepare("
  SELECT b.id, b.name, b.phone, b.pickup_point, b.pembayaran, b.seat, b.price, b.discount, c.address AS gmaps 
  FROM bookings b 
  LEFT JOIN customers c ON b.phone = c.phone 
  WHERE b.rute=? AND b.tanggal=? AND b.jam=? AND b.unit=? AND b.status!='canceled' 
  ORDER BY CAST(b.seat AS INTEGER)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}
try {
    $stmt->execute([$rute, $tanggal, $jam, $unit]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'db_error', 'detail' => $e->getMessage()]);
    exit;
}
$seats = [];
for ($i = 1; $i <= 8; $i++)
    $seats[(string) $i] = null;

// Calculate Summary
$totalPaid = 0;
$countPaid = 0;
$totalUnpaid = 0;
$countUnpaid = 0;

foreach ($rows as $r) {
    $seat = trim((string) $r['seat']);
    $pPrice = floatval($r['price'] ?? 0);
    $pDisc = floatval($r['discount'] ?? 0);
    $final = max(0, $pPrice - $pDisc);

    if ($r['pembayaran'] === 'Lunas') {
        $totalPaid += $final;
        $countPaid++;
    } else {
        $totalUnpaid += $final;
        $countUnpaid++;
    }

    if ($seat === '')
        continue;
    if (is_numeric($seat) && intval($seat) >= 1 && intval($seat) <= 8)
        $seats[(string) intval($seat)] = $r;
    else
        $seats[$seat] = $r;
}

ob_start();

// INFO CARD (HEADER)
$tglIndo = date('d M Y', strtotime($tanggal));
$jamIndo = substr($jam, 0, 5);
$totalPax = count($rows);

echo '<div id="departureInfoCard" class="info-card" style="margin-bottom:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;" data-driver-name="' . htmlspecialchars($assignedDriverName) . '">';
echo '  <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">';
echo '    <span style="font-size:16px;">🚐</span>';
echo '    <div style="font-weight:700; color:#0f172a; font-size:15px;">Info Pemberangkatan</div>';
echo '  </div>';
echo '  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; font-size:13px;">';
echo '    <div><span style="color:#64748b;">Rute:</span> <br><strong>' . htmlspecialchars($rute) . '</strong></div>';
echo '    <div><span style="color:#64748b;">Waktu:</span> <br><strong>' . $tglIndo . ' - ' . $jamIndo . '</strong></div>';
echo '    <div><span style="color:#64748b;">Unit:</span> <br><strong>Unit ' . intval($unit) . '</strong></div>';
echo '    <div><span style="color:#64748b;">Penumpang:</span> <br><strong>' . $totalPax . ' Orang</strong></div>';

// Driver Row with Edit Logic
echo '    <div style="grid-column: 1 / -1; margin-top:4px; border-top:1px dashed #e2e8f0; padding-top:4px;">';
echo '      <span style="color:#64748b;">Driver:</span> ';

// View Mode
echo '      <span id="driverDisplay">';
echo '        <strong id="driverNameText">' . htmlspecialchars($assignedDriverName) . '</strong>';
echo '        <button onclick="document.getElementById(\'driverDisplay\').style.display=\'none\'; document.getElementById(\'driverEdit\').style.display=\'flex\';" style="border:none; background:none; cursor:pointer; font-size:14px; margin-left:4px;" title="Edit Driver">✏️</button>';
echo '      </span>';

// Edit Mode
echo '      <div id="driverEdit" style="display:none; gap:4px; align-items:center;">';
echo '        <select id="driverSelect" style="padding:4px; font-size:12px; border-radius:4px; border:1px solid #cbd5e1;">';
echo '          <option value="0">-- Pilih Driver --</option>';
foreach ($allDrivers as $drv) {
    $sel = ($drv['id'] == $assignedDriverId) ? 'selected' : '';
    echo '          <option value="' . $drv['id'] . '" ' . $sel . '>' . htmlspecialchars($drv['nama']) . '</option>';
}
echo '        </select>';
echo '        <button onclick="saveDriverAssignment(\'' . $rute . '\', \'' . $tanggal . '\', \'' . $jam . '\', ' . $unit . ')" class="btn-bright" style="padding:4px 8px; font-size:11px;">Simpan</button>';
echo '        <button onclick="document.getElementById(\'driverEdit\').style.display=\'none\'; document.getElementById(\'driverDisplay\').style.display=\'inline\';" style="padding:4px 8px; font-size:11px; border:1px solid #ccc; background:#fff; border-radius:4px; cursor:pointer;">Batal</button>';
echo '      </div>';

echo '    </div>'; // End Driver Row

echo '  </div>';
echo '</div>';

foreach ($seats as $num => $p) {
    echo '<div class="seat-block" data-seat="' . htmlspecialchars($num) . '">';

    // Header: Badge Kursi + Status + Actions
    echo '<div class="seat-head">';
    echo '  <div class="seat-title-group">';
    echo '    <div class="seat-badge-num">Kursi ' . htmlspecialchars($num) . '</div>';
    if ($p !== null) {
        $payStatus = $p['pembayaran'] ?? 'Belum Lunas';
        $payClass = ($payStatus === 'Lunas') ? 'paid' : 'unpaid';
        echo '    <div class="seat-pay-badge ' . $payClass . '">' . htmlspecialchars($payStatus) . '</div>';
    }
    echo '  </div>'; // title-group

    echo '  <div class="seat-actions">';
    echo '    <button class="copy-single copy-btn" data-seat="' . htmlspecialchars($num) . '" title="Copy Detail"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg></button>';
    if ($p !== null) {
        if ($p['pembayaran'] !== 'Lunas') {
            echo '    <button class="mark-paid-seat btn-action-icon pay" data-id="' . htmlspecialchars($p['id']) . '" title="Mark Lunas">💰</button>';
        }
        echo '    <button class="cancel-btn btn-action-icon cancel" data-id="' . htmlspecialchars($p['id']) . '" title="Batalkan">❌</button>';
    }
    echo '  </div>'; // actions
    echo '</div>'; // head

    if ($p !== null) {
        echo '<div class="seat-body">';

        echo '  <div class="sb-row">';
        echo '    <div class="sb-icon" title="Nama">👤</div>';
        echo '    <div class="sb-content">';
        echo '      <div class="sb-label">Nama</div>';
        echo '      <div class="sb-val name">' . htmlspecialchars($p['name'] ?? '-') . '</div>';
        echo '    </div>';
        echo '  </div>';

        echo '  <div class="sb-row">';
        echo '    <div class="sb-icon" title="No. HP">📱</div>';
        echo '    <div class="sb-content">';
        echo '      <div class="sb-label">No. HP</div>';
        echo '      <div class="sb-val phone">' . htmlspecialchars($p['phone'] ?? '-') . '</div>';
        echo '    </div>';
        echo '  </div>';

        if (!empty($p['pickup_point'])) {
            echo '  <div class="sb-row">';
            echo '    <div class="sb-icon" title="Titik Jemput">📍</div>';
            echo '    <div class="sb-content">';
            echo '      <div class="sb-label">Titik Jemput</div>';
            echo '      <div class="sb-val pickup">' . htmlspecialchars($p['pickup_point']) . '</div>';
            echo '    </div>';
            echo '  </div>';
        }

        if (!empty($p['gmaps'])) {
            echo '  <div class="sb-row">';
            echo '    <div class="sb-icon" title="Maps">🗺️</div>';
            echo '    <div class="sb-content">';
            echo '      <div class="sb-label">Google Maps</div>';
            echo '      <div class="sb-val gmaps">' . htmlspecialchars($p['gmaps']) . '</div>';
            echo '    </div>';
            echo '  </div>';
        }

        // Hidden vals for copy logic
        echo '<span class="sb-val pay" style="display:none">' . htmlspecialchars($p['pembayaran'] ?? '') . '</span>';
        echo '</div>'; // body
    } else {
        echo '<div class="seat-body empty-state"><div class="muted">- Kosong -</div></div>';
    }

    echo '</div>'; // seat-block
}

// SUMMARY FOOTER
echo '<div id="passengerSummary" class="summary-footer" data-paid="' . $totalPaid . '" data-unpaid="' . $totalUnpaid . '">';
echo '  <div class="sum-row paid">';
echo '    <div class="sum-label"><span class="indicator"></span>Sudah Lunas (' . $countPaid . ')</div>';
echo '    <div class="sum-val">Rp ' . number_format($totalPaid, 0, ',', '.') . '</div>';
echo '  </div>';
echo '  <div class="sum-row unpaid">';
echo '    <div class="sum-label"><span class="indicator"></span>Belum Lunas (' . $countUnpaid . ')</div>';
echo '    <div class="sum-val">Rp ' . number_format($totalUnpaid, 0, ',', '.') . '</div>';
echo '  </div>';
echo '  <div class="sum-total">';
echo '    <div class="sum-label">Total Estimasi</div>';
echo '    <div class="sum-val">Rp ' . number_format($totalPaid + $totalUnpaid, 0, ',', '.') . '</div>';
echo '  </div>';
echo '</div>';

// Add CSS for summary footer inline
echo '<style>
  .summary-footer { margin-top:20px; background:#fff; border-radius:12px; padding:16px; border:1px solid #e2e8f0; box-shadow:0 2px 4px rgba(0,0,0,0.02); }
  .sum-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px; }
  .sum-row.paid .indicator { display:inline-block; width:8px; height:8px; background:#10b981; border-radius:50%; margin-right:8px; }
  .sum-row.unpaid .indicator { display:inline-block; width:8px; height:8px; background:#f59e0b; border-radius:50%; margin-right:8px; }
  .sum-val { font-weight:600; font-family:monospace; font-size:14px; }
  .sum-total { display:flex; justify-content:space-between; margin-top:12px; padding-top:12px; border-top:1px dashed #cbd5e1; font-weight:700; font-size:15px; }
</style>';
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
exit;
