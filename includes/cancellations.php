<!-- CANCELLATIONS -->
<section id="cancellations" class="card">
  <h3>Cancellation Log</h3>
  <div class="muted">Riwayat pembatalan booking.</div>
  <!-- SEARCH BAR -->
  <div class="search-bar-modern" style="margin-bottom:16px">
    <input type="text" id="search_cancellations_input" class="search-input-modern"
      placeholder="Cari nama atau no. HP...">
    <button type="button" id="searchCancellationsBtn" class="search-btn-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </button>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
    <div class="small" id="cancellations_info">Memuat cancellations...</div>
    <div style="display:flex;gap:8px;align-items:center"><label class="small">Per page</label><select
        id="cancellations_per_page">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>

  <div id="cancellations_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <!-- CARD GRID -->
  <div id="cancellations_tbody" class="booking-cards-grid" style="margin-top:12px;min-height:100px">
    <div class="small" style="grid-column:1/-1;text-align:center">Loading...</div>
  </div>

  <div class="table-wrapper" style="display:none">
    <!-- Hidden legacy table wrapper for backwards compatibility -->
  </div>
  <div id="cancellations_pagination" style="margin-top:8px"></div>
</section>