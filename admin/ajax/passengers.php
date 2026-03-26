<?php
/**
 * AJAX Handler: getPassengers
 * Returns booking list detail for a specific trip schedule
 */

global $conn;

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

function route_parts(string $route): array
{
    if (strpos($route, ' - ') !== false) {
        $parts = explode(' - ', $route, 2);
        return [trim($parts[0]), trim($parts[1])];
    }
    if (strpos($route, '-') !== false) {
        $parts = explode('-', $route, 2);
        return [trim($parts[0]), trim($parts[1])];
    }
    return [$route, ''];
}

try {
    $assignedDriverName = '-';
    $assignedDriverId = 0;
    $stmtD = $conn->prepare("\n    SELECT ta.driver_id, d.nama\n    FROM trip_assignments ta\n    JOIN drivers d ON ta.driver_id = d.id\n    WHERE ta.rute=? AND ta.tanggal=? AND ta.jam=? AND ta.unit=?\n");
    if ($stmtD) {
        try {
            $stmtD->execute([$rute, $tanggal, $jam, $unit]);
            $resD = $stmtD->fetch();
            if ($resD) {
                $assignedDriverName = $resD['nama'];
                $assignedDriverId = intval($resD['driver_id']);
            }
        } catch (PDOException $e) {
            // Keep UI resilient.
        }
    }

    $allDrivers = [];
    $resAllD = $conn->query("SELECT id, nama FROM drivers ORDER BY nama ASC");
    while ($rd = $resAllD->fetch()) {
        $allDrivers[] = $rd;
    }

    $stmt = $conn->prepare("\n  SELECT b.id, b.name, b.phone, b.pickup_point, b.pembayaran, b.seat, b.price, b.discount, b.status, b.created_at, b.segment_id, c.address AS gmaps,\n         s.rute AS segment_route, s.origin AS segment_origin, s.destination AS segment_destination\n  FROM bookings b\n  LEFT JOIN customers c ON b.phone = c.phone\n  LEFT JOIN segments s ON b.segment_id = s.id\n  WHERE b.rute=? AND b.tanggal=? AND b.jam=? AND b.unit=? AND b.status!='canceled'\n  ORDER BY CAST(b.seat AS INTEGER), b.created_at ASC\n");
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

    $totalPaid = 0;
    $countPaid = 0;
    $totalUnpaid = 0;
    $countUnpaid = 0;

    foreach ($rows as $r) {
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
    }

    $tglIndo = date('d M Y', strtotime($tanggal));
    $jamIndo = substr($jam, 0, 5);
    $totalPax = count($rows);
    [$routeOrigin, $routeDestination] = route_parts($rute);

    ob_start();
    ?>
<div id="departureInfoCard" class="info-card view-trip-card admin-bs-panel" data-driver-name="<?php echo h($assignedDriverName); ?>">
  <div class="view-trip-head" style="display:none;" aria-hidden="true">
    <div>
      <div class="view-trip-kicker">Booking Detail</div>
      <div class="view-trip-title">Info Pemberangkatan</div>
    </div>
    <div class="view-trip-badge">Unit <?php echo intval($unit); ?></div>
  </div>

  <div class="view-trip-meta-grid" style="display:none;" aria-hidden="true">
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

<div class="view-booking-list-shell">
  <div class="view-booking-list-head">
    <div class="view-booking-list-copy">
      <div class="view-booking-kicker">Ringkasan Booking</div>
      <h4 class="view-booking-title">Data Booking</h4>
      <div class="view-booking-stats">
        <span class="view-booking-stat-chip">Total <?php echo intval($totalPax); ?> penumpang</span>
        <span class="view-booking-stat-chip success"><?php echo intval($countPaid); ?> lunas</span>
        <?php if ($countUnpaid > 0): ?>
          <span class="view-booking-stat-chip warning"><?php echo intval($countUnpaid); ?> belum lunas</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="view-booking-head-actions">
      <?php if ($countUnpaid > 0): ?>
      <button type="button" class="mark-all-paid-btn btn btn-modern booking-detail-toolbar-btn success"
        data-rute="<?php echo h($rute); ?>"
        data-tanggal="<?php echo h($tanggal); ?>"
        data-jam="<?php echo h($jam); ?>"
        data-unit="<?php echo intval($unit); ?>"
        title="Tandai semua penumpang belum lunas menjadi Lunas">
        <i class="fa-solid fa-circle-check fa-icon"></i>
        <span>Lunas Semua (<?php echo $countUnpaid; ?>)</span>
      </button>
      <?php endif; ?>
      <a href="index.php" class="view-booking-cta booking-detail-toolbar-btn">
        <i class="fa-solid fa-plus fa-icon"></i>
        Tambah Booking
      </a>
    </div>
  </div>

  <div class="booking-detail-grid">
    <?php if (empty($rows)): ?>
      <div class="admin-empty-state view-empty-state">Belum ada penumpang untuk jadwal ini.</div>
    <?php else: ?>
      <?php foreach ($rows as $idx => $p): ?>
        <?php
          $payStatus = trim((string) ($p['pembayaran'] ?? 'Belum Lunas')) ?: 'Belum Lunas';
          $isPaid = $payStatus === 'Lunas';
          $statusLabel = $isPaid ? 'LUNAS' : 'BELUM LUNAS';
          $statusTone = $isPaid ? 'ready' : 'warning';
          $lineTone = $isPaid ? 'emerald' : 'amber';
          if (in_array($payStatus, ['Redbus', 'Traveloka'], true)) {
              $statusLabel = 'ON-TRIP';
              $statusTone = 'ontrip';
              $lineTone = 'blue';
          }
          $pickupText = trim((string) ($p['pickup_point'] ?? ''));
          if ($pickupText === '') {
              $pickupText = $routeOrigin !== '' ? $routeOrigin : 'Pickup belum diisi';
          }
          $segmentOrigin = trim((string) ($p['segment_origin'] ?? ''));
          $segmentDestination = trim((string) ($p['segment_destination'] ?? ''));
          $segmentRoute = trim((string) ($p['segment_route'] ?? ''));
          if ($segmentOrigin !== '' && $segmentDestination !== '') {
              $segmentLabel = $segmentOrigin . ' - ' . $segmentDestination;
          } elseif ($segmentRoute !== '') {
              $segmentLabel = $segmentRoute;
          } else {
              $segmentLabel = '';
          }
          $bookingCode = formatBookingId($p['id'], $p['created_at'] ?? $tanggal . ' 00:00:00');
          $sourceLabel = in_array($payStatus, ['Redbus', 'Traveloka'], true) ? strtoupper($payStatus) : 'END USER';
          $priceValue = max(0, floatval($p['price'] ?? 0) - floatval($p['discount'] ?? 0));
        ?>
        <div
          class="seat-block view-seat-card booking-detail-card tone-<?php echo h($lineTone); ?>"
          data-seat="<?php echo h($p['seat'] ?? ''); ?>"
          data-name="<?php echo h(mb_strtoupper(trim((string) ($p['name'] ?? '')), 'UTF-8')); ?>"
          data-payment-rank="<?php echo $isPaid ? 2 : (in_array($payStatus, ['Redbus', 'Traveloka'], true) ? 3 : 1); ?>"
          data-payment-label="<?php echo h($payStatus); ?>"
          data-phone="<?php echo h($p['phone'] ?? ''); ?>"
          data-pickup="<?php echo h($pickupText); ?>">
          <div class="booking-detail-line"></div>
          <div class="booking-detail-main">
            <div class="booking-detail-copy">
              <div class="booking-detail-meta-row">
                <span class="booking-detail-code mono-font"><?php echo h($bookingCode); ?></span>
                <span class="booking-detail-status tone-<?php echo h($statusTone); ?>">
                  <span class="booking-detail-dot"></span>
                  <?php echo h($statusLabel); ?>
                </span>
              </div>
              <div class="seat-badge-num">Kursi <?php echo h($p['seat'] ?? '-'); ?></div>
              <h3 class="booking-detail-name sb-val name"><?php echo h($p['name'] ?? '-'); ?></h3>
              <div class="booking-detail-route">
                <i class="fa-solid fa-location-dot fa-icon"></i>
                <span class="font-medium"><?php echo h($pickupText); ?></span>
                <?php if ($routeDestination !== ''): ?>
                  <i class="booking-detail-arrow fa-solid fa-arrow-right fa-icon"></i>
                  <span class="font-medium"><?php echo h($routeDestination); ?></span>
                <?php else: ?>
                  <i class="booking-detail-arrow fa-solid fa-arrow-right fa-icon"></i>
                  <span class="font-medium"><?php echo h($rute); ?></span>
                <?php endif; ?>
              </div>
              <?php if ($segmentLabel !== ''): ?>
                <div class="booking-detail-segment">
                  <i class="fa-solid fa-shuffle fa-icon"></i>
                  <span>Segment: <strong><?php echo h($segmentLabel); ?></strong></span>
                </div>
              <?php endif; ?>
              <div class="booking-detail-hidden sb-val phone"><?php echo h($p['phone'] ?? '-'); ?></div>
              <div class="booking-detail-hidden sb-val pickup"><?php echo h($pickupText); ?></div>
              <div class="booking-detail-hidden sb-val gmaps"><?php echo h($p['gmaps'] ?? '-'); ?></div>
              <div class="booking-detail-hidden sb-val pay"><?php echo h($payStatus); ?></div>
            </div>

            <div class="booking-detail-side">
              <div class="booking-detail-side-block">
                <span class="booking-detail-side-label">Departure Time</span>
                <span class="booking-detail-side-value mono-font"><?php echo h($tglIndo . ' ' . $jamIndo); ?></span>
              </div>
              <div class="booking-detail-side-block">
                <span class="booking-detail-side-label">Pembayaran</span>
                <span class="booking-detail-side-value">Rp <?php echo number_format($priceValue, 0, ',', '.'); ?></span>
              </div>
              <div class="booking-detail-source"><?php echo h($sourceLabel); ?></div>
              <div class="seat-actions booking-detail-actions">
                <button type="button" class="btn-action-icon info booking-detail-toggle" title="Lihat detail">
                  <i class="fa-solid fa-chevron-down fa-icon"></i>
                </button>
                <button
                  type="button"
                  class="edit-booking-btn btn-action-icon edit"
                  data-id="<?php echo h($p['id']); ?>"
                  data-unit="<?php echo intval($unit); ?>"
                  data-rute="<?php echo h($rute); ?>"
                  data-tanggal="<?php echo h($tanggal); ?>"
                  data-jam="<?php echo h($jam); ?>"
                  data-seat="<?php echo h($p['seat'] ?? ''); ?>"
                  data-pickup="<?php echo h($p['pickup_point'] ?? ''); ?>"
                  data-segment-id="<?php echo h($p['segment_id'] ?? '0'); ?>"
                  data-price="<?php echo h($p['price'] ?? '0'); ?>"
                  data-discount="<?php echo h($p['discount'] ?? '0'); ?>"
                  data-pembayaran="<?php echo h($payStatus); ?>"
                  title="Edit Penumpang">
                  <i class="fa-solid fa-pen-to-square fa-icon"></i>
                </button>
                <button type="button" class="copy-single copy-btn" data-seat="<?php echo h($p['seat'] ?? ''); ?>" title="Copy Detail">
                  <i class="fa-solid fa-copy fa-icon"></i>
                </button>
                <?php if (!$isPaid): ?>
                  <button type="button" class="mark-paid-seat btn-action-icon pay" data-id="<?php echo h($p['id']); ?>" title="Mark Lunas">
                    <i class="fa-solid fa-circle-check fa-icon"></i>
                  </button>
                <?php endif; ?>
                <button type="button" class="cancel-btn btn-action-icon cancel" data-id="<?php echo h($p['id']); ?>" title="Batalkan">
                  <i class="fa-solid fa-xmark fa-icon"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="booking-detail-extra">
            <div class="booking-detail-extra-item">
              <span class="booking-detail-extra-label">No. HP</span>
              <strong class="booking-detail-extra-value"><?php echo h($p['phone'] ?? '-'); ?></strong>
            </div>
            <div class="booking-detail-extra-item">
              <span class="booking-detail-extra-label">Alamat / Titik Jemput</span>
              <strong class="booking-detail-extra-value"><?php echo h($pickupText); ?></strong>
            </div>
            <div class="booking-detail-extra-item">
              <span class="booking-detail-extra-label">Status Bayar</span>
              <strong class="booking-detail-extra-value"><?php echo h($payStatus); ?></strong>
            </div>
            <div class="booking-detail-extra-item">
              <span class="booking-detail-extra-label">Google Maps</span>
              <strong class="booking-detail-extra-value"><?php echo h($p['gmaps'] ?? '-'); ?></strong>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
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
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'error' => 'server_error', 'detail' => $e->getMessage()]);
    exit;
}
