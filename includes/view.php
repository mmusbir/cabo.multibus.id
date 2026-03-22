<section id="view" class="card">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">View Detail Penumpang</h3>
      <p class="admin-section-subtitle">Cek manifest keberangkatan, salin detail penumpang, dan kelola driver dalam satu panel.</p>
    </div>
    <span class="admin-bs-chip">Trip Monitor</span>
  </div>

  <div class="admin-bs-panel view-filter-panel p-3 p-lg-4">
    <div class="admin-bs-form-grid view-filter-grid">
      <div class="admin-bs-col-6">
        <label for="view_rute" class="admin-bs-input-label">Rute</label>
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
          <select id="view_rute" class="modern-select form-select" required>
            <option value="">Pilih Rute</option>
            <?php foreach ($routes as $r): ?>
              <option value="<?php echo htmlspecialchars($r['name']); ?>">
                <?php echo htmlspecialchars($r['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="admin-bs-col-6">
        <label for="view_tanggal" class="admin-bs-input-label">Tanggal</label>
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
          <input type="date" id="view_tanggal" class="modern-input form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>

      <div class="admin-bs-col-6">
        <label for="view_jam" class="admin-bs-input-label">Jam</label>
        <div class="input-group">
          <span class="input-group-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </span>
          <select id="view_jam" class="modern-select form-select">
            <option value="">Pilih Jam</option>
          </select>
        </div>
      </div>

      <div class="admin-bs-col-6">
        <label for="view_unit" class="admin-bs-input-label">Unit</label>
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
          <select id="view_unit" class="modern-select form-select">
            <option value="1">Unit 1</option>
          </select>
        </div>
      </div>

      <div class="admin-bs-col-12">
        <div class="admin-bs-actions view-filter-actions">
          <button type="button" id="btnLoadPassengers" class="btn btn-modern view-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.3-4.3"></path>
            </svg>
            <span>Lihat Data</span>
          </button>
          <button type="button" id="copyAllBtn" class="btn btn-modern secondary view-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            <span>Copy Semua</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="passenger_spinner_wrap" class="spinner-wrap" style="display:none;">
    <div class="ajax-spinner"></div>
  </div>

  <div id="passengerList" class="view-passenger-list">
    <div class="admin-empty-state view-empty-state">
      Pilih rute, tanggal, jam, dan unit lalu klik Lihat Data untuk menampilkan manifest.
    </div>
  </div>
</section>
