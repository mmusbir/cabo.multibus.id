<!-- SCHEDULES -->
<section id="schedules" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Jadwal</h3>
      <p class="admin-section-subtitle">Atur rute, hari operasi, jam keberangkatan, dan kendaraan terhubung.</p>
    </div>
  </div>
  <?php
  $edit_schedule = null;
  if (isset($_GET['edit_schedule'])) {
    $id = intval($_GET['edit_schedule']);
    if ($id > 0) {
      $stmt = $conn->prepare("SELECT id, rute, dow, jam, unit_id, units FROM schedules WHERE id=? LIMIT 1");
      $stmt->execute([$id]);
      $edit_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  }

  $schedule_form_id = $edit_schedule['id'] ?? 0;
  $schedule_form_rute = $edit_schedule['rute'] ?? '';
  $schedule_form_dow = $edit_schedule['dow'] ?? '';
  $schedule_form_jam = $edit_schedule['jam'] ?? '';
  $schedule_form_unit_id = $edit_schedule['unit_id'] ?? null;
  $schedule_form_units = $edit_schedule['units'] ?? 1;
  ?>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <?php echo $schedule_form_id > 0 ? 'Edit Jadwal' : 'Tambah Jadwal'; ?>
    </div>
    <form method="post">
      <?php if ($schedule_form_id) {
        echo '<input type="hidden" name="schedule_id" value="' . intval($schedule_form_id) . '">';
      } ?>

      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Rute</label>
          <select name="sch_rute" class="modern-select form-select" required>
            <option value="">Pilih Rute</option>
            <?php foreach ($routes as $r) { ?>
              <option value="<?php echo htmlspecialchars($r['name']); ?>" <?php if ($r['name'] === $schedule_form_rute)
                   echo 'selected'; ?>><?php echo htmlspecialchars($r['name']); ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Hari Operasi</label>
          <select name="sch_dow" class="modern-select form-select" required>
            <option value="">Pilih Hari</option>
            <?php
            $daysOfWeek = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            foreach ($daysOfWeek as $i => $day) {
              echo '<option value="' . htmlspecialchars($i) . '" ' . ($i == $schedule_form_dow ? 'selected' : '') . '>' . htmlspecialchars($day) . '</option>';
            }
            ?>
          </select>
        </div>

        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Jam Keberangkatan</label>
          <input type="time" name="sch_jam" class="modern-input form-control" required
            value="<?php echo htmlspecialchars($schedule_form_jam); ?>">
        </div>

        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Jumlah Unit</label>
          <select name="sch_units" class="modern-select form-select" required title="Jumlah unit">
            <option value="">Pilih Jumlah Unit</option>
            <?php for ($i = 1; $i <= 5; $i++) { ?>
              <option value="<?php echo $i; ?>" <?php if ($i == $schedule_form_units)
                   echo 'selected'; ?>><?php echo $i; ?> Unit</option>
            <?php } ?>
          </select>
        </div>

        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Kendaraan</label>
          <select name="sch_unit_id" class="modern-select form-select">
            <option value="">Pilih Kendaraan</option>
            <?php foreach ($units as $u) { ?>
              <option value="<?php echo intval($u['id']); ?>" <?php if ($u['id'] == $schedule_form_unit_id)
                   echo 'selected'; ?>>
                <?php echo htmlspecialchars($u['nopol']) . ' (' . htmlspecialchars($u['kapasitas']) . ')'; ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="admin-bs-actions admin-bs-col-12">
          <?php if ($schedule_form_id > 0) {
            echo '<a href="admin.php#schedules" class="btn btn-outline-secondary btn-modern secondary">Batal</a>';
          } ?>
          <button name="save_schedule" class="btn btn-primary btn-modern">
            <?php echo $schedule_form_id > 0 ? 'Update Jadwal' : 'Simpan Jadwal'; ?>
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_schedule_route_input" class="search-input-modern" placeholder="Cari nama rute...">
      <button type="button" id="searchScheduleRouteBtn" class="search-btn-icon" aria-label="Cari jadwal">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="schedules_info">Memuat jadwal...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="schedules_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="schedules_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div class="table-wrapper customers-table-wrap">
    <table class="table align-middle mb-0 customers-admin-table">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Rute</th>
          <th scope="col">Hari</th>
          <th scope="col">Jam</th>
          <th scope="col">Unit</th>
          <th scope="col">Kendaraan</th>
          <th scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody id="schedules_tbody" data-colspan="7">
        <tr>
          <td colspan="7" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="schedules_pagination" class="pagination-outer"></div>
</section>

