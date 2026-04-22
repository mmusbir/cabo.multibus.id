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

// Load existing customers from customer_charter for autocomplete
$charterExistingCustomers = [];
try {
  $charterExistingCustomers = $conn->query("SELECT id, nama, no_hp, perusahaan FROM customer_charter ORDER BY nama")->fetchAll();
} catch (Throwable $e) {
  $charterExistingCustomers = [];
}

$charterCreateForm = array_merge([
  'name' => '',
  'phone' => '',
  'email' => '',
  'perusahaan' => '',
  'pickup_point' => '',
  'drop_point' => '',
  'start_date' => date('Y-m-d'),
  'end_date' => date('Y-m-d', strtotime('+2 days')),
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
<section id="charter-create" class="card" style="display:none; background:transparent !important; box-shadow:none !important; border:none !important; padding:0 !important;">
    <div class="admin-section-header mb-4">
      <div>
        <h3 class="admin-section-title"><i class="fa-solid fa-van-shuttle fa-icon" style="color:var(--neu-primary); margin-right:8px;"></i> <span id="charter_form_title">Tambah Carter Baru</span></h3>
        <p class="admin-section-subtitle" id="charter_form_subtitle">Buat reservasi carter unit bus baru</p>
      </div>
      <a href="#bookings" class="btn btn-outline-secondary btn-modern secondary" data-target="bookings" data-booking-mode="charters">
        <i class="fa-solid fa-arrow-left fa-icon"></i> Kembali ke Daftar
      </a>
    </div>

    <?php if (!empty($charterCreateErrors)): ?>
      <div class="alert alert-danger" style="border-radius:15px;">
        <ul class="mb-0">
          <?php foreach ($charterCreateErrors as $error): ?>
            <li><?php echo htmlspecialchars((string) $error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="modern-form-container" id="charterMainForm">
      <input type="hidden" name="action" id="charter_form_action" value="create_charter">
      <input type="hidden" name="id" id="charter_form_id" value="">

      <div class="row g-4">
        <!-- Panel Kiri: Informasi Penyewa & Rute -->
        <div class="col-lg-7">
          <div class="card p-4 h-100 luggage-card shadow-sm" style="border-radius: 20px;">
            <div class="d-flex align-items-center gap-3 mb-4">
               <div class="icon-box bg-primary-soft p-3 rounded-4" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                  <i class="fa-solid fa-user-tie fa-2x"></i>
               </div>
               <div>
                  <h5 class="mb-0 fw-bold">Identitas Penyewa</h5>
                  <span class="small text-muted">Data diri penyewa carter</span>
               </div>
            </div>

            <!-- Pilih Customer Tersimpan -->
            <?php if (!empty($charterExistingCustomers)): ?>
            <div class="mb-3">
              <label class="admin-bs-input-label"><i class="fa-solid fa-address-book me-1" style="color:var(--neu-primary);"></i>Pilih dari Customer Carter Tersimpan</label>
              <div class="input-group-modern">
                <span class="input-icon"><i class="fa-solid fa-search"></i></span>
                <select id="charter_customer_select" class="form-select modern-input ps-5">
                  <option value="">-- Ketik nama baru / pilih customer lama --</option>
                  <?php foreach ($charterExistingCustomers as $cust): ?>
                    <option value="<?= intval($cust['id']) ?>"
                      data-nama="<?= htmlspecialchars(strtoupper($cust['nama'])) ?>"
                      data-hp="<?= htmlspecialchars($cust['no_hp']) ?>"
                      data-perusahaan="<?= htmlspecialchars($cust['perusahaan'] ?? '') ?>">
                      <?= htmlspecialchars($cust['nama']) ?><?= !empty($cust['perusahaan']) ? ' (' . htmlspecialchars($cust['perusahaan']) . ')' : '' ?> — <?= htmlspecialchars($cust['no_hp']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mt-1 small text-muted">Memilih customer akan mengisi otomatis nama & nomor HP di bawah</div>
            </div>
            <hr style="border-color: var(--border-color); margin: 12px 0 20px;">
            <?php endif; ?>

            <!-- Identitas Penyewa -->
            <div class="customer-section mb-4 p-3 rounded-4" style="background: rgba(148, 163, 184, 0.05); border: 1px solid rgba(148, 163, 184, 0.1);">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="admin-bs-input-label">Nama Lengkap <span class="text-danger">*</span></label>
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                    <input type="text" id="charter_name_input" name="name" value="<?php echo htmlspecialchars($charterCreateForm['name']); ?>" class="form-control modern-input ps-5" placeholder="Contoh: Budi Santoso" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="admin-bs-input-label">Nomor Telepon <span class="text-danger">*</span></label>
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-phone"></i></span>
                    <input type="tel" id="charter_phone_input" name="phone" value="<?php echo htmlspecialchars($charterCreateForm['phone']); ?>" class="form-control modern-input ps-5" placeholder="+62 812-xxxx-xxxx" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="admin-bs-input-label">Email <small class="text-muted">(Opsional)</small></label>
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($charterCreateForm['email']); ?>" class="form-control modern-input ps-5" placeholder="budi@example.com">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="admin-bs-input-label">Perusahaan <small class="text-muted">(Opsional)</small></label>
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-building"></i></span>
                    <input type="text" id="charter_perusahaan_input" name="perusahaan" value="<?php echo htmlspecialchars($charterCreateForm['perusahaan']); ?>" class="form-control modern-input ps-5" placeholder="Nama perusahaan (opsional)">
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 mb-4 mt-4">
               <div class="icon-box bg-success-soft p-3 rounded-4" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                  <i class="fa-solid fa-route fa-2x"></i>
               </div>
               <div>
                  <h5 class="mb-0 fw-bold">Rute Perjalanan</h5>
                  <span class="small text-muted">Titik jemput dan destinasi</span>
               </div>
            </div>

            <!-- Rute Perjalanan -->
            <div class="customer-section mb-4 p-3 rounded-4" style="background: rgba(148, 163, 184, 0.05); border: 1px solid rgba(148, 163, 184, 0.1);">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="admin-bs-input-label">Lokasi Penjemputan (From)</label>
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                    <input type="text" id="charter_pickup_point" name="pickup_point" value="<?php echo htmlspecialchars($charterCreateForm['pickup_point']); ?>" class="form-control modern-input ps-5" placeholder="Contoh: Jakarta" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="admin-bs-input-label">Tujuan / Destinasi (To)</label>
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <input type="text" id="charter_drop_point" name="drop_point" value="<?php echo htmlspecialchars($charterCreateForm['drop_point']); ?>" class="form-control modern-input ps-5" placeholder="Contoh: Yogyakarta" required>
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <label class="admin-bs-input-label">Pilih dari Master Rute</label>
                <div class="d-flex flex-wrap gap-2">
                  <?php foreach ($charterCreateRoutes as $route): ?>
                    <?php
                    $routeLabel = trim(($route['origin'] ?? '') . ' - ' . ($route['destination'] ?? ''));
                    if ($routeLabel === ' - ')
                      $routeLabel = trim((string) ($route['name'] ?? ''));
                    ?>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary charter-route-preset rounded-pill"
                      data-pickup="<?php echo htmlspecialchars((string) ($route['origin'] ?? '')); ?>"
                      data-drop="<?php echo htmlspecialchars((string) ($route['destination'] ?? '')); ?>"
                      data-duration="<?php echo htmlspecialchars((string) ($route['duration'] ?? '3')); ?>"
                      data-price="<?php echo htmlspecialchars((string) intval($route['rental_price'] ?? 0)); ?>">
                      <?php echo htmlspecialchars($routeLabel); ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Panel Kanan: Detail Armada & Pembayaran -->
        <div class="col-lg-5">
          <div class="card p-4 h-100 luggage-card shadow-sm" style="border-radius: 20px; border-top: 4px solid var(--neu-primary);">
            
            <div class="d-flex align-items-center gap-3 mb-4">
               <div class="icon-box bg-warning-soft p-3 rounded-4" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                  <i class="fa-solid fa-bus fa-2x"></i>
               </div>
               <div>
                  <h5 class="mb-0 fw-bold">Detail Armada & Jadwal</h5>
                  <span class="small text-muted">Tanggal, armada, dan pembayaran</span>
               </div>
            </div>

            <!-- Tanggal Keberangkatan & Kepulangan -->
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="admin-bs-input-label"><i class="fa-solid fa-bus me-1" style="color:var(--primary-color);"></i>Tgl. Berangkat</label>
                <input type="date" id="charter_start_date" name="start_date" class="form-control modern-input" value="<?php echo htmlspecialchars($charterCreateForm['start_date']); ?>" required>
              </div>
              <div class="col-6">
                <label class="admin-bs-input-label"><i class="fa-solid fa-location-dot me-1" style="color:#ef4444;"></i>Tgl. Kepulangan</label>
                <input type="date" id="charter_end_date" name="end_date" class="form-control modern-input" value="<?php echo htmlspecialchars($charterCreateForm['end_date']); ?>" required>
              </div>
            </div>

            <!-- Durasi (auto-calculated) + Jam -->
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="admin-bs-input-label">Durasi (Hari)</label>
                <input type="number" id="charter_duration_days" name="duration_days" class="form-control modern-input" min="1" value="<?php echo htmlspecialchars($charterCreateForm['duration_days']); ?>" readonly style="background: rgba(148,163,184,0.07); cursor: default;">
              </div>
              <div class="col-6">
                <label class="admin-bs-input-label">Jam Berangkat</label>
                <input type="time" name="departure_time" class="form-control modern-input" value="<?php echo htmlspecialchars($charterCreateForm['departure_time']); ?>">
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12">
                <label class="admin-bs-input-label">Tipe Bus</label>
                <select name="bus_type" id="charter_bus_type" class="form-select modern-input">
                  <?php foreach (['Big Bus', 'Medium Bus', 'Mini Bus'] as $busType): ?>
                    <option value="<?php echo htmlspecialchars($busType); ?>" <?php echo $charterCreateForm['bus_type'] === $busType ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($busType); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="admin-bs-input-label">Unit Kendaraan</label>
              <select name="unit_id" class="form-select modern-input" required>
                <option value="">Pilih Unit</option>
                <?php foreach ($charterCreateUnits as $unit): ?>
                  <option value="<?php echo (int) $unit['id']; ?>" <?php echo (string) $charterCreateForm['unit_id'] === (string) $unit['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($unit['nopol'] ?? '-') . ' - ' . ($unit['merek'] ?? 'Unit')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="admin-bs-input-label">Driver <small class="text-muted">(Opsional)</small></label>
              <select name="driver_name" class="form-select modern-input">
                <option value="">Pilih Driver</option>
                <?php foreach ($charterCreateDrivers as $driver): ?>
                  <option value="<?php echo htmlspecialchars($driver['nama']); ?>" <?php echo $charterCreateForm['driver_name'] === $driver['nama'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($driver['nama']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Pricing Summary Card -->
            <div class="summary-card p-4 rounded-4 mb-3" style="background: var(--neu-primary); color: white; box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);">
               <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="small fw-600 opacity-75">TOTAL HARGA CARTER</span>
                  <i class="fa-solid fa-receipt opacity-50"></i>
               </div>
               <div class="d-flex align-items-baseline gap-2 mb-2">
                  <span class="h4 mb-0 fw-bold">Rp</span>
                  <span id="charterSummaryPriceLabel" class="display-6 fw-900 mb-0"><?php echo htmlspecialchars($charterCreateForm['price'] !== '' ? number_format($charterCreateForm['price'], 0, ',', '.') : '0'); ?></span>
               </div>
               <input type="text" id="charter_price_input" name="price" class="form-control text-end fw-bold" style="background:rgba(255,255,255,0.2); color:white; border:none;" value="<?php echo htmlspecialchars($charterCreateForm['price']); ?>" placeholder="12500000">
               <div class="mt-1 small opacity-75 text-end">Ubah nominal harga di kolom ini jika perlu</div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-12">
                <label class="admin-bs-input-label">Uang Muka (DP)</label>
                <div class="input-group-modern">
                  <span class="input-icon fw-bold ps-3" style="font-size:14px; top:12px;">Rp</span>
                  <input type="text" name="down_payment" class="form-control modern-input ps-5" value="<?php echo htmlspecialchars($charterCreateForm['down_payment']); ?>" placeholder="0">
                </div>
              </div>
            </div>

            <div class="mb-4">
               <label class="admin-bs-input-label">Status Pembayaran</label>
               <div class="d-flex gap-2">
                  <?php foreach (['Lunas' => 'success', 'DP' => 'warning', 'Belum Bayar' => 'secondary'] as $status => $color): ?>
                    <label class="flex-fill">
                      <input type="radio" name="payment_status" value="<?php echo htmlspecialchars($status); ?>" <?php echo $charterCreateForm['payment_status'] === $status ? 'checked' : ''; ?> class="btn-check">
                      <span class="btn btn-outline-<?php echo $color; ?> w-100 py-2 border-2 fw-bold" style="border-radius:12px; font-size:12px;"><?php echo htmlspecialchars(strtoupper($status)); ?></span>
                    </label>
                  <?php endforeach; ?>
               </div>
            </div>

            <button type="submit" name="create_charter_submit" id="charter_submit_btn" class="btn btn-primary btn-modern w-100 py-3 shadow-lg" style="font-size:18px; border-radius:15px; background: linear-gradient(135deg, #0d6efd 0%, #0052cc 100%);">
              <i class="fa-solid fa-check-circle me-2"></i> <span id="charter_submit_text">KONFIRMASI & SIMPAN</span>
            </button>
          </div>
        </div>
      </div>
    </form>

  <script>
    (function () {
      const pickupInput   = document.getElementById('charter_pickup_point');
      const dropInput     = document.getElementById('charter_drop_point');
      const startInput    = document.getElementById('charter_start_date');
      const endInput      = document.getElementById('charter_end_date');
      const durationInput = document.getElementById('charter_duration_days');
      const busSelect     = document.getElementById('charter_bus_type');
      const priceInput    = document.getElementById('charter_price_input');
      const priceLabel    = document.getElementById('charterSummaryPriceLabel');
      const custSelect    = document.getElementById('charter_customer_select');
      const nameInput     = document.getElementById('charter_name_input');
      const phoneInput    = document.getElementById('charter_phone_input');
      const perusInput    = document.getElementById('charter_perusahaan_input');

      function formatRupiah(amount) {
          try {
              let parsed = parseFloat(amount);
              if (isNaN(parsed)) return '0';
              return new Intl.NumberFormat('id-ID').format(parsed);
          } catch (e) { return amount; }
      }

      function syncSummary() {
        if (priceLabel && priceInput) {
            priceLabel.textContent = formatRupiah(priceInput.value);
        }
      }

      // Auto-calculate duration days from start and end date
      function calcDuration() {
        if (!startInput || !endInput || !durationInput) return;
        const s = new Date(startInput.value);
        const e = new Date(endInput.value);
        if (!isNaN(s) && !isNaN(e) && e >= s) {
          const diff = Math.round((e - s) / 86400000) + 1;
          durationInput.value = diff;
        }
      }

      // Ensure end date >= start date
      function onStartChange() {
        if (endInput && startInput.value && endInput.value < startInput.value) {
          endInput.value = startInput.value;
        }
        if (endInput) endInput.min = startInput.value;
        calcDuration();
      }

      if (startInput) {
        startInput.addEventListener('change', onStartChange);
        if (startInput.value) endInput && (endInput.min = startInput.value);
      }
      if (endInput) endInput.addEventListener('change', calcDuration);

      // Customer select auto-fill
      if (custSelect) {
        custSelect.addEventListener('change', function () {
          const opt = this.options[this.selectedIndex];
          if (this.value && opt) {
            if (nameInput)  nameInput.value  = opt.dataset.nama  || '';
            if (phoneInput) phoneInput.value = opt.dataset.hp    || '';
            if (perusInput) perusInput.value = opt.dataset.perusahaan || '';
          }
        });
      }

      // Route preset buttons
      document.querySelectorAll('.charter-route-preset').forEach(btn => {
        btn.addEventListener('click', function () {
          if (pickupInput) pickupInput.value = this.dataset.pickup || '';
          if (dropInput)   dropInput.value   = this.dataset.drop   || '';
          // Set duration and recompute end date
          if (durationInput && this.dataset.duration) {
            const dur = parseInt(this.dataset.duration, 10) || 1;
            if (startInput && startInput.value) {
              const s = new Date(startInput.value);
              s.setDate(s.getDate() + dur - 1);
              if (endInput) endInput.value = s.toISOString().split('T')[0];
            }
            durationInput.value = dur;
          }
          if (priceInput && this.dataset.price) {
            priceInput.value = this.dataset.price;
          }
          syncSummary();
        });
      });

      if (priceInput) {
        priceInput.addEventListener('input', syncSummary);
        priceInput.addEventListener('change', syncSummary);
      }

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

      // Initial sync
      calcDuration();
      syncSummary();

      // Handle Customer Selection
      const custSelect = document.getElementById('charter_customer_select');
      if (custSelect) {
        custSelect.addEventListener('change', function() {
          const opt = this.options[this.selectedIndex];
          if (!opt.value) return;
          if (document.getElementById('charter_name_input')) document.getElementById('charter_name_input').value = opt.dataset.nama || '';
          if (document.getElementById('charter_phone_input')) document.getElementById('charter_phone_input').value = opt.dataset.phone || '';
          if (document.getElementById('charter_perusahaan_input')) document.getElementById('charter_perusahaan_input').value = opt.dataset.perusahaan || '';
          syncSummary();
        });
      }

      // Handle Form Submission (AJAX)
      if (form) {
        form.onsubmit = async function (e) {
          e.preventDefault();
          const actionInput = document.getElementById('charter_form_action');
          const action = actionInput ? actionInput.value : 'create_charter';
          const formData = new FormData(this);
          
          try {
            const res = await fetch('admin.php?action=' + action, {
              method: 'POST',
              body: formData,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const js = await parseAdminApiResponse(res);
            if (js.success) {
              await customAlert(js.message || 'Data carter berhasil disimpan.', 'Sukses');
              if (typeof window.showSectionById === 'function') {
                window.showSectionById('bookings');
                window.location.hash = '#bookings';
                if (window.bookingDashboardState) window.bookingDashboardState.active = 'charters';
                if (typeof ajaxListLoad === 'function') {
                    ajaxListLoad('charters', { page: 1 });
                }
              } else {
                window.location.href = 'admin.php?booking_mode=charters#bookings';
                location.reload();
              }
            } else {
              customAlert('Gagal menyimpan: ' + (js.error || 'Terjadi kesalahan internal.'));
            }
          } catch (err) {
            console.error(err);
            customAlert('Kesalahan koneksi saat menyimpan data carter.');
          }
        };
      }

      window.resetCharterForm = function() {
        if (!form) return;
        
        // Reset basic fields
        form.reset();
        
        // Explicitly clear hidden fields
        const idField = document.getElementById('charter_form_id');
        if (idField) idField.value = '';
        const actionField = document.getElementById('charter_form_action');
        if (actionField) actionField.value = 'create_charter';
        
        // Reset Labels
        const title = document.getElementById('charter_form_title');
        if (title) title.textContent = 'Tambah Carter Baru';
        const subtitle = document.getElementById('charter_form_subtitle');
        if (subtitle) subtitle.textContent = 'Buat reservasi carter unit bus baru';
        const submitText = document.getElementById('charter_submit_text');
        if (submitText) submitText.textContent = 'KONFIRMASI & SIMPAN';
        
        // Reset pricing summary
        syncSummary();
        
        // Reset dates to default (today / +2 days)
        if (startInput) startInput.value = new Date().toISOString().split('T')[0];
        if (endInput) {
            const d = new Date();
            d.setDate(d.getDate() + 2);
            endInput.value = d.toISOString().split('T')[0];
        }
        calcDuration();
      };
    })();
  </script>
</section>
