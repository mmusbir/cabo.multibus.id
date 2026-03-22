<!-- CANCELLATIONS -->
<section id="cancellations" class="card">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Cancellation Log</h3>
      <p class="admin-section-subtitle">Riwayat pembatalan booking beserta admin dan alasan yang dicatat.</p>
    </div>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_cancellations_input" class="search-input-modern"
        placeholder="Cari nama atau no. HP...">
      <button type="button" id="searchCancellationsBtn" class="search-btn-icon" aria-label="Cari pembatalan">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="cancellations_info">Memuat cancellations...</div>
    <div class="d-flex gap-2 align-items-center">
      <label class="small" for="cancellations_per_page">Per page</label>
      <select id="cancellations_per_page" class="form-select form-select-sm">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
  </div>

  <div id="cancellations_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <!-- CARD GRID -->
  <div id="cancellations_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid">
    <div class="small admin-grid-message">Loading...</div>
  </div>

  <div class="table-wrapper" style="display:none">
    <!-- Hidden legacy table wrapper for backwards compatibility -->
  </div>
  <div id="cancellations_pagination" class="admin-pagination-host"></div>
</section>
