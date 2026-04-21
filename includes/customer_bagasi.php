<!-- CUSTOMER BAGASI -->
<section id="customer_bagasi" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Data Customer Bagasi</h3>
      <p class="admin-section-subtitle">Tambah, ubah, atau hapus data pengirim dan penerima bagasi.</p>
    </div>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="customerBagasiFormTitle">Tambah Customer Bagasi</span>
    </div>
    <form id="customerBagasiForm">
      <input type="hidden" id="cust_bagasi_id" name="id" value="">
      <input type="hidden" name="subAction" value="save">
      
      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nama Lengkap</label>
          <input id="cust_bagasi_nama" class="modern-input form-control" name="nama" placeholder="Nama Lengkap" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">No. Handphone</label>
          <input id="cust_bagasi_hp" class="modern-input form-control" name="hp" placeholder="No. Handphone" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Alamat Lengkap</label>
          <input id="cust_bagasi_alamat" class="modern-input form-control" name="alamat" placeholder="Alamat lengkap customer">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Tipe Customer</label>
          <select id="cust_bagasi_tipe" name="tipe" class="modern-input form-control form-select">
            <option value="pengirim">Pengirim</option>
            <option value="penerima">Penerima</option>
            <option value="keduanya" selected>Keduanya</option>
          </select>
        </div>
        <div class="admin-bs-actions admin-bs-col-12">
          <button type="button" id="resetCustomerBagasiForm" class="btn btn-outline-secondary btn-modern secondary" style="display:none">Batal Edit</button>
          <button type="submit" class="btn btn-primary btn-modern" id="saveCustomerBagasiBtn">
            Simpan Customer
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_customer_bagasi_input" class="search-input-modern"
        placeholder="Cari data customer bagasi...">
      <button type="button" id="searchCustomerBagasiBtn" class="search-btn-icon" aria-label="Cari customer">
        <i class="fa-solid fa-magnifying-glass fa-icon"></i>
      </button>
    </div>
  </div>
  <div class="admin-bs-meta">
    <div id="customer_bagasi_info" class="small">Memuat data...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="customer_bagasi_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="customer_bagasi_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div class="table-wrapper customers-table-wrap">
    <table class="table align-middle mb-0 customers-admin-table">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Nama</th>
          <th scope="col">No. Handphone</th>
          <th scope="col">Alamat</th>
          <th scope="col">Tipe</th>
          <th scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody id="customer_bagasi_tbody">
        <tr>
          <td colspan="6" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="customer_bagasi_pagination" class="pagination-outer"></div>

</section>

