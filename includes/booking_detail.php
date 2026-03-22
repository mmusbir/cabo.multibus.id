<section id="booking-detail" class="card booking-detail-page">
  <div class="booking-detail-page-shell">
    <div class="booking-detail-page-head">
      <div class="booking-detail-page-copy">
        <span class="booking-detail-page-kicker">Logistics Overview</span>
        <h3 class="booking-detail-page-title">Detail Booking</h3>
        <p class="booking-detail-page-subtitle">Semua penumpang pada jadwal terpilih tampil di satu halaman, lengkap dengan status pembayaran, driver, dan aksi cepat.</p>
      </div>

      <div class="booking-detail-page-actions">
        <button type="button" id="copyAllBtn" class="booking-detail-page-ghost">
          <span class="material-symbols-outlined">content_copy</span>
          <span>Copy Semua</span>
        </button>
        <a href="index.php" class="booking-detail-page-cta">
          <span class="material-symbols-outlined">add</span>
          <span>Tambah Booking</span>
        </a>
      </div>
    </div>

    <div class="booking-detail-context admin-bs-panel">
      <div class="booking-detail-context-head">
        <div>
          <div class="booking-detail-context-kicker">Jadwal Terpilih</div>
          <div class="booking-detail-context-title">Info Keberangkatan</div>
        </div>
        <div class="booking-detail-context-badge" id="booking_detail_unit_text_badge">Unit -</div>
      </div>

      <div class="booking-detail-context-grid">
        <div class="booking-detail-context-item">
          <span class="booking-detail-context-label">Rute</span>
          <span class="booking-detail-context-value" id="booking_detail_route_text">Belum dipilih</span>
        </div>
        <div class="booking-detail-context-item">
          <span class="booking-detail-context-label">Tanggal</span>
          <span class="booking-detail-context-value" id="booking_detail_date_text">-</span>
        </div>
        <div class="booking-detail-context-item">
          <span class="booking-detail-context-label">Jam</span>
          <span class="booking-detail-context-value" id="booking_detail_time_text">-</span>
        </div>
        <div class="booking-detail-context-item">
          <span class="booking-detail-context-label">Unit</span>
          <span class="booking-detail-context-value" id="booking_detail_unit_text">-</span>
        </div>
      </div>

      <p class="booking-detail-context-helper" id="booking_detail_helper_text">
        Pilih aksi <strong>Detail Booking List</strong> dari halaman Booking untuk menampilkan semua penumpang pada jadwal tersebut.
      </p>

      <input type="hidden" id="booking_detail_rute" value="">
      <input type="hidden" id="booking_detail_tanggal" value="">
      <input type="hidden" id="booking_detail_jam" value="">
      <input type="hidden" id="booking_detail_unit" value="">
    </div>

    <div id="passenger_spinner_wrap" class="spinner-wrap" style="display:none;">
      <div class="ajax-spinner"></div>
    </div>

    <div id="passengerList" class="view-passenger-list booking-detail-page-list">
      <div class="admin-empty-state view-empty-state">
        Belum ada jadwal yang dipilih. Buka menu Booking lalu tekan <strong>Detail Booking List</strong> pada trip yang ingin dilihat.
      </div>
    </div>
  </div>
</section>
