<!-- CUSTOMER CHARTER -->
<section id="customer_charter" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Data Customer Carter</h3>
      <p class="admin-section-subtitle">Kelola daftar pelanggan layanan carter/sewa bus.</p>
    </div>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="customerCharterFormTitle">Tambah Customer Carter</span>
    </div>
    <form id="customerCharterForm">
      <input type="hidden" id="cust_charter_id" name="id" value="">
      <input type="hidden" name="subAction" value="save">
      
      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nama Lengkap</label>
          <input id="cust_charter_nama" class="modern-input form-control" name="nama" placeholder="Nama Lengkap" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Perusahaan / Instansi</label>
          <input id="cust_charter_perusahaan" class="modern-input form-control" name="perusahaan" placeholder="Nama Perusahaan (Opsional)">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">No. Handphone</label>
          <input id="cust_charter_hp" class="modern-input form-control" name="no_hp" placeholder="No. Handphone" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Alamat Lengkap</label>
          <input id="cust_charter_alamat" class="modern-input form-control" name="alamat" placeholder="Alamat lengkap customer">
        </div>
        <div class="admin-bs-actions admin-bs-col-12">
          <button type="button" id="resetCustomerCharterForm" class="btn btn-outline-secondary btn-modern secondary" style="display:none">Batal Edit</button>
          <button type="submit" class="btn btn-primary btn-modern" id="saveCustomerCharterBtn">
            Simpan Customer
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_customer_charter_input" class="search-input-modern"
        placeholder="Cari data customer carter...">
      <button type="button" id="searchCustomerCharterBtn" class="search-btn-icon" aria-label="Cari customer">
        <i class="fa-solid fa-magnifying-glass fa-icon"></i>
      </button>
    </div>
  </div>
  <div class="admin-bs-meta">
    <div id="customer_charter_info" class="small">Memuat data...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="customer_charter_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="customer_charter_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div class="table-wrapper customers-table-wrap">
    <table class="table align-middle mb-0 customers-admin-table">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Nama</th>
          <th scope="col">Perusahaan</th>
          <th scope="col">No. Handphone</th>
          <th scope="col">Alamat</th>
          <th scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody id="customer_charter_tbody">
        <tr>
          <td colspan="6" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="customer_charter_pagination" class="pagination-outer"></div>

</section>

