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

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
        // Handle silently or log if needed.
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
for ($i = 1; $i <= 8; $i++) {
    $seats[(string) $i] = null;
}

$totalPaid = 0;
$countPaid = 0;
$totalUnpaid = 0;
$countUnpaid = 0;

foreach ($rows as $r) {
    $seat = trim((string) $r['seat']);
    $priceValue = floatval($r['price'] ?? 0);
    $discountValue = floatval($r['discount'] ?? 0);
    $finalValue = max(0, $priceValue - $discountValue);

    if (($r['pembayaran'] ?? '') === 'Lunas') {
        $totalPaid += $finalValue;
        $countPaid++;
    } else {
        $totalUnpaid += $finalValue;
        $countUnpaid++;
    }

    if ($seat === '') {
        continue;
    }

    if (is_numeric($seat) && intval($seat) >= 1 && intval($seat) <= 8) {
        $seats[(string) intval($seat)] = $r;
    } else {
        $seats[$seat] = $r;
    }
}

$tglIndo = date('d M Y', strtotime($tanggal));
$jamIndo = substr($jam, 0, 5);
$totalPax = count($rows);

ob_start();
?>
<div id="departureInfoCard" class="info-card view-trip-card admin-bs-panel" data-driver-name="<?php echo h($assignedDriverName); ?>">
  <div class="view-trip-head">
    <div>
      <div class="view-trip-kicker">Manifest</div>
      <div class="view-trip-title">Info Pemberangkatan</div>
    </div>
    <div class="view-trip-badge">Unit <?php echo intval($unit); ?></div>
  </div>

  <div class="view-trip-meta-grid">
    <div class="view-trip-meta-item">
      <div class="view-trip-meta-label">Rute</div>
      <div class="view-trip-meta-value"><?php echo h($rute); ?></div>
    </div>
    <div class="view-trip-meta-item">
      <div class="view-trip-meta-label">Waktu</div>
      <div class="view-trip-meta-value"><?php echo h($tglIndo . ' - ' . $jamIndo); ?></div>
    </div>
    <div class="view-trip-meta-item">
      <div class="view-trip-meta-label">Unit</div>
      <div class="view-trip-meta-value">Unit <?php echo intval($unit); ?></div>
    </div>
    <div class="view-trip-meta-item">
      <div class="view-trip-meta-label">Penumpang</div>
      <div class="view-trip-meta-value"><?php echo intval($totalPax); ?> Orang</div>
    </div>
  </div>

  <div class="view-driver-row">
    <div class="view-driver-label">Driver</div>

    <div id="driverDisplay" class="view-driver-display">
      <strong id="driverNameText"><?php echo h($assignedDriverName); ?></strong>
      <button type="button" class="btn btn-modern secondary view-driver-toggle"
        onclick="document.getElementById('driverDisplay').style.display='none'; document.getElementById('driverEdit').style.display='flex';"
        title="Edit Driver">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 20h9"></path>
          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
        </svg>
        <span>Edit</span>
      </button>
    </div>

    <div id="driverEdit" class="view-driver-edit">
      <select id="driverSelect" class="form-select modern-select view-driver-select">
        <option value="0">Pilih Driver</option>
        <?php foreach ($allDrivers as $drv): ?>
          <option value="<?php echo intval($drv['id']); ?>" <?php echo ($drv['id'] == $assignedDriverId) ? 'selected' : ''; ?>>
            <?php echo h($drv['nama']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-modern view-driver-save"
        data-rute="<?php echo h($rute); ?>"
        data-tanggal="<?php echo h($tanggal); ?>"
        data-jam="<?php echo h($jam); ?>"
        data-unit="<?php echo intval($unit); ?>"
        onclick="saveDriverAssignment(this.dataset.rute, this.dataset.tanggal, this.dataset.jam, this.dataset.unit)">
        Simpan
      </button>
      <button type="button" class="btn btn-modern secondary view-driver-cancel"
        onclick="document.getElementById('driverEdit').style.display='none'; document.getElementById('driverDisplay').style.display='flex';">
        Batal
      </button>
    </div>
  </div>
</div>

<div class="passenger-seat-grid">
  <?php foreach ($seats as $num => $p): ?>
    <div class="seat-block view-seat-card" data-seat="<?php echo h($num); ?>">
      <div class="seat-head">
        <div class="seat-title-group">
          <div class="seat-badge-num">Kursi <?php echo h($num); ?></div>
          <?php if ($p !== null): ?>
            <?php $payStatus = $p['pembayaran'] ?? 'Belum Lunas'; ?>
            <?php $payClass = ($payStatus === 'Lunas') ? 'paid' : 'unpaid'; ?>
            <div class="seat-pay-badge <?php echo $payClass; ?>"><?php echo h($payStatus); ?></div>
          <?php endif; ?>
        </div>

        <div class="seat-actions">
          <button type="button" class="copy-single copy-btn" data-seat="<?php echo h($num); ?>" title="Copy Detail">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
          </button>
          <?php if ($p !== null && ($p['pembayaran'] ?? '') !== 'Lunas'): ?>
            <button type="button" class="mark-paid-seat btn-action-icon pay" data-id="<?php echo h($p['id']); ?>" title="Mark Lunas">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="m9 12 2 2 4-4"></path>
              </svg>
            </button>
          <?php endif; ?>
          <?php if ($p !== null): ?>
            <button type="button" class="cancel-btn btn-action-icon cancel" data-id="<?php echo h($p['id']); ?>" title="Batalkan">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="m15 9-6 6"></path>
                <path d="m9 9 6 6"></path>
              </svg>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($p !== null): ?>
        <div class="seat-body">
          <div class="sb-row">
            <div class="sb-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div class="sb-content">
              <div class="sb-label">Nama</div>
              <div class="sb-val name"><?php echo h($p['name'] ?? '-'); ?></div>
            </div>
          </div>

          <div class="sb-row">
            <div class="sb-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.63 2.62a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6.09 6.09l1.46-1.29a2 2 0 0 1 2.11-.45c.84.3 1.72.51 2.62.63A2 2 0 0 1 22 16.92z"></path>
              </svg>
            </div>
            <div class="sb-content">
              <div class="sb-label">No. HP</div>
              <div class="sb-val phone"><?php echo h($p['phone'] ?? '-'); ?></div>
            </div>
          </div>

          <?php if (!empty($p['pickup_point'])): ?>
            <div class="sb-row">
              <div class="sb-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
              </div>
              <div class="sb-content">
                <div class="sb-label">Titik Jemput</div>
                <div class="sb-val pickup"><?php echo h($p['pickup_point']); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($p['gmaps'])): ?>
            <div class="sb-row">
              <div class="sb-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                  <line x1="9" x2="9" y1="3" y2="18"></line>
                  <line x1="15" x2="15" y1="6" y2="21"></line>
                </svg>
              </div>
              <div class="sb-content">
                <div class="sb-label">Google Maps</div>
                <div class="sb-val gmaps"><?php echo h($p['gmaps']); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <span class="sb-val pay admin-hidden-value"><?php echo h($p['pembayaran'] ?? ''); ?></span>
        </div>
      <?php else: ?>
        <div class="seat-body empty-state">
          <div class="view-seat-empty">
            <div class="view-seat-empty-title">Kursi kosong</div>
            <div class="view-seat-empty-text">Belum ada penumpang pada kursi ini.</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div id="passengerSummary" class="summary-footer admin-bs-panel" data-paid="<?php echo $totalPaid; ?>" data-unpaid="<?php echo $totalUnpaid; ?>">
  <div class="summary-grid">
    <div class="sum-row paid">
      <div class="sum-label"><span class="indicator"></span>Sudah Lunas (<?php echo $countPaid; ?>)</div>
      <div class="sum-val">Rp <?php echo number_format($totalPaid, 0, ',', '.'); ?></div>
    </div>
    <div class="sum-row unpaid">
      <div class="sum-label"><span class="indicator"></span>Belum Lunas (<?php echo $countUnpaid; ?>)</div>
      <div class="sum-val">Rp <?php echo number_format($totalUnpaid, 0, ',', '.'); ?></div>
    </div>
    <div class="sum-total">
      <div class="sum-label">Total Estimasi</div>
      <div class="sum-val">Rp <?php echo number_format($totalPaid + $totalUnpaid, 0, ',', '.'); ?></div>
    </div>
  </div>
</div>
<?php
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);
exit;
