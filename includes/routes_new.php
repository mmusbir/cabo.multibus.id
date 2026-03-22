<!-- ROUTES -->
<section id="routes" class="card">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Rute</h3>
      <p class="admin-section-subtitle">Kelola rute reguler untuk perjalanan</p>
    </div>
  </div>
  <?php
  $edit_route = null;
  if (isset($_GET['edit_route'])) {
    $id = intval($_GET['edit_route']);
    if ($id > 0) {
      $stmt = $conn->prepare("SELECT id, name, origin, destination FROM routes WHERE id=? LIMIT 1");
      $stmt->execute([$id]);
      $edit_route = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  }

  $route_form_id = 0;
  $route_origin = '';
  $route_destination = '';

  if ($edit_route) {
    $route_form_id = $edit_route['id'];
    $route_origin = $edit_route['origin'] ?? '';
    $route_destination = $edit_route['destination'] ?? '';
  }
  ?>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="form-title"><?php echo $route_form_id > 0 ? 'Edit Rute' : 'Tambah Rute'; ?></span>
    </div>
    <form method="post" id="routeForm">
      <?php if ($route_form_id) {
        echo '<input type="hidden" name="route_id" value="' . intval($route_form_id) . '">';
      } ?>

      <div id="form-shared" class="modern-form-grid admin-bs-form-grid">
        <div class="input-group admin-bs-col-6">
          <label class="admin-bs-input-label">Keberangkatan (Dari)</label>
          <input name="origin" class="modern-input form-control" placeholder="Kota Asal"
            value="<?php echo htmlspecialchars($route_origin); ?>" required>
        </div>
        <div class="input-group admin-bs-col-6">
          <label class="admin-bs-input-label">Tujuan (Ke)</label>
          <input name="destination" class="modern-input form-control" placeholder="Kota Tujuan"
            value="<?php echo htmlspecialchars($route_destination); ?>" required>
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
    <div class="d-flex gap-2 align-items-center">
      <label class="small" for="routes_per_page">Per page</label>
      <select id="routes_per_page" class="form-select form-select-sm">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
  </div>

  <div id="routes_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="routes_tbody" class="booking-cards-grid admin-bs-card-grid" style="margin-top:12px;min-height:100px">
    <div class="small" style="grid-column:1/-1;text-align:center">Loading...</div>
  </div>
  <div class="table-wrapper" style="display:none">
    <!-- Hidden legacy table wrapper just in case scripts need it, but we use div above. -->
  </div>
  <div id="routes_pagination" style="margin-top:8px"></div>

  <script>
    function loadRoutes(page) {
      const perPage = parseInt(document.getElementById('routes_per_page')?.value || '25', 10);
      const search = document.getElementById('search_route_input')?.value || '';

      ajaxListLoad('routes', {
        page: page || 1,
        per_page: perPage,
        search: search
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadRoutes(1);
    });
  </script>
</section>
