<?php
$charterCreateErrors = $_SESSION['charter_create_errors'] ?? [];
$charterCreateOld = $_SESSION['charter_create_old'] ?? [];
unset($_SESSION['charter_create_errors'], $_SESSION['charter_create_old']);

$charterCreateUnits = [];
try {
  $charterCreateUnits = $conn->query("SELECT id, nopol, merek, kapasitas FROM units ORDER BY nopol")->fetchAll();
} catch (Throwable $e) {
  $charterCreateUnits = [];
}

$charterCreateDrivers = [];
try {
  $charterCreateDrivers = $conn->query("SELECT nama FROM drivers ORDER BY nama")->fetchAll();
} catch (Throwable $e) {
  $charterCreateDrivers = [];
}

$charterCreateRoutes = [];
try {
  $charterCreateRoutes = $conn->query("SELECT name, origin, destination, duration, rental_price FROM master_carter ORDER BY name")->fetchAll();
} catch (Throwable $e) {
  $charterCreateRoutes = [];
}

$charterCreateForm = array_merge([
  'name' => '',
  'phone' => '',
  'email' => '',
  'route_text' => '',
  'start_date' => date('Y-m-d'),
  'duration_days' => '3',
  'departure_time' => '08:30',
  'bus_type' => 'Big Bus',
  'unit_id' => (string) ($charterCreateUnits[0]['id'] ?? ''),
  'driver_name' => '',
  'price' => '',
  'down_payment' => '',
  'payment_status' => 'DP',
], is_array($charterCreateOld) ? $charterCreateOld : []);
?>
<section id="charter-create" class="card charter-create-page" style="display:none;">
  <div class="charter-create-shell">
    <div class="charter-create-head">
      <div>
        <h3 class="charter-create-title">Tambah Carter</h3>
      </div>
      <a href="#bookings" class="charter-create-back" data-target="bookings" data-booking-mode="charters">
        <i class="fa-solid fa-arrow-left fa-icon"></i>
        Kembali
      </a>
    </div>

    <?php if (!empty($charterCreateErrors)): ?>
      <div class="charter-create-error">
        <ul>
          <?php foreach ($charterCreateErrors as $error): ?>
            <li><?php echo htmlspecialchars((string) $error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="charter-create-layout" novalidate>
      <div class="charter-create-main">
        <div class="charter-create-panel">
          <div class="charter-create-section-title">Identitas Penyewa</div>
          <div class="charter-create-grid charter-create-grid-2">
            <label class="charter-create-field">
              <span>Nama Lengkap</span>
              <input type="text" name="name" value="<?php echo htmlspecialchars($charterCreateForm['name']); ?>" placeholder="Contoh: Budi Santoso" required>
            </label>
            <label class="charter-create-field">
              <span>Nomor Telepon</span>
              <input type="tel" name="phone" value="<?php echo htmlspecialchars($charterCreateForm['phone']); ?>" placeholder="+62 812-xxxx-xxxx" required>
            </label>
            <label class="charter-create-field charter-create-field-wide">
              <span>Email</span>
              <input type="email" name="email" value="<?php echo htmlspecialchars($charterCreateForm['email']); ?>" placeholder="budi@example.com">
            </label>
          </div>
        </div>

        <div class="charter-create-panel">
          <div class="charter-create-section-title">Detail Carter</div>
          <div class="charter-create-grid charter-create-grid-2">
            <label class="charter-create-field charter-create-field-wide">
              <span>Rute Perjalanan</span>
              <input type="text" id="charter_route_text" name="route_text" value="<?php echo htmlspecialchars($charterCreateForm['route_text']); ?>" placeholder="Jakarta - Yogyakarta (PP)" required>
            </label>
            <div class="charter-create-presets">
              <?php foreach ($charterCreateRoutes as $route): ?>
                <?php
                $routeLabel = trim(($route['origin'] ?? '') . ' - ' . ($route['destination'] ?? ''));
                if ($routeLabel === ' - ')
                  $routeLabel = trim((string) ($route['name'] ?? ''));
                ?>
                <button
                  type="button"
                  class="charter-route-preset"
                  data-route="<?php echo htmlspecialchars($routeLabel); ?>"
                  data-duration="<?php echo htmlspecialchars((string) ($route['duration'] ?? '3')); ?>"
                  data-price="<?php echo htmlspecialchars((string) intval($route['rental_price'] ?? 0)); ?>">
                  <?php echo htmlspecialchars($routeLabel); ?>
                </button>
              <?php endforeach; ?>
            </div>
            <label class="charter-create-field">
              <span>Tanggal Keberangkatan</span>
              <input type="date" name="start_date" value="<?php echo htmlspecialchars($charterCreateForm['start_date']); ?>" required>
            </label>
            <label class="charter-create-field">
              <span>Durasi (Hari)</span>
              <input type="number" id="charter_duration_days" name="duration_days" min="1" value="<?php echo htmlspecialchars($charterCreateForm['duration_days']); ?>">
            </label>
            <label class="charter-create-field">
              <span>Jam Berangkat</span>
              <input type="time" name="departure_time" value="<?php echo htmlspecialchars($charterCreateForm['departure_time']); ?>">
            </label>
            <label class="charter-create-field">
              <span>Tipe Bus</span>
              <select name="bus_type" id="charter_bus_type">
                <?php foreach (['Big Bus', 'Medium Bus', 'Mini Bus'] as $busType): ?>
                  <option value="<?php echo htmlspecialchars($busType); ?>" <?php echo $charterCreateForm['bus_type'] === $busType ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($busType); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="charter-create-field">
              <span>Unit</span>
              <select name="unit_id" required>
                <option value="">Pilih Unit</option>
                <?php foreach ($charterCreateUnits as $unit): ?>
                  <option value="<?php echo (int) $unit['id']; ?>" <?php echo (string) $charterCreateForm['unit_id'] === (string) $unit['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($unit['nopol'] ?? '-') . ' - ' . ($unit['merek'] ?? 'Unit')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="charter-create-field">
              <span>Driver</span>
              <select name="driver_name">
                <option value="">Pilih Driver</option>
                <?php foreach ($charterCreateDrivers as $driver): ?>
                  <option value="<?php echo htmlspecialchars($driver['nama']); ?>" <?php echo $charterCreateForm['driver_name'] === $driver['nama'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($driver['nama']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
        </div>

        <div class="charter-create-panel">
          <div class="charter-create-section-title">Pembayaran</div>
          <div class="charter-create-grid charter-create-grid-2">
            <label class="charter-create-field">
              <span>Total Harga</span>
              <input type="text" id="charter_price_input" name="price" value="<?php echo htmlspecialchars($charterCreateForm['price']); ?>" placeholder="12500000">
            </label>
            <label class="charter-create-field">
              <span>Uang Muka (DP)</span>
              <input type="text" name="down_payment" value="<?php echo htmlspecialchars($charterCreateForm['down_payment']); ?>" placeholder="0">
            </label>
            <div class="charter-create-field charter-create-field-wide">
              <span>Status Pembayaran</span>
              <div class="charter-create-radio-group">
                <?php foreach (['Lunas', 'DP', 'Belum Bayar'] as $status): ?>
                  <label class="charter-create-radio">
                    <input type="radio" name="payment_status" value="<?php echo htmlspecialchars($status); ?>" <?php echo $charterCreateForm['payment_status'] === $status ? 'checked' : ''; ?>>
                    <span><?php echo htmlspecialchars($status); ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="charter-create-actions">
          <button type="submit" name="create_charter_submit" class="charter-create-save">
            <i class="fa-solid fa-floppy-disk fa-icon"></i>
            Konfirmasi & Simpan
          </button>
          <a href="#bookings" class="charter-create-cancel" data-target="bookings" data-booking-mode="charters">Batalkan</a>
        </div>
      </div>

      <aside class="charter-create-summary">
        <div class="charter-create-summary-title">Ringkasan</div>
        <div class="charter-create-summary-list">
          <div class="charter-create-summary-row">
            <span>Rute</span>
            <strong id="charterSummaryRoute"><?php echo htmlspecialchars($charterCreateForm['route_text'] ?: '-'); ?></strong>
          </div>
          <div class="charter-create-summary-row">
            <span>Tanggal</span>
            <strong id="charterSummaryDate"><?php echo htmlspecialchars($charterCreateForm['start_date']); ?></strong>
          </div>
          <div class="charter-create-summary-row">
            <span>Durasi</span>
            <strong id="charterSummaryDuration"><?php echo htmlspecialchars($charterCreateForm['duration_days']); ?> Hari</strong>
          </div>
          <div class="charter-create-summary-row">
            <span>Bus</span>
            <strong id="charterSummaryBus"><?php echo htmlspecialchars($charterCreateForm['bus_type']); ?></strong>
          </div>
          <div class="charter-create-summary-row">
            <span>Harga</span>
            <strong id="charterSummaryPrice"><?php echo htmlspecialchars($charterCreateForm['price'] !== '' ? $charterCreateForm['price'] : '0'); ?></strong>
          </div>
        </div>
      </aside>
    </form>
  </div>

  <script>
    (function () {
      const routeInput = document.getElementById('charter_route_text');
      const durationInput = document.getElementById('charter_duration_days');
      const busSelect = document.getElementById('charter_bus_type');
      const priceInput = document.getElementById('charter_price_input');
      const dateInput = document.querySelector('#charter-create input[name="start_date"]');
      const summaryRoute = document.getElementById('charterSummaryRoute');
      const summaryDate = document.getElementById('charterSummaryDate');
      const summaryDuration = document.getElementById('charterSummaryDuration');
      const summaryBus = document.getElementById('charterSummaryBus');
      const summaryPrice = document.getElementById('charterSummaryPrice');

      function syncSummary() {
        if (summaryRoute) summaryRoute.textContent = routeInput?.value || '-';
        if (summaryDate) summaryDate.textContent = dateInput?.value || '-';
        if (summaryDuration) summaryDuration.textContent = (durationInput?.value || '0') + ' Hari';
        if (summaryBus) summaryBus.textContent = busSelect?.value || '-';
        if (summaryPrice) summaryPrice.textContent = priceInput?.value || '0';
      }

      document.querySelectorAll('.charter-route-preset').forEach(btn => {
        btn.addEventListener('click', function () {
          if (routeInput) routeInput.value = this.dataset.route || '';
          if (durationInput && this.dataset.duration) durationInput.value = this.dataset.duration;
          if (priceInput && this.dataset.price) priceInput.value = this.dataset.price;
          syncSummary();
        });
      });

      [routeInput, durationInput, busSelect, priceInput, dateInput].forEach(el => {
        if (el) el.addEventListener('input', syncSummary);
        if (el) el.addEventListener('change', syncSummary);
      });

      document.querySelectorAll('#charter-create [data-target="bookings"]').forEach(link => {
        link.addEventListener('click', function () {
          if (window.bookingDashboardState) {
            window.bookingDashboardState.active = 'charters';
          }
        });
      });

      if (window.location.hash === '#charter-create' && window.bookingDashboardState) {
        window.bookingDashboardState.active = 'charters';
      }

      syncSummary();
    })();
  </script>
</section>
