<!-- ROUTES CARTER -->
<section id="routes_carter" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Rute Carter</h3>
      <p class="admin-section-subtitle">Kelola rute carter untuk perjalanan</p>
    </div>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <span id="routes-carter-form-title">Tambah Rute Carter</span>
    </div>
    <form id="routeCarterForm" class="admin-ajax-form">
      <input type="hidden" name="id" id="route_carter_id" value="0">
      <input type="hidden" name="type" value="carter">

      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Keberangkatan (Dari)</label>
          <input name="origin" id="route_carter_origin" class="modern-input form-control" placeholder="Kota Asal" required>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Tujuan (Ke)</label>
          <input name="destination" id="route_carter_destination" class="modern-input form-control" placeholder="Kota Tujuan" required>
        </div>

        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Jenis Layanan</label>
          <select name="duration" id="route_carter_duration" class="modern-select form-select">
            <option value="">-- Pilih Layanan --</option>
            <option value="DROP OFF">DROP OFF</option>
            <option value="HALF DAY">HALF DAY</option>
            <option value="FULL DAY">FULL DAY</option>
            <option value="2D1N">2D1N</option>
            <option value="3D2N">3D2N</option>
            <option value="4D3N">4D3N</option>
            <option value="5D4N">5D4N</option>
          </select>
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nilai Sewa (Rp)</label>
          <input name="rental_price" id="route_carter_rental" type="number" class="modern-input form-control" placeholder="0">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nilai BOP (Rp)</label>
          <input name="bop_price" id="route_carter_bop" type="number" class="modern-input form-control" placeholder="0">
        </div>
        <div class="admin-bs-field admin-bs-col-12">
          <label class="admin-bs-input-label">Catatan</label>
          <textarea name="notes" id="route_carter_notes" class="modern-input form-control admin-textarea-sm"
            placeholder="Keterangan rute..."></textarea>
        </div>
      </div>

      <div class="admin-bs-actions">
        <button type="submit" class="btn btn-primary btn-modern">
          <i class="fa-solid fa-floppy-disk me-2"></i>
          <span id="route-carter-submit-text">Simpan Rute Carter</span>
        </button>
        <button type="button" id="resetRouteCarterForm" class="btn btn-outline-secondary btn-modern secondary" style="display:none;">Batal</button>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_route_carter_input" class="search-input-modern" placeholder="Cari rute carter...">
      <button type="button" id="searchRouteCarterBtn" class="search-btn-icon">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="routes_carter_info">Memuat rute carter...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="routes_carter_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="routes_carter_spinner_wrap" class="spinner-wrap" style="display:none">
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
          <th scope="col">BOP</th>
          <th scope="col">Keterangan</th>
          <th scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody id="routes_carter_tbody" data-colspan="9">
        <tr>
          <td colspan="9" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="routes_carter_pagination" class="pagination-outer"></div>

  <script>
    (function() {
      function loadRoutesCarter(page) {
        const search = document.getElementById('search_route_carter_input')?.value || '';
        ajaxListLoad('routes_carter', {
          page: page || 1,
          per_page: parseInt(document.getElementById('routes_carter_per_page')?.value || '25', 10),
          type: 'carter',
          search: search
        });
      }

      function init() {
        loadRoutesCarter(1);
        
        const form = document.getElementById('routeCarterForm');
        const resetBtn = document.getElementById('resetRouteCarterForm');
        
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
                        document.getElementById('route_carter_id').value = '0';
                        document.getElementById('routes-carter-form-title').textContent = 'Tambah Rute Carter';
                        document.getElementById('route-carter-submit-text').textContent = 'Simpan Rute Carter';
                        resetBtn.style.display = 'none';
                        loadRoutesCarter(1);
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
                document.getElementById('route_carter_id').value = '0';
                document.getElementById('routes-carter-form-title').textContent = 'Tambah Rute Carter';
                document.getElementById('route-carter-submit-text').textContent = 'Simpan Rute Carter';
                resetBtn.style.display = 'none';
            };
        }

        const searchBtn = document.getElementById('searchRouteCarterBtn');
        const searchInput = document.getElementById('search_route_carter_input');
        if (searchBtn && searchInput) {
            searchBtn.onclick = () => loadRoutesCarter(1);
            searchInput.onkeyup = (e) => { if (e.key === 'Enter') loadRoutesCarter(1); };
        }

        const perPageSelect = document.getElementById('routes_carter_per_page');
        if (perPageSelect) {
            perPageSelect.onchange = () => loadRoutesCarter(1);
        }

        // Action Handlers
        const tbody = document.getElementById('routes_carter_tbody');
        if (tbody) {
            tbody.onclick = async (e) => {
                const editBtn = e.target.closest('.edit-route-btn');
                const deleteBtn = e.target.closest('.delete-route-btn');

                if (editBtn) {
                    const id = editBtn.dataset.id;
                    try {
                        const res = await fetch('admin.php?action=routes_crud&subAction=get&type=carter&id=' + id);
                        const js = await res.json();
                        if (js.success && js.data) {
                            document.getElementById('route_carter_id').value = js.data.id;
                            document.getElementById('route_carter_origin').value = js.data.origin || '';
                            document.getElementById('route_carter_destination').value = js.data.destination || '';
                            document.getElementById('route_carter_duration').value = js.data.duration || '';
                            document.getElementById('route_carter_rental').value = js.data.rental_price || '';
                            document.getElementById('route_carter_bop').value = js.data.bop_price || '';
                            document.getElementById('route_carter_notes').value = js.data.notes || '';
                            
                            document.getElementById('routes-carter-form-title').textContent = 'Edit Rute Carter';
                            document.getElementById('route-carter-submit-text').textContent = 'Update Rute Carter';
                            resetBtn.style.display = 'inline-block';
                            
                            const formSection = document.querySelector('#routes_carter .modern-form-card');
                            if (formSection) formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            document.getElementById('route_carter_origin').focus();
                        }
                    } catch (err) {
                        customAlert('Gagal mengambil data');
                    }
                }

                if (deleteBtn) {
                    const id = deleteBtn.dataset.id;
                    customConfirm('Hapus rute carter ini?', async () => {
                        try {
                            const formData = new FormData();
                            formData.append('subAction', 'delete');
                            formData.append('type', 'carter');
                            formData.append('id', id);
                            const res = await fetch('admin.php?action=routes_crud', {
                                method: 'POST',
                                body: formData
                            });
                            const js = await res.json();
                            if (js.success) {
                                customAlert(js.message || 'Rute carter berhasil dihapus');
                                loadRoutesCarter(1);
                            } else {
                                customAlert(js.error || 'Gagal menghapus rute');
                            }
                        } catch (err) {
                            customAlert('Kesalahan koneksi');
                        }
                    }, 'Hapus Rute Carter', 'danger');
                }
            };
        }
      }

      const observer = new MutationObserver((mutations, obs) => {
        const el = document.getElementById('routes_carter');
        if (el && el.style.display !== 'none' && !el.dataset.loaded) {
          el.dataset.loaded = 'true';
          init();
        }
      });
      observer.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['style', 'class'] });
    })();
  </script>
</section>
