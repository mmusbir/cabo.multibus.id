<!-- SCHEDULES -->
<div id="schedules" class="card">
  <h3>Jadwal</h3>
  <?php $schedule_form_id = $edit_schedule['id'] ?? 0;
  $schedule_form_rute = $edit_schedule['rute'] ?? '';
  $schedule_form_dow = $edit_schedule['dow'] ?? '';
  $schedule_form_jam = $edit_schedule['jam'] ?? '';
  $schedule_form_unit_id = $edit_schedule['unit_id'] ?? null;
  $schedule_form_units = $edit_schedule['units'] ?? 1; ?>
  <!-- FORM JADWAL -->
  <div class="modern-form-card">
    <div
      style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
      <span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:6px;font-size:12px">PLUS</span>
      Tambah / Edit Jadwal
    </div>
    <form method="post">
      <?php if ($schedule_form_id) {
        echo '<input type="hidden" name="schedule_id" value="' . intval($schedule_form_id) . '">';
      } ?>

      <div class="view-filter-grid"
        style="box-shadow:none;padding:0;background:transparent;backdrop-filter:none;border:none;margin:0;justify-content:flex-start">

        <!-- RUTE -->
        <div class="input-group">
          <span class="input-group-icon">🚌</span>
          <select name="sch_rute" class="modern-select" required style="min-width:160px">
            <option value="">Pilih Rute</option>
            <?php foreach ($routes as $r) { ?>
              <option value="<?php echo htmlspecialchars($r['name']); ?>" <?php if ($r['name'] === $schedule_form_rute)
                   echo 'selected'; ?>><?php echo htmlspecialchars($r['name']); ?></option>
            <?php } ?>
          </select>
        </div>

        <!-- HARI -->
        <div class="input-group">
          <span class="input-group-icon">📅</span>
          <select name="sch_dow" class="modern-select" required style="min-width:140px">
            <option value="">Pilih Hari</option>
            <?php
            $daysOfWeek = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            foreach ($daysOfWeek as $i => $day) {
              echo '<option value="' . htmlspecialchars($i) . '" ' . ($i == $schedule_form_dow ? 'selected' : '') . '>' . htmlspecialchars($day) . '</option>';
            }
            ?>
          </select>
        </div>

        <!-- JAM -->
        <div class="input-group">
          <span class="input-group-icon">🕒</span>
          <input type="time" name="sch_jam" class="modern-input" placeholder="Jam" required
            value="<?php echo htmlspecialchars($schedule_form_jam); ?>" style="min-width:120px">
        </div>

        <!-- JUMLAH UNIT -->
        <div class="input-group">
          <span class="input-group-icon">🔢</span>
          <select name="sch_units" class="modern-select" required style="min-width:120px" title="Jumlah unit">
            <option value="">Jml Unit</option>
            <?php for ($i = 1; $i <= 5; $i++) { ?>
              <option value="<?php echo $i; ?>" <?php if ($i == $schedule_form_units)
                   echo 'selected'; ?>><?php echo $i; ?>
                Unit
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- PILIH UNIT (LINKED) -->
        <div class="input-group">
          <span class="input-group-icon">🔗</span>
          <select name="sch_unit_id" class="modern-select" style="min-width:200px">
            <option value="">Pilih Kendaraan</option>
            <?php foreach ($units as $u) { ?>
              <option value="<?php echo intval($u['id']); ?>" <?php if ($u['id'] == $schedule_form_unit_id)
                   echo 'selected'; ?>>
                <?php echo htmlspecialchars($u['nopol']) . ' (' . htmlspecialchars($u['kapasitas']) . ')'; ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <button name="save_schedule" class="btn-modern" style="min-width:auto;margin-left:auto">
          💾 <?php echo $schedule_form_id > 0 ? 'Update' : 'Simpan'; ?>
        </button>
      </div>
    </form>
  </div>

  <!-- SEARCH BAR -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;margin-bottom:12px">
    <div class="search-bar-modern">
      <input type="text" id="search_schedule_route_input" class="search-input-modern" placeholder="Cari nama rute...">
      <button type="button" id="searchScheduleRouteBtn" class="search-btn-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
    <div class="small" id="schedules_info">Memuat jadwal...</div>
    <div style="display:flex;gap:8px;align-items:center"><label class="small">Per page</label><select
        id="schedules_per_page">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>

  <div id="schedules_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="schedules_tbody" class="booking-cards-grid" style="margin-top:12px;min-height:100px">
    <div class="small" style="grid-column:1/-1;text-align:center">Loading...</div>
  </div>
  <div class="table-wrapper" style="display:none"></div>
  <div id="schedules_pagination" style="margin-top:8px"></div>
</div>