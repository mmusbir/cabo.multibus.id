<!-- CUSTOMERS -->
<section id="customers" class="card">
  <h3>Data Penumpang (Customers)</h3>
  <div class="muted">Tambah, edit, hapus atau import CSV data penumpang.</div>
  <?php
  $cust_name = $edit_customer['name'] ?? '';
  $cust_phone = $edit_customer['phone'] ?? '';
  $cust_pickup = $edit_customer['pickup_point'] ?? '';
  $cust_addr = $edit_customer['address'] ?? '';
  ?>
  <!-- FORM TAMBAH -->
  <div class="modern-form-card">
    <div
      style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
      <span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:6px;font-size:12px">PLUS</span>
      Tambah / Edit Penumpang
    </div>
    <form method="post" enctype="multipart/form-data">
      <?php if (!empty($edit_customer)) {
        echo '<input type="hidden" name="customer_id" value="' . intval($edit_customer['id']) . '">';
      } ?>
      <div class="modern-form-grid">
        <!-- NAMA -->
        <div class="input-group">
          <span class="input-group-icon">👤</span>
          <input id="cust_name_input" class="modern-input" name="cust_name" placeholder="Nama Lengkap" required
            value="<?php echo htmlspecialchars($cust_name); ?>">
        </div>
        <!-- HP -->
        <div class="input-group">
          <span class="input-group-icon">📱</span>
          <input id="cust_phone_input" class="modern-input" name="cust_phone" placeholder="No. Handphone" required
            value="<?php echo htmlspecialchars($cust_phone); ?>">
        </div>
        <!-- ALAMAT -->
        <div class="input-group">
          <span class="input-group-icon">📍</span>
          <input class="modern-input" name="cust_pickup" placeholder="Titik Jemput (cth: Jl. Mawar)"
            value="<?php echo htmlspecialchars($cust_pickup); ?>">
        </div>
        <!-- GMAPS -->
        <div class="input-group">
          <span class="input-group-icon">🗺️</span>
          <input class="modern-input" name="cust_address" placeholder="Google Maps Link"
            value="<?php echo htmlspecialchars($cust_addr); ?>">
        </div>
        <!-- ACTIONS -->
        <div style="display:flex;gap:8px">
          <button name="save_customer" class="btn-modern" style="flex:1">
            <?php echo !empty($edit_customer) ? '💾 Update' : '💾 Simpan'; ?>
          </button>
          <?php if (!empty($edit_customer)) {
            echo ' <a href="admin.php" class="btn-modern secondary" style="flex:1;text-align:center">Batal</a>';
          } ?>
        </div>
      </div>
    </form>
  </div>

  <!-- IMPORT / EXPORT ROW -->
  <div class="import-row">
    <form method="post" enctype="multipart/form-data"
      style="flex:1;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <div class="file-upload-wrapper">
        <label class="file-upload-btn">
          <input type="file" name="csv" accept=".csv" required
            onchange="this.parentElement.querySelector('span').innerText = this.files[0].name">
          📁 <span>Pilih File CSV...</span>
        </label>
      </div>
      <button name="import_customers" class="btn-modern secondary" style="height:46px;padding:0 20px;min-width:auto">
        📥 Import
      </button>
      <div class="small muted" style="margin-left:8px"><?php echo htmlspecialchars($import_msg ?? ''); ?></div>
    </form>

    <form method="post">
      <button name="export_customers" class="btn-modern secondary" style="height:46px;padding:0 20px;min-width:auto">
        📤 Export CSV
      </button>
    </form>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;margin-bottom:12px">
    <div class="search-bar-modern">
      <input type="text" id="search_customer_name_input" class="search-input-modern"
        placeholder="Cari data penumpang...">
      <button type="button" id="searchCustomerBtn" class="search-btn-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
    <div id="customers_info" class="small">Memuat data...</div>
    <div style="display:flex;gap:8px;align-items:center"><label class="small">Per page</label><select
        id="customers_per_page">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>

  <div id="customers_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="customers_tbody" class="booking-cards-grid" style="margin-top:12px;min-height:100px">
    <div class="small" style="grid-column:1/-1;text-align:center">Loading...</div>
  </div>
  <div class="table-wrapper" style="display:none"></div>
  <div id="customers_pagination" style="margin-top:8px"></div>
</section>