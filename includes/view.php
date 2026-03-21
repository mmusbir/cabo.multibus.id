<!-- VIEW DETAIL -->
<section id="view" class="card">
  <h3>View Detail Penumpang</h3>
  <div class="view-filter-grid">
    <!-- RUTE -->
    <div class="input-group">
      <span class="input-group-icon">🚌</span>
      <select id="view_rute" class="modern-select" required style="min-width:160px">
        <option value="">Pilih Rute</option>
        <?php foreach ($routes as $r)
          echo '<option value="' . htmlspecialchars($r['name']) . '">' . htmlspecialchars($r['name']) . '</option>'; ?>
      </select>
    </div>

    <!-- TANGGAL -->
    <div class="input-group">
      <span class="input-group-icon">📅</span>
      <input type="date" id="view_tanggal" class="modern-input" value="<?php echo date('Y-m-d'); ?>"
        style="min-width:160px">
    </div>

    <!-- JAM -->
    <div class="input-group">
      <span class="input-group-icon">🕒</span>
      <select id="view_jam" class="modern-select" style="min-width:140px">
        <option value="">-- Jam --</option>
      </select>
    </div>

    <!-- UNIT -->
    <div class="input-group">
      <span class="input-group-icon">🚐</span>
      <select id="view_unit" class="modern-select" style="min-width:120px">
        <option value="1">Unit 1</option>
      </select>
    </div>

    <!-- BUTTONS -->
    <button type="button" id="btnLoadPassengers" class="btn-modern">
      🔍 Lihat
    </button>
    <button type="button" id="copyAllBtn" class="btn-modern secondary">
      📋 Copy All
    </button>
  </div>
  <div id="passenger_spinner_wrap" class="spinner-wrap" style="display:none;justify-content:center;">
    <div class="ajax-spinner"></div>
  </div>
  <div id="passengerList" style="margin-top:12px;display:flex;flex-direction:column;align-items:stretch;width:100%;">
    <div class="small">Pilih rute, tanggal, dan jam lalu klik Lihat atau Refresh jams.</div>
  </div>
</section>