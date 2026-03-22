<section id="booking-detail" class="card booking-detail-page">
  <div class="booking-detail-page-shell">
    <div class="booking-detail-page-head">
      <div class="booking-detail-page-copy">
        <span class="booking-detail-page-kicker">Logistics Overview</span>
        <h3 class="booking-detail-page-title">Data Booking</h3>
        <p class="booking-detail-page-subtitle">Buka detail booking per jadwal, lihat semua penumpang, salin detail booking, lalu kelola pembayaran dan driver dari satu halaman.</p>
      </div>

      <div class="booking-detail-page-actions">
        <a href="index.php" class="booking-detail-page-cta">
          <span class="material-symbols-outlined">add</span>
          <span>Tambah Booking</span>
        </a>
      </div>
    </div>

    <div class="booking-detail-page-chips no-scrollbar">
      <button type="button" class="booking-detail-static-chip active">Semua Jadwal</button>
      <button type="button" class="booking-detail-static-chip">Pending</button>
      <button type="button" class="booking-detail-static-chip">Confirmed</button>
      <button type="button" class="booking-detail-static-chip">Selesai</button>
      <button type="button" class="booking-detail-static-chip">End User</button>
    </div>

    <div class="booking-detail-page-panel admin-bs-panel">
      <div class="booking-detail-filter-grid">
        <div class="booking-detail-filter-field">
          <label for="booking_detail_rute" class="booking-detail-filter-label">Rute</label>
          <div class="input-group">
            <span class="input-group-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 6v6"></path>
                <path d="M15 6v6"></path>
                <path d="M2 12h19.6"></path>
                <path d="M18 18h3s.5-1.5.5-3-1-7-1-7c0-1-1-2-2.5-2h-13C3.5 6 2.5 7 2.5 8c0 0-1 5-1 7s.5 3 .5 3h3"></path>
                <circle cx="7" cy="18" r="2"></circle>
                <circle cx="17" cy="18" r="2"></circle>
              </svg>
            </span>
            <select id="booking_detail_rute" class="modern-select form-select" required>
              <option value="">Pilih Rute</option>
              <?php foreach ($routes as $r): ?>
                <option value="<?php echo htmlspecialchars($r['name']); ?>">
                  <?php echo htmlspecialchars($r['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="booking-detail-filter-field">
          <label for="booking_detail_tanggal" class="booking-detail-filter-label">Tanggal</label>
          <div class="input-group">
            <span class="input-group-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v4"></path>
                <path d="M16 2v4"></path>
                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                <path d="M3 10h18"></path>
              </svg>
            </span>
            <input type="date" id="booking_detail_tanggal" class="modern-input form-control" value="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>

        <div class="booking-detail-filter-field">
          <label for="booking_detail_jam" class="booking-detail-filter-label">Jam</label>
          <div class="input-group">
            <span class="input-group-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </span>
            <select id="booking_detail_jam" class="modern-select form-select">
              <option value="">Pilih Jam</option>
            </select>
          </div>
        </div>

        <div class="booking-detail-filter-field">
          <label for="booking_detail_unit" class="booking-detail-filter-label">Unit</label>
          <div class="input-group">
            <span class="input-group-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="8" width="18" height="8" rx="2"></rect>
                <path d="M6 16v2"></path>
                <path d="M18 16v2"></path>
                <path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"></path>
              </svg>
            </span>
            <select id="booking_detail_unit" class="modern-select form-select">
              <option value="1">Unit 1</option>
            </select>
          </div>
        </div>
      </div>

      <div class="booking-detail-filter-actions">
        <button type="button" id="btnLoadPassengers" class="booking-detail-filter-btn primary">
          <span class="material-symbols-outlined">travel_explore</span>
          <span>Lihat Booking</span>
        </button>
        <button type="button" id="copyAllBtn" class="booking-detail-filter-btn secondary">
          <span class="material-symbols-outlined">content_copy</span>
          <span>Copy Semua</span>
        </button>
      </div>
    </div>

    <div id="passenger_spinner_wrap" class="spinner-wrap" style="display:none;">
      <div class="ajax-spinner"></div>
    </div>

    <div id="passengerList" class="view-passenger-list booking-detail-page-list">
      <div class="admin-empty-state view-empty-state">
        Pilih rute, tanggal, jam, dan unit lalu klik Lihat Booking untuk menampilkan semua penumpang pada jadwal tersebut.
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.booking-detail-static-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        document.querySelectorAll('.booking-detail-static-chip').forEach(function (item) {
          item.classList.remove('active');
        });
        chip.classList.add('active');
      });
    });
  });
</script>

