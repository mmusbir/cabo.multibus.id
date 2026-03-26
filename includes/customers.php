<!-- CUSTOMERS -->
<section id="customers" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Data Penumpang</h3>
      <p class="admin-section-subtitle">Tambah, ubah, hapus, atau import data penumpang dalam satu panel.</p>
    </div>
  </div>
  <?php
  $cust_name = $edit_customer['name'] ?? '';
  $cust_phone = $edit_customer['phone'] ?? '';
  $cust_pickup = $edit_customer['pickup_point'] ?? '';
  $cust_addr = $edit_customer['address'] ?? '';
  ?>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <?php echo !empty($edit_customer) ? 'Edit Penumpang' : 'Tambah Penumpang'; ?>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?php if (!empty($edit_customer)) {
        echo '<input type="hidden" name="customer_id" value="' . intval($edit_customer['id']) . '">';
      } ?>
      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nama Lengkap</label>
          <input id="cust_name_input" class="modern-input form-control" name="cust_name" placeholder="Nama Lengkap" required
            value="<?php echo htmlspecialchars($cust_name); ?>">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">No. Handphone</label>
          <input id="cust_phone_input" class="modern-input form-control" name="cust_phone" placeholder="No. Handphone" required
            value="<?php echo htmlspecialchars($cust_phone); ?>">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Titik Jemput</label>
          <input class="modern-input form-control" name="cust_pickup" placeholder="Contoh: Jl. Mawar"
            value="<?php echo htmlspecialchars($cust_pickup); ?>">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Google Maps Link</label>
          <input class="modern-input form-control" name="cust_address" placeholder="https://maps.google.com/..."
            value="<?php echo htmlspecialchars($cust_addr); ?>">
        </div>
        <div class="admin-bs-actions admin-bs-col-12">
          <?php if (!empty($edit_customer)) {
            echo '<a href="admin.php#customers" class="btn btn-outline-secondary btn-modern secondary">Batal</a>';
          } ?>
          <button name="save_customer" class="btn btn-primary btn-modern">
            <?php echo !empty($edit_customer) ? 'Update Penumpang' : 'Simpan Penumpang'; ?>
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="import-row admin-bs-panel">
    <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-3 flex-grow-1">
      <div class="file-upload-wrapper">
        <label class="file-upload-btn">
          <input type="file" name="csv" accept=".csv" required
            onchange="this.parentElement.querySelector('span').innerText = this.files[0].name">
          <span>Pilih File CSV...</span>
        </label>
      </div>
      <button name="import_customers" class="btn btn-outline-secondary btn-modern secondary admin-toolbar-btn">
        Import CSV
      </button>
      <div class="small muted admin-inline-note"><?php echo htmlspecialchars($import_msg ?? ''); ?></div>
    </form>

    <form method="post" class="ms-lg-auto">
      <button name="export_customers" class="btn btn-outline-secondary btn-modern secondary admin-toolbar-btn">
        Export CSV
      </button>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_customer_name_input" class="search-input-modern"
        placeholder="Cari data penumpang...">
      <button type="button" id="searchCustomerBtn" class="search-btn-icon" aria-label="Cari penumpang">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>
  <div class="admin-bs-meta">
    <div id="customers_info" class="small">Memuat data...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="customers_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="customers_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div class="table-wrapper customers-table-wrap">
    <table class="table align-middle mb-0 customers-admin-table">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Nama</th>
          <th scope="col">No. Handphone</th>
          <th scope="col">Titik Jemput</th>
          <th scope="col">Google Maps</th>
          <th scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody id="customers_tbody">
        <tr>
          <td colspan="6" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="customers_pagination" class="pagination-outer"></div>

</section>