<script>
(function() {
    let current_page = 1;
    
    function loadCustomerBagasi() {
        const search = document.getElementById('search_customer_bagasi_input').value;
        const per_page = document.getElementById('customer_bagasi_per_page').value;
        const spinner = document.getElementById('customer_bagasi_spinner_wrap');
        const tbody = document.getElementById('customer_bagasi_tbody');
        
        if (spinner) spinner.style.display = 'flex';
        
        fetch(`admin.php?action=customer_bagasi&page=${current_page}&per_page=${per_page}&search=${encodeURIComponent(search)}`)
            .then(res => res.json())
            .then(json => {
                if (spinner) spinner.style.display = 'none';
                if (json.success) {
                    tbody.innerHTML = json.rows;
                    document.getElementById('customer_bagasi_pagination').innerHTML = json.pagination;
                    document.getElementById('customer_bagasi_info').textContent = `Total: ${json.total} data`;
                    
                    if (!tbody.dataset.boundEvents) {
                        tbody.dataset.boundEvents = "1";
                        tbody.addEventListener('click', function(e) {
                            const editBtn = e.target.closest('.edit-customer-bagasi-btn');
                            if (editBtn) {
                                editCustomerBagasi(editBtn.dataset.id);
                                return;
                            }
                            const deleteBtn = e.target.closest('.delete-customer-bagasi-btn');
                            if (deleteBtn) {
                                deleteCustomerBagasi(deleteBtn.dataset.id);
                                return;
                            }
                        });
                    }

                    document.querySelectorAll('#customer_bagasi_pagination a.ajax-page').forEach(a => {
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            current_page = parseInt(this.dataset.page);
                            loadCustomerBagasi();
                        });
                    });
                }
            })
            .catch(err => {
                if (spinner) spinner.style.display = 'none';
                console.error(err);
            });
    }

    function editCustomerBagasi(id) {
        fetch(`admin.php?action=customer_bagasi_crud&subAction=get&id=${id}`)
            .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
            .then(json => {
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('cust_bagasi_id').value = d.id;
                    document.getElementById('cust_bagasi_nama').value = d.nama;
                    document.getElementById('cust_bagasi_hp').value = d.no_hp;
                    document.getElementById('cust_bagasi_alamat').value = d.alamat || '';
                    document.getElementById('cust_bagasi_tipe').value = d.tipe || 'keduanya';
                    
                    document.getElementById('customerBagasiFormTitle').textContent = 'Edit Customer Bagasi';
                    document.getElementById('saveCustomerBagasiBtn').textContent = 'Update Customer';
                    document.getElementById('resetCustomerBagasiForm').style.display = 'inline-block';
                    
                    document.querySelector('#customer_bagasi .modern-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.getElementById('cust_bagasi_nama').focus();
                }
            });
    }

    function deleteCustomerBagasi(id) {
        if (typeof customConfirm === 'function') {
            customConfirm('Hapus data customer bagasi ini?', () => {
                const formData = new FormData();
                formData.append('subAction', 'delete');
                formData.append('id', id);
                
                fetch('admin.php?action=customer_bagasi_crud', {
                    method: 'POST',
                    body: formData
                })
                .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
                .then(json => {
                    if (json.success) {
                        window.customAlert(json.message || 'Customer berhasil dihapus');
                        loadCustomerBagasi();
                    } else {
                        window.customAlert(json.error || 'Gagal menghapus customer', 'Error');
                    }
                });
            }, 'Hapus Data', 'danger');
        }
    }

    function resetForm() {
        document.getElementById('customerBagasiForm').reset();
        document.getElementById('cust_bagasi_id').value = '';
        document.getElementById('customerBagasiFormTitle').textContent = 'Tambah Customer Bagasi';
        document.getElementById('saveCustomerBagasiBtn').textContent = 'Simpan Customer';
        document.getElementById('resetCustomerBagasiForm').style.display = 'none';
    }

    document.getElementById('customerBagasiForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('admin.php?action=customer_bagasi_crud', {
            method: 'POST',
            body: formData
        })
        .then(res => typeof window.parseAdminApiResponse === 'function' ? window.parseAdminApiResponse(res) : res.json())
        .then(json => {
            if (json.success) {
                window.customAlert(json.message || (formData.get('id') ? 'Customer berhasil diperbarui' : 'Customer berhasil ditambahkan'));
                resetForm();
                loadCustomerBagasi();
            } else {
                window.customAlert(json.error || 'Terjadi kesalahan', 'Error');
            }
        });
    });

    document.getElementById('resetCustomerBagasiForm').addEventListener('click', resetForm);
    document.getElementById('search_customer_bagasi_input').addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            current_page = 1;
            loadCustomerBagasi();
        }
    });
    document.getElementById('searchCustomerBagasiBtn').addEventListener('click', () => {
        current_page = 1;
        loadCustomerBagasi();
    });
    document.getElementById('customer_bagasi_per_page').addEventListener('change', () => {
        current_page = 1;
        loadCustomerBagasi();
    });

    // Integration with SPA loading
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'data-loaded') {
                const slot = mutation.target;
                if (slot.getAttribute('data-section-slot') === 'customer_bagasi' && slot.getAttribute('data-loaded') === '1') {
                    loadCustomerBagasi();
                }
            }
        });
    });

    const slot = document.getElementById('section-slot-customer_bagasi');
    if (slot) {
        observer.observe(slot, { attributes: true });
        if (slot.getAttribute('data-loaded') === '1' || window.location.hash === '#customer_bagasi') {
            loadCustomerBagasi();
        }
    }
    
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#customer_bagasi') loadCustomerBagasi();
    });

})();
</script>
