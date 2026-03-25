<!-- LOGS -->
<section id="cancellations" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Logs Activity</h3>
      <p class="admin-section-subtitle">Riwayat aktivitas terbaru booking, carter, dan bagasi dalam satu halaman.</p>
    </div>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_cancellations_input" class="search-input-modern"
        placeholder="Cari aktivitas, nama, tag, atau rute...">
      <button type="button" id="searchCancellationsBtn" class="search-btn-icon" aria-label="Cari logs activity">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </button>
    </div>
  </div>

  <div class="charter-command-filters no-scrollbar" id="logsFilterRow">
    <input type="hidden" id="log_activity_type" value="">
    <button type="button" class="charter-filter-chip active" data-log-type="">Semua</button>
    <button type="button" class="charter-filter-chip" data-log-type="booking">Booking</button>
    <button type="button" class="charter-filter-chip" data-log-type="charter">Carter</button>
    <button type="button" class="charter-filter-chip" data-log-type="luggage">Bagasi</button>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="cancellations_info">Memuat logs activity...</div>
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

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const logTypeInput = document.getElementById('log_activity_type');
      const searchInput = document.getElementById('search_cancellations_input');
      let logSearchDebounce = null;

      document.querySelectorAll('[data-log-type]').forEach((chip) => {
        chip.addEventListener('click', function () {
          const type = this.getAttribute('data-log-type') || '';
          if (logTypeInput) logTypeInput.value = type;
          document.querySelectorAll('[data-log-type]').forEach((item) => item.classList.remove('active'));
          this.classList.add('active');
          if (typeof ajaxListLoad === 'function') {
            ajaxListLoad('cancellations', {
              page: 1,
              per_page: parseInt(document.getElementById('cancellations_per_page')?.value || '25', 10),
              search: searchInput?.value || '',
              type: type
            });
          }
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', function () {
          clearTimeout(logSearchDebounce);
          const value = this.value;
          logSearchDebounce = setTimeout(() => {
            if (typeof ajaxListLoad === 'function') {
              ajaxListLoad('cancellations', {
                page: 1,
                per_page: parseInt(document.getElementById('cancellations_per_page')?.value || '25', 10),
                search: value,
                type: logTypeInput?.value || ''
              });
            }
          }, 250);
        });
      }
    });
  </script>
</section>
