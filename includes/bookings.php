<!-- BOOKINGS -->
<section id="bookings" class="card">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Bookings</h3>
      <p class="admin-section-subtitle">Pantau booking reguler, carter, dan bagasi dari satu tampilan ringkas.</p>
    </div>
  </div>
  <div class="bookings-header-row">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_name_input" class="search-input-modern" placeholder="Cari nama penumpang...">
      <button type="button" id="searchBtn" class="search-btn-icon" aria-label="Cari booking">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>

    <!-- Toggle Switch (Reguler / Carter / Bagasi) -->
    <div class="toggle-container grid-cols-3 w-full max-w-400" id="admin-mode-toggle-container">
      <div class="toggle-slider"></div>
      <button type="button" class="toggle-btn active" id="btn-view-reguler" onclick="switchAdminView('bookings')">Reguler</button>
      <button type="button" class="toggle-btn" id="btn-view-carter" onclick="switchAdminView('charters')">Carter</button>
      <button type="button" class="toggle-btn" id="btn-view-bagasi" onclick="switchAdminView('luggage')">Bagasi</button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div id="bookings_info" class="small">Memuat...</div>
    <div class="d-flex gap-2 align-items-center">
      <label class="small" for="bookings_per_page">Per page</label>
      <select id="bookings_per_page" class="form-select form-select-sm">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
  </div>

  <div id="bookings_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <!-- Cards container replaces table -->
  <div id="bookings_tbody" class="booking-cards-grid admin-bs-card-grid">
    <!-- Cards injected via AJAX -->
    <div class="small admin-grid-message">Loading...</div>
  </div>
  <div id="bookings_pagination" class="admin-pagination-host"></div>

  <!-- CHARTERS CONTAINER -->
  <div id="charters_tbody" class="booking-cards-grid admin-bs-card-grid" style="display:none">
    <div class="small admin-grid-message">Loading Charters...</div>
  </div>
  <div id="charters_pagination" class="admin-pagination-host" style="display:none"></div>

  <!-- LUGGAGE CONTAINER -->
  <div id="luggage_tbody" class="booking-cards-grid admin-bs-card-grid" style="display:none">
    <div class="small admin-grid-message">Loading Luggage...</div>
  </div>
  <div id="luggage_pagination" class="admin-pagination-host" style="display:none"></div>

  <script>
    function switchAdminView(mode) {
      document.getElementById('btn-view-reguler').classList.toggle('active', mode === 'bookings');
      document.getElementById('btn-view-carter').classList.toggle('active', mode === 'charters');
      document.getElementById('btn-view-bagasi').classList.toggle('active', mode === 'luggage');

      const container = document.getElementById('admin-mode-toggle-container');
      if (container) {
        container.classList.remove('mode-charters', 'mode-luggage');
        if (mode === 'charters') container.classList.add('mode-charters');
        else if (mode === 'luggage') container.classList.add('mode-luggage');
      }

      // Hide all containers first
      document.getElementById('bookings_tbody').style.display = 'none';
      document.getElementById('bookings_pagination').style.display = 'none';
      document.getElementById('bookings_info').style.display = 'none';
      document.getElementById('charters_tbody').style.display = 'none';
      document.getElementById('charters_pagination').style.display = 'none';
      document.getElementById('luggage_tbody').style.display = 'none';
      document.getElementById('luggage_pagination').style.display = 'none';

      if (mode === 'charters') {
        document.getElementById('charters_tbody').style.display = 'grid';
        document.getElementById('charters_pagination').style.display = 'flex';
        if (document.getElementById('charters_tbody').children.length <= 1) {
          ajaxListLoad('charters', { page: 1 });
        }
      } else if (mode === 'luggage') {
        document.getElementById('luggage_tbody').style.display = 'grid';
        document.getElementById('luggage_pagination').style.display = 'flex';
        if (document.getElementById('luggage_tbody').children.length <= 1) {
          ajaxListLoad('luggage', { page: 1 });
        }
      } else {
        // Reguler (bookings)
        document.getElementById('bookings_tbody').style.display = 'grid';
        document.getElementById('bookings_pagination').style.display = 'flex';
        document.getElementById('bookings_info').style.display = 'block';
      }
    }
    
    // Initialize default toggle state to Reguler
    document.addEventListener('DOMContentLoaded', () => {
      switchAdminView('bookings');
    });
  </script>
</section>
