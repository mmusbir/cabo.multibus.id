<!-- ROUTES -->
<section id="routes" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Rute</h3>
      <p class=\"admin-section-subtitle\">Kelola rute reguler untuk perjalanan</p>
    </div>
  </div>
  <?php
  $edit_route = null;
  $edit_carter = null;

  if (isset($_GET['edit_route'])) {
    $id = intval($_GET['edit_route']);
    if ($id > 0) {
      $stmt = $conn->prepare("SELECT id, name, origin, destination FROM routes WHERE id=? LIMIT 1");
      $stmt->execute([$id]);
      $edit_route = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  }

  if (isset($_GET['edit_carter'])) {
    $id = intval($_GET['edit_carter']);
    if ($id > 0) {
      $stmt = $conn->prepare("SELECT * FROM master_carter WHERE id=? LIMIT 1");
      $stmt->execute([$id]);
      $edit_carter = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  }



  $route_form_id = 0;
  $route_type = 'reguler';
  $route_origin = '';
  $route_destination = '';
  $route_duration = '';
  $route_rental = '';
  $route_bop = '';
  $route_notes = '';

  if ($edit_route) {
    $route_form_id = $edit_route['id'];
    $route_type = 'reguler';
    $route_origin = $edit_route['origin'] ?? '';
    $route_destination = $edit_route['destination'] ?? '';
  } elseif ($edit_carter) {
    $route_form_id = $edit_carter['id'];
    $route_type = 'carter';
    $route_origin = $edit_carter['origin'];
    $route_destination = $edit_carter['destination'];
    $route_duration = $edit_carter['duration'];
    $route_rental = $edit_carter['rental_price'];
    $route_bop = $edit_carter['bop_price'];
    $route_notes = $edit_carter['notes'];
  }
  ?>

  <div class="route-tab-switch" role="tablist" aria-label="Jenis rute">
    <button type="button" id="tab-reguler" class="btn toggle-btn active" onclick="switchRouteTab('reguler')">Rute Reguler</button>
    <button type="button" id="tab-carter" class="btn toggle-btn" onclick="switchRouteTab('carter')">Rute Carter</button>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="form-title"><?php echo $route_form_id > 0 ? 'Edit Rute' : 'Tambah Rute'; ?></span>
    </div>
    <form method="post" id="routeForm">
      <?php if ($route_form_id) {
        echo '<input type="hidden" name="route_id" value="' . intval($route_form_id) . '">';
      } ?>
      <input type="hidden" name="route_type" id="hidden_route_type" value="<?php echo $route_type; ?>">

      <div id="form-shared" class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Keberangkatan (Dari)</label>
          <input name="origin" class="modern-input form-control" placeholder="Kota Asal"
            value="<?php echo htmlspecialchars($route_origin); ?>" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Tujuan (Ke)</label>
          <input name="destination" class="modern-input form-control" placeholder="Kota Tujuan"
            value="<?php echo htmlspecialchars($route_destination); ?>" required>
        </div>

        <div id="carter-fields" style="display:contents">
          <div class="admin-bs-field admin-bs-col-6">
            <label class="admin-bs-input-label">Jenis Layanan</label>
            <select name="duration" class="modern-select form-select">
              <option value="">-- Pilih Layanan --</option>
              <?php
              $durations = ['DROP OFF', 'HALF DAY', 'FULL DAY', '2D1N', '3D2N', '4D3N', '5D4N'];
              foreach ($durations as $d) {
                $sel = $route_duration === $d ? 'selected' : '';
                echo "<option value='$d' $sel>$d</option>";
              }
              ?>
            </select>
          </div>
          <div class="admin-bs-field admin-bs-col-6">
            <label class="admin-bs-input-label">Nilai Sewa (Rp)</label>
            <input name="rental_price" type="number" class="modern-input form-control" placeholder="0"
              value="<?php echo htmlspecialchars($route_rental); ?>">
          </div>
          <div class="admin-bs-field admin-bs-col-6">
            <label class="admin-bs-input-label">Nilai BOP (Rp)</label>
            <input name="bop_price" type="number" class="modern-input form-control" placeholder="0"
              value="<?php echo htmlspecialchars($route_bop); ?>">
          </div>
          <div class="admin-bs-field admin-bs-col-12">
            <label class="admin-bs-input-label">Catatan</label>
            <textarea name="notes" class="modern-input form-control admin-textarea-sm"
              placeholder="Keterangan rute..."><?php echo htmlspecialchars($route_notes); ?></textarea>
          </div>
        </div>
      </div>

      <div class="admin-bs-actions">
        <button type="submit" name="save_route" value="1" class="btn btn-primary btn-modern">
          <?php echo $route_form_id > 0 ? 'Update Rute' : 'Simpan Rute'; ?>
        </button>
        <?php if ($route_form_id > 0)
          echo '<a href="admin.php#routes" class="btn btn-outline-secondary btn-modern secondary">Batal</a>'; ?>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_route_input" class="search-input-modern" placeholder="Cari nama rute...">
      <button type="button" id="searchRouteBtn" class="search-btn-icon" aria-label="Cari rute">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="routes_info">Memuat rute...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="routes_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="routes_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="routes_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid">
    <div class="small admin-grid-message">Loading...</div>
  </div>
  <div id="routes_pagination" class="pagination-outer"></div>

  <script>
    <?php if ($route_type === 'carter'): ?>
      window.addEventListener('DOMContentLoaded', () => {
        if (typeof switchRouteTab === 'function') switchRouteTab('carter');
      });
    <?php endif; ?>
  </script>

  <script>
    function switchRouteTab(type) {
      document.querySelectorAll('.toggle-btn').forEach((button) => button.classList.remove('active'));
      document.getElementById('tab-' + type).classList.add('active');

      const hiddenType = document.getElementById('hidden_route_type');
      if (hiddenType) hiddenType.value = type;

      const carterFields = document.getElementById('carter-fields');
      if (carterFields) {
        carterFields.style.display = type === 'carter' ? 'contents' : 'none';
      }

      loadRoutes(1, type);
    }

    function loadRoutes(page, type) {
      if (type) window.currentRouteType = type;
      const currentType = window.currentRouteType || 'reguler';
      const search = document.getElementById('search_route_input')?.value || '';
      const currentPage = parseInt(page || '1', 10) || 1;

      ajaxListLoad('routes', {
        page: currentPage,
        per_page: parseInt(document.getElementById('routes_per_page')?.value || '25', 10),
        type: currentType,
        search: search
      });
    }

    window.currentRouteType = '<?php echo $route_type; ?>';

    document.addEventListener('DOMContentLoaded', () => {
      switchRouteTab(window.currentRouteType);
    });
  </script>
</section>

