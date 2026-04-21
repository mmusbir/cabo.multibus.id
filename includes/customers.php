<!-- CUSTOMERS -->
<section id="customers" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Data Customer Reguler</h3>
      <p class="admin-section-subtitle">Tambah, ubah, hapus, atau import data customer reguler dalam satu panel.</p>
    </div>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="customerRegulerFormTitle">Tambah Customer Reguler</span>
    </div>
    <form id="customerRegulerForm">
      <input type="hidden" id="cust_reguler_id" name="id" value="">
      <input type="hidden" name="subAction" value="save">
      
      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nama Lengkap</label>
          <input id="cust_reguler_nama" class="modern-input form-control" name="name" placeholder="Nama Lengkap" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">No. Handphone</label>
          <input id="cust_reguler_phone" class="modern-input form-control" name="phone" placeholder="No. Handphone" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Titik Jemput</label>
          <input id="cust_reguler_pickup" class="modern-input form-control" name="pickup_point" placeholder="Contoh: Jl. Mawar">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Google Maps Link</label>
          <input id="cust_reguler_address" class="modern-input form-control" name="address" placeholder="https://maps.google.com/...">
        </div>
        <div class="admin-bs-actions admin-bs-col-12">
          <button type="button" id="resetCustomerRegulerForm" class="btn btn-outline-secondary btn-modern secondary" style="display:none">Batal Edit</button>
          <button type="submit" class="btn btn-primary btn-modern" id="saveCustomerRegulerBtn">
            Simpan Customer
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="import-row admin-bs-panel">
    <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-3 flex-grow-1" action="admin.php#customers">
      <div class="file-upload-wrapper">
        <label class="file-upload-btn">
          <input type="file" name="csv" accept=".csv" required
            onchange="this.parentElement.querySelector('span').innerText = this.files[0].name">
          <span>Import CSV Customer</span>
        </label>
      </div>
      <button name="import_customers" class="btn btn-outline-secondary btn-modern secondary admin-toolbar-btn">
        Import CSV
      </button>
      <div class="small muted admin-inline-note"><?php echo htmlspecialchars($import_msg ?? ''); ?></div>
    </form>

    <form method="post" class="ms-lg-auto" action="admin.php#customers">
      <button name="export_customers" class="btn btn-outline-secondary btn-modern secondary admin-toolbar-btn">
        Export CSV
      </button>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_customer_name_input" class="search-input-modern"
        placeholder="Cari data customer reguler...">
      <button type="button" id="searchCustomerBtn" class="search-btn-icon" aria-label="Cari customer reguler">
        <i class="fa-solid fa-magnifying-glass fa-icon"></i>
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

