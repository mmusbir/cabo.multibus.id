<!-- ROUTES REGULER -->
<section id="routes" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Rute Reguler</h3>
      <p class="admin-section-subtitle">Kelola rute reguler untuk perjalanan</p>
    </div>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="routes-form-title">Tambah Rute Reguler</span>
    </div>
    <form id="routeForm" class="admin-ajax-form">
      <input type="hidden" name="id" id="route_id" value="0">
      <input type="hidden" name="type" value="reguler">

      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Keberangkatan (Dari)</label>
          <input name="origin" id="route_origin" class="modern-input form-control" placeholder="Kota Asal" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Tujuan (Ke)</label>
          <input name="destination" id="route_destination" class="modern-input form-control" placeholder="Kota Tujuan" required>
        </div>
      </div>

      <div class="admin-bs-actions">
        <button type="submit" class="btn btn-primary btn-modern">
          <i class="fa-solid fa-floppy-disk me-2"></i>
          <span id="route-submit-text">Simpan Rute Reguler</span>
        </button>
        <button type="button" id="resetRouteForm" class="btn btn-outline-secondary btn-modern secondary" style="display:none;">Batal</button>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_route_input" class="search-input-modern" placeholder="Cari rute...">
      <button type="button" id="searchRouteBtn" class="search-btn-icon">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="routes_info">Memuat rute...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="routes_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="routes_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div class="table-wrapper customers-table-wrap">
    <table class="table align-middle mb-0 customers-admin-table">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Nama Rute</th>
          <th scope="col">Keberangkatan</th>
          <th scope="col">Tujuan</th>
          <th scope="col">Layanan</th>
          <th scope="col">Harga</th>
          <th scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody id="routes_tbody" data-colspan="7">
        <tr>
          <td colspan="7" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="routes_pagination" class="pagination-outer"></div>

  <script>
    (function() {
      function loadRoutes(page) {
        const search = document.getElementById('search_route_input')?.value || '';
        ajaxListLoad('routes', {
          page: page || 1,
          per_page: parseInt(document.getElementById('routes_per_page')?.value || '25', 10),
          type: 'reguler',
          search: search
        });
      }

      function init() {
        loadRoutes(1);
        
        const form = document.getElementById('routeForm');
        const resetBtn = document.getElementById('resetRouteForm');
        
        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                formData.append('subAction', 'save');
                
                try {
                    const res = await fetch('admin.php?action=routes_crud', {
                        method: 'POST',
                        body: formData
                    });
                    const js = await res.json();
                    if (js.success) {
                        customAlert(js.message || 'Berhasil disimpan');
                        form.reset();
                        document.getElementById('route_id').value = '0';
                        document.getElementById('routes-form-title').textContent = 'Tambah Rute Reguler';
                        document.getElementById('route-submit-text').textContent = 'Simpan Rute Reguler';
                        resetBtn.style.display = 'none';
                        loadRoutes(1);
                    } else {
                        customAlert(js.error || 'Gagal menyimpan data');
                    }
                } catch (err) {
                    customAlert('Kesalahan koneksi');
                }
            };
        }

        if (resetBtn) {
            resetBtn.onclick = () => {
                form.reset();
                document.getElementById('route_id').value = '0';
                document.getElementById('routes-form-title').textContent = 'Tambah Rute Reguler';
                document.getElementById('route-submit-text').textContent = 'Simpan Rute Reguler';
                resetBtn.style.display = 'none';
            };
        }

        const searchBtn = document.getElementById('searchRouteBtn');
        const searchInput = document.getElementById('search_route_input');
        if (searchBtn && searchInput) {
            searchBtn.onclick = () => loadRoutes(1);
            searchInput.onkeyup = (e) => { if (e.key === 'Enter') loadRoutes(1); };
        }

        const perPageSelect = document.getElementById('routes_per_page');
        if (perPageSelect) {
            perPageSelect.onchange = () => loadRoutes(1);
        }

        // Action Handlers for table buttons
        const tbody = document.getElementById('routes_tbody');
        if (tbody) {
            tbody.onclick = async (e) => {
                const editBtn = e.target.closest('.edit-route-btn');
                const deleteBtn = e.target.closest('.delete-route-btn');

                if (editBtn) {
                    const id = editBtn.dataset.id;
                    try {
                        const res = await fetch('admin.php?action=routes_crud&subAction=get&type=reguler&id=' + id);
                        const js = await res.json();
                        if (js.success && js.data) {
                            document.getElementById('route_id').value = js.data.id;
                            document.getElementById('route_origin').value = js.data.origin || '';
                            document.getElementById('route_destination').value = js.data.destination || '';
                            document.getElementById('routes-form-title').textContent = 'Edit Rute Reguler';
                            document.getElementById('route-submit-text').textContent = 'Update Rute Reguler';
                            resetBtn.style.display = 'inline-block';
                            
                            const formSection = document.querySelector('#routes .modern-form-card');
                            if (formSection) formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            document.getElementById('route_origin').focus();
                        }
                    } catch (err) {
                        customAlert('Gagal mengambil data');
                    }
                }

                if (deleteBtn) {
                    const id = deleteBtn.dataset.id;
                    customConfirm('Hapus rute reguler ini?', async () => {
                        try {
                            const formData = new FormData();
                            formData.append('subAction', 'delete');
                            formData.append('type', 'reguler');
                            formData.append('id', id);
                            const res = await fetch('admin.php?action=routes_crud', {
                                method: 'POST',
                                body: formData
                            });
                            const js = await res.json();
                            if (js.success) {
                                customAlert(js.message || 'Rute berhasil dihapus');
                                loadRoutes(1);
                            } else {
                                customAlert(js.error || 'Gagal menghapus rute');
                            }
                        } catch (err) {
                            customAlert('Kesalahan koneksi');
                        }
                    }, 'Hapus Rute', 'danger');
                }
            };
        }
      }

      const observer = new MutationObserver((mutations, obs) => {
        const el = document.getElementById('routes');
        if (el && el.style.display !== 'none' && !el.dataset.loaded) {
          el.dataset.loaded = 'true';
          init();
        }
      });
      observer.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['style', 'class'] });
    })();
  </script>
</section>