<script>
(function() {
    let current_page = 1;
    
    function loadCustomerCharter() {
        const search = document.getElementById('search_customer_charter_input').value;
        const per_page = document.getElementById('customer_charter_per_page').value;
        const spinner = document.getElementById('customer_charter_spinner_wrap');
        const tbody = document.getElementById('customer_charter_tbody');
        
        if (spinner) spinner.style.display = 'flex';
        
        fetch(`admin.php?action=customer_charterPage&page=${current_page}&per_page=${per_page}&search=${encodeURIComponent(search)}`)
            .then(res => res.json())
            .then(json => {
                if (spinner) spinner.style.display = 'none';
                if (json.success) {
                    tbody.innerHTML = json.rows;
                    document.getElementById('customer_charter_pagination').innerHTML = json.pagination;
                    document.getElementById('customer_charter_info').textContent = `Total: ${json.total} data`;
                    
                    // Add listeners for edit and delete buttons via event delegation
                    if (!tbody.dataset.boundEvents) {
                        tbody.dataset.boundEvents = "1";
                        tbody.addEventListener('click', function(e) {
                            const editBtn = e.target.closest('.edit-customer-charter-btn');
                            if (editBtn) {
                                editCustomerCharter(editBtn.dataset.id);
                                return;
                            }
                            const deleteBtn = e.target.closest('.delete-customer-charter-btn');
                            if (deleteBtn) {
                                deleteCustomerCharter(deleteBtn.dataset.id);
                                return;
                            }
                        });
                    }

                    // Add listeners for pagination
                    document.querySelectorAll('#customer_charter_pagination a.ajax-page').forEach(a => {
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            current_page = parseInt(this.dataset.page);
                            loadCustomerCharter();
                        });
                    });
                }
            })
            .catch(err => {
                if (spinner) spinner.style.display = 'none';
                console.error(err);
            });
    }

    function editCustomerCharter(id) {
        fetch(`admin.php?action=customer_charterCRUD&subAction=get&id=${id}`)
            .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
            .then(json => {
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('cust_charter_id').value = d.id;
                    document.getElementById('cust_charter_nama').value = d.nama;
                    document.getElementById('cust_charter_perusahaan').value = d.perusahaan || '';
                    document.getElementById('cust_charter_hp').value = d.no_hp;
                    document.getElementById('cust_charter_alamat').value = d.alamat || '';
                    
                    document.getElementById('customerCharterFormTitle').textContent = 'Edit Customer Carter';
                    document.getElementById('saveCustomerCharterBtn').textContent = 'Update Customer';
                    document.getElementById('resetCustomerCharterForm').style.display = 'inline-block';
                    
                    document.querySelector('#customer_charter .modern-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.getElementById('cust_charter_nama').focus();
                }
            });
    }

    function deleteCustomerCharter(id) {
        if (typeof customConfirm === 'function') {
            customConfirm('Hapus data customer carter ini?', () => {
                const formData = new FormData();
                formData.append('subAction', 'delete');
                formData.append('id', id);
                
                fetch('admin.php?action=customer_charterCRUD', {
                    method: 'POST',
                    body: formData
                })
                .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
                .then(json => {
                    if (json.success) {
                        window.customAlert('Customer berhasil dihapus');
                        loadCustomerCharter();
                    } else {
                        window.customAlert(json.error || 'Gagal menghapus customer', 'Error');
                    }
                });
            }, 'Hapus Data', 'danger');
        }
    }

    function resetForm() {
        document.getElementById('customerCharterForm').reset();
        document.getElementById('cust_charter_id').value = '';
        document.getElementById('customerCharterFormTitle').textContent = 'Tambah Customer Carter';
        document.getElementById('saveCustomerCharterBtn').textContent = 'Simpan Customer';
        document.getElementById('resetCustomerCharterForm').style.display = 'none';
    }

    document.getElementById('customerCharterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('admin.php?action=customer_charterCRUD', {
            method: 'POST',
            body: formData
        })
        .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
        .then(json => {
            if (json.success) {
                window.customAlert(formData.get('id') ? 'Customer berhasil diperbarui' : 'Customer berhasil ditambahkan');
                resetForm();
                loadCustomerCharter();
            } else {
                window.customAlert(json.error || 'Terjadi kesalahan', 'Error');
            }
        });
    });

    document.getElementById('resetCustomerCharterForm').addEventListener('click', resetForm);
    document.getElementById('search_customer_charter_input').addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            current_page = 1;
            loadCustomerCharter();
        }
    });
    document.getElementById('searchCustomerCharterBtn').addEventListener('click', () => {
        current_page = 1;
        loadCustomerCharter();
    });
    document.getElementById('customer_charter_per_page').addEventListener('change', () => {
        current_page = 1;
        loadCustomerCharter();
    });

    // Lazy load logic integration
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'data-loaded') {
                const slot = mutation.target;
                if (slot.getAttribute('data-section-slot') === 'customer_charter' && slot.getAttribute('data-loaded') === '1') {
                    loadCustomerCharter();
                }
            }
        });
    });

    const slot = document.getElementById('section-slot-customer_charter');
    if (slot) {
        observer.observe(slot, { attributes: true });
        // Initial load if already active
        if (slot.getAttribute('data-loaded') === '1' || window.location.hash === '#customer_charter') {
            loadCustomerCharter();
        }
    }
    
    // Fallback trigger for first load
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#customer_charter') loadCustomerCharter();
    });

})();
</script>