<script>
(function() {
    let current_page = 1;
    
    function loadCustomers() {
        const search = document.getElementById('search_customer_name_input').value;
        const per_page = document.getElementById('customers_per_page').value;
        const spinner = document.getElementById('customers_spinner_wrap');
        const tbody = document.getElementById('customers_tbody');
        
        if (spinner) spinner.style.display = 'flex';
        
        fetch(`admin.php?action=customers&page=${current_page}&per_page=${per_page}&search=${encodeURIComponent(search)}`)
            .then(res => res.json())
            .then(json => {
                if (spinner) spinner.style.display = 'none';
                if (json.success) {
                    tbody.innerHTML = json.rows;
                    document.getElementById('customers_pagination').innerHTML = json.pagination;
                    document.getElementById('customers_info').textContent = `Total: ${json.total} data`;
                    
                    // Use event delegation in case ajaxListLoad re-renders the table
                    if (!tbody.dataset.boundEvents) {
                        tbody.dataset.boundEvents = "1";
                        tbody.addEventListener('click', function(e) {
                            const editBtn = e.target.closest('.edit-customer-btn');
                            if (editBtn) {
                                editCustomer(editBtn.dataset.id);
                                return;
                            }
                            const deleteBtn = e.target.closest('.delete-customer-btn');
                            if (deleteBtn) {
                                deleteCustomer(deleteBtn.dataset.id);
                                return;
                            }
                        });
                    }

                    document.querySelectorAll('#customers_pagination a.ajax-page').forEach(a => {
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            current_page = parseInt(this.dataset.page);
                            loadCustomers();
                        });
                    });
                }
            })
            .catch(err => {
                if (spinner) spinner.style.display = 'none';
                console.error(err);
            });
    }

    function editCustomer(id) {
        fetch(`admin.php?action=customer_crud&subAction=get&id=${id}`)
            .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
            .then(json => {
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('cust_reguler_id').value = d.id;
                    document.getElementById('cust_reguler_nama').value = d.name;
                    document.getElementById('cust_reguler_phone').value = d.phone;
                    document.getElementById('cust_reguler_pickup').value = d.pickup_point || '';
                    document.getElementById('cust_reguler_address').value = d.address || '';
                    
                    document.getElementById('customerRegulerFormTitle').textContent = 'Edit Customer Reguler';
                    document.getElementById('saveCustomerRegulerBtn').textContent = 'Update Customer';
                    document.getElementById('resetCustomerRegulerForm').style.display = 'inline-block';
                    
                    document.querySelector('#customers .modern-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.getElementById('cust_reguler_nama').focus();
                } else {
                    window.customAlert(json.error || 'Gagal mengambil data customer', 'Error');
                }
            })
            .catch(err => {
                window.customAlert('Kesalahan saat memuat data: ' + err.message, 'Error');
            });
    }

    function deleteCustomer(id) {
        if (typeof customConfirm === 'function') {
            customConfirm('Hapus data customer ini?', () => {
                const formData = new FormData();
                formData.append('subAction', 'delete');
                formData.append('id', id);
                
                fetch('admin.php?action=customer_crud', {
                    method: 'POST',
                    body: formData
                })
                .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
                .then(json => {
                    if (json.success) {
                        window.customAlert(json.message || 'Customer berhasil dihapus');
                        loadCustomers();
                    } else {
                        window.customAlert(json.error || 'Gagal menghapus customer', 'Error');
                    }
                })
                .catch(err => {
                    window.customAlert('Kesalahan sistem: ' + err.message, 'Error');
                });
            }, 'Hapus Data', 'danger');
        }
    }

    function resetForm() {
        document.getElementById('customerRegulerForm').reset();
        document.getElementById('cust_reguler_id').value = '';
        document.getElementById('customerRegulerFormTitle').textContent = 'Tambah Customer Reguler';
        document.getElementById('saveCustomerRegulerBtn').textContent = 'Simpan Customer';
        document.getElementById('resetCustomerRegulerForm').style.display = 'none';
    }

    document.getElementById('customerRegulerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('admin.php?action=customer_crud', {
            method: 'POST',
            body: formData
        })
        .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
        .then(json => {
            if (json.success) {
                window.customAlert(json.message || (formData.get('id') ? 'Customer berhasil diperbarui' : 'Customer berhasil ditambahkan'));
                resetForm();
                loadCustomers();
            } else {
                window.customAlert(json.error || 'Terjadi kesalahan', 'Error');
            }
        })
        .catch(err => {
            window.customAlert('Kesalahan sistem: ' + err.message, 'Error');
        });
    });

    document.getElementById('resetCustomerRegulerForm').addEventListener('click', resetForm);
    document.getElementById('search_customer_name_input').addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            current_page = 1;
            loadCustomers();
        }
    });
    document.getElementById('searchCustomerBtn').addEventListener('click', () => {
        current_page = 1;
        loadCustomers();
    });
    document.getElementById('customers_per_page').addEventListener('change', () => {
        current_page = 1;
        loadCustomers();
    });

    // Integration with SPA loading
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'data-loaded') {
                const slot = mutation.target;
                if (slot.getAttribute('data-section-slot') === 'customers' && slot.getAttribute('data-loaded') === '1') {
                    loadCustomers();
                }
            }
        });
    });

    const slot = document.getElementById('section-slot-customers');
    if (slot) {
        observer.observe(slot, { attributes: true });
        if (slot.getAttribute('data-loaded') === '1' || window.location.hash === '#customers') {
            loadCustomers();
        }
    }
    
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#customers') loadCustomers();
    });

})();
</script>
