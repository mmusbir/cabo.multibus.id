<!-- BOOKINGS -->
<section id="bookings" class="card">
  <h3>Bookings</h3>


  <div class="bookings-header-row">
    <div class="search-bar-modern">
      <input type="text" id="search_name_input" class="search-input-modern" placeholder="Cari nama penumpang...">
      <button type="button" id="searchBtn" class="search-btn-icon">
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
      <button class="toggle-btn active" id="btn-view-reguler" onclick="switchAdminView('bookings')">Reguler</button>
      <button class="toggle-btn" id="btn-view-carter" onclick="switchAdminView('charters')">Carter</button>
      <button class="toggle-btn" id="btn-view-bagasi" onclick="switchAdminView('luggage')">Bagasi</button>
    </div>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:8px">
    <div id="bookings_info" class="small">Memuat...</div>
    <div style="display:flex;gap:8px;align-items:center"><label class="small">Per page</label><select
        id="bookings_per_page">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>

  <div id="bookings_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <!-- Cards container replaces table -->
  <div id="bookings_tbody" class="booking-cards-grid">
    <!-- Cards injected via AJAX -->
    <div class="small" style="grid-column: 1/-1; text-align:center; padding: 20px;">Loading...</div>
  </div>
  <div id="bookings_pagination" style="margin-top:8px"></div>

  <!-- CHARTERS CONTAINER -->
  <div id="charters_tbody" class="booking-cards-grid" style="display:none">
    <div class="small" style="grid-column: 1/-1; text-align:center; padding: 20px;">Loading Charters...</div>
  </div>
  <div id="charters_pagination" style="margin-top:8px;display:none"></div>

  <!-- LUGGAGE CONTAINER -->
  <div id="luggage_tbody" class="booking-cards-grid" style="display:none">
    <div class="small" style="grid-column: 1/-1; text-align:center; padding: 20px;">Loading Luggage...</div>
  </div>
  <div id="luggage_pagination" style="margin-top:8px;display:none"></div>

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