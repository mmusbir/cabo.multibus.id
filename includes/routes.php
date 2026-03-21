<!-- ROUTES -->
<section id="routes" class="card">
  <h3>Rute</h3>
  <?php
  $route_form_id = 0;
  $route_type = 'reguler';
  $route_form_name = '';
  $route_origin = '';
  $route_destination = '';
  $route_duration = '';
  $route_rental = '';
  $route_bop = '';
  $route_notes = '';

  if ($edit_route) {
    $route_id = $edit_route['id'];
    $route_type = 'reguler';
    $route_form_name = $edit_route['name'];
    $route_origin = $edit_route['origin'] ?? '';
    $route_destination = $edit_route['destination'] ?? '';
  } elseif ($edit_carter) {
    $route_form_id = $edit_carter['id'];
    $route_type = 'carter';
    $route_form_name = $edit_carter['name'];
    $route_origin = $edit_carter['origin'];
    $route_destination = $edit_carter['destination'];
    $route_duration = $edit_carter['duration'];
    $route_rental = $edit_carter['rental_price'];
    $route_bop = $edit_carter['bop_price'];
    $route_notes = $edit_carter['notes'];
  }
  ?>

  <!-- Toggle / Tabs -->
  <div style="display:flex;gap:10px;margin-bottom:16px">
    <button id="tab-reguler" class="toggle-btn active" onclick="switchRouteTab('reguler')">Rute Reguler</button>
    <button id="tab-carter" class="toggle-btn" onclick="switchRouteTab('carter')">Rute Carter</button>
  </div>

  <!-- FORM RUTE -->
  <div class="modern-form-card">
    <div
      style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
      <span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:6px;font-size:12px">PLUS</span>
      <span id="form-title"><?php echo $route_form_id > 0 ? 'Edit Rute' : 'Tambah Rute'; ?></span>
    </div>
    <form method="post" id="routeForm">
      <?php if ($route_form_id) {
        echo '<input type="hidden" name="route_id" value="' . intval($route_form_id) . '">';
      } ?>
      <input type="hidden" name="route_type" id="hidden_route_type" value="<?php echo $route_type; ?>">




      <!-- REGULER & CARTER FORM GRID -->
      <div id="form-shared" class="modern-form-grid" style="margin: 0 auto; display: grid;">
          <div class="input-group">
            <label style="font-size:11px;font-weight:700">Keberangkatan (Dari)</label>
            <input name="origin" class="modern-input" placeholder="Kota Asal"
              value="<?php echo htmlspecialchars($route_origin); ?>" required>
          </div>
          <div class="input-group">
            <label style="font-size:11px;font-weight:700">Tujuan (Ke)</label>
            <input name="destination" class="modern-input" placeholder="Kota Tujuan"
              value="<?php echo htmlspecialchars($route_destination); ?>" required>
          </div>

          <!-- CARTER ONLY FIELDS -->
          <div id="carter-fields" style="display:contents">


        <div class="input-group">
          <label style="font-size:11px;font-weight:700">Jenis Layanan</label>
          <select name="duration" class="modern-select">
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
        <div class="input-group">
          <!-- Empty or spacer -->
        </div>

        <div class="input-group">
          <label style="font-size:11px;font-weight:700">Nilai Sewa (Rp)</label>
          <input name="rental_price" type="number" class="modern-input" placeholder="0"
            value="<?php echo $route_rental; ?>">
        </div>
        <div class="input-group">
          <label style="font-size:11px;font-weight:700">Nilai BOP (Rp)</label>
          <input name="bop_price" type="number" class="modern-input" placeholder="0" value="<?php echo $route_bop; ?>">
        </div>

        <div class="input-group" style="grid-column:1/-1">
          <label style="font-size:11px;font-weight:700">Catatan</label>
          <textarea name="notes" class="modern-input" style="height:60px"
            placeholder="Keterangan rute..."><?php echo htmlspecialchars($route_notes); ?></textarea>
        </div>
          </div> <!-- /carter-fields -->
      </div>

      <div style="margin-top:12px">
        <button type="submit" name="save_route" value="1" class="btn-modern" style="min-width:auto">
          💾 <?php echo $route_form_id > 0 ? 'Update' : 'Simpan'; ?>
        </button>
        <?php if ($route_form_id > 0)
          echo '<a href="admin.php#routes" class="btn-modern secondary">Batal</a>'; ?>
      </div>
    </form>
  </div>

  <!-- SEARCH BAR -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;margin-bottom:12px">
    <div class="search-bar-modern">
      <input type="text" id="search_route_input" class="search-input-modern" placeholder="Cari nama rute...">
      <button type="button" id="searchRouteBtn" class="search-btn-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
    <div class="small" id="routes_info">Memuat rute...</div>
    <div style="display:flex;gap:8px;align-items:center"><label class="small">Per page</label><select
        id="routes_per_page">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>

  <div id="routes_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="routes_tbody" class="booking-cards-grid" style="margin-top:12px;min-height:100px">
    <div class="small" style="grid-column:1/-1;text-align:center">Loading...</div>
  </div>
  <div class="table-wrapper" style="display:none">
    <!-- Hidden legacy table wrapper just in case scripts need it, but we use div above -->
  </div>
  <div id="routes_pagination" style="margin-top:8px"></div>

  <script>
    function switchRouteTab(type) {
      // Update tabs
      document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
      document.getElementById('tab-' + type).classList.add('active');

      // Update hidden input
      const hiddenType = document.getElementById('hidden_route_type');
      if (hiddenType) hiddenType.value = type;

      // Show/Hide Carter specific fields
      const carterFields = document.getElementById('carter-fields');
      if (type === 'carter') {
          carterFields.style.display = 'contents';
      } else {
          carterFields.style.display = 'none';
      }

      // Reload List
      loadRoutes(1, type);
    }

    function loadRoutes(page, type) {
      if (type) window.currentRouteType = type;
      const t = window.currentRouteType || 'reguler';
      const perPage = parseInt(document.getElementById('routes_per_page')?.value || '25', 10);
      const search = document.getElementById('search_route_input')?.value || '';

      ajaxListLoad('routes', {
        page: page || 1,
        per_page: perPage,
        type: t,
        search: search
      });
    }

    // Actually, looking at admin.php, `ajaxListLoad` is generic. 
    // We should intercept or modify the "routes" target to include the type filter.

    // Let's store current route type in a global var
    window.currentRouteType = '<?php echo $route_type; ?>';

    // Update the initial state
    document.addEventListener('DOMContentLoaded', () => {
      switchRouteTab(window.currentRouteType);
    });
  </script>
  </div>