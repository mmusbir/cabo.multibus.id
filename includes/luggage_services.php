<!-- LUGGAGE SERVICES MANAGEMENT -->
<?php
$mRoutes = [];
try {
    $mRoutes = $conn->query("SELECT id, name FROM routes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
?>

<section id="luggage_services" class="card" style="display:none; background:transparent !important; border:none !important; box-shadow:none !important; padding:0 !important;">
    <div class="admin-section-header mb-4">
      <div>
        <h3 class="admin-section-title"><i class="fa-solid fa-sliders fa-icon" style="color:var(--neu-primary); margin-right:8px;"></i> Manajemen Layanan & Harga</h3>
        <p class="admin-section-subtitle">Konfigurasi kategori kargo dan pemetaan harga khusus rute</p>
      </div>
    </div>

    <div class="row g-4">
      <!-- Master Layanan & Form -->
      <div class="col-lg-5">
        <div class="card luggage-card p-4 shadow-sm mb-4" style="border-radius: 20px;">
          <div class="d-flex align-items-center gap-3 mb-4">
             <div class="icon-box bg-primary-soft p-3 rounded-4" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                <i class="fa-solid fa-list-check fa-2x"></i>
             </div>
             <div>
                <h5 class="mb-0 fw-bold" id="ls-form-title">Master Layanan</h5>
                <span class="small text-muted">Input kategori layanan utama</span>
             </div>
          </div>

          <form id="lsForm" class="mb-4">
            <input type="hidden" id="ls_id" value="0">
            <div class="mb-3">
              <label class="admin-bs-input-label">Nama Layanan</label>
              <div class="input-group-modern">
                <span class="input-icon"><i class="fa-solid fa-tag"></i></span>
                <input type="text" id="ls_name" class="form-control modern-input ps-5" placeholder="Contoh: Paket < 5kg, Motor Bebek..." required>
              </div>
            </div>
            <div class="mb-3">
              <label class="admin-bs-input-label">Harga Dasar (Default)</label>
              <div class="input-group-modern">
                <span class="input-icon" style="font-weight:900; font-style:normal;">Rp</span>
                <input type="number" id="ls_price" class="form-control modern-input ps-5" placeholder="0" required>
              </div>
            </div>
            <div class="mb-4">
                <label class="admin-bs-input-label">Harga Khusus Rute (Opsional)</label>
                <select id="ls_rute_id" class="form-select modern-input">
                    <option value="0">-- Gunakan Harga Dasar --</option>
                    <?php foreach ($mRoutes as $rt): ?>
                        <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="small mt-2 p-2 rounded-3" style="font-size:11px; color:var(--text-muted); background:rgba(148, 163, 184, 0.05); border-left:3px solid #cbd5e1;">
                    <i class="fa-solid fa-circle-info me-1"></i> Jika rute dipilih, harga akan langsung disimpan sebagai pemetaan rute.
                </div>
            </div>
            <div class="d-flex gap-2 pt-2">
              <button type="submit" id="btnSaveLs" class="btn btn-primary btn-modern flex-grow-1 shadow-sm py-2">
                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan
              </button>
              <button type="button" id="btnCancelLsEdit" class="btn btn-outline-secondary btn-modern secondary px-3" style="display:none;">
                <i class="fa-solid fa-rotate-left"></i>
              </button>
            </div>
          </form>

          <hr class="my-4 opacity-10">

          <div class="table-responsive">
            <table class="table table-hover align-middle" style="font-size: 13px;">
              <thead class="table-light">
                <tr>
                  <th>LAYANAN</th>
                  <th class="text-end">HARGA DASAR</th>
                  <th class="text-center">AKSI</th>
                </tr>
              </thead>
              <tbody id="ls_tbody">
                <tr><td colspan="3" class="text-center py-4">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
          <div id="ls_pagination" class="pagination-outer small mt-3"></div>
        </div>
      </div>

      <!-- Pemetaan Harga per Rute -->
      <div class="col-lg-7">
        <div class="card luggage-card p-4 shadow-sm" style="border-radius: 20px; border-top: 4px solid #10b981;">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
               <div class="icon-box bg-success-soft p-3 rounded-4" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                  <i class="fa-solid fa-map-location-dot fa-2x"></i>
               </div>
               <div>
                  <h5 class="mb-0 fw-bold">Pemetaan Harga Rute</h5>
                  <span class="small text-muted">Daftar harga khusus rute</span>
               </div>
            </div>
            <div class="search-bar-modern" style="width:200px;">
                <input type="text" id="hp_search" class="search-input-modern" placeholder="Cari rute..." oninput="filterHpTable(this.value)">
                <button type="button" class="search-btn-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="font-size:11px; letter-spacing:1px;">RUTE</th>
                  <th style="font-size:11px; letter-spacing:1px;">LAYANAN</th>
                  <th class="text-end" style="font-size:11px; letter-spacing:1px;">HARGA MAPPING</th>
                  <th class="text-center" style="font-size:11px; letter-spacing:1px;">AKSI</th>
                </tr>
              </thead>
              <tbody id="hp_tbody">
                <tr><td colspan="4" class="text-center py-5 text-muted">Memuat data mapping...</td></tr>
              </tbody>
            </table>
          </div>
          
          <div class="mt-4 p-3 rounded-4" style="background: rgba(16, 185, 129, 0.05); border: 1px dashed rgba(16, 185, 129, 0.2);">
             <h6 class="small fw-bold mb-2 text-success"><i class="fa-solid fa-lightbulb me-2"></i> Tips Pemetaan Harga</h6>
             <p class="small text-muted mb-0">Harga mapping secara otomatis memprioritaskan rute terpilih di atas harga dasar saat menghitung biaya di form input bagasi.</p>
          </div>
        </div>
      </div>
    </div>
</section>

<script>
(function() {
    const lsForm = document.getElementById('lsForm');
    const lsTbody = document.getElementById('ls_tbody');
    const lsInfo = document.getElementById('ls_info');
    const btnCancel = document.getElementById('btnCancelLsEdit');
    const btnSave = document.getElementById('btnSaveLs');
    const inputId = document.getElementById('ls_id');
    const inputName = document.getElementById('ls_name');
    const inputPrice = document.getElementById('ls_price');
    const inputRuteId = document.getElementById('ls_rute_id');
    const formTitle = document.getElementById('ls-form-title');

    function loadLuggageServices(page = 1) {
        lsTbody.innerHTML = '<tr><td colspan="3" class="text-center py-4"><div class="ajax-spinner mx-auto"></div></td></tr>';
        fetch('admin.php?action=luggage_servicesPage&page=' + page + '&per_page=10')
            .then((r) => r.json())
            .then((js) => {
                if (js.success) {
                    lsTbody.innerHTML = js.rows;
                    document.getElementById('ls_pagination').innerHTML = js.pagination || '';
                    bindLsPagination();
                    attachActionListeners();
                } else {
                    lsTbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>';
                }
            })
            .catch((e) => {
                lsTbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-danger">Kesalahan koneksi.</td></tr>';
            });
    }

    function loadPriceMapping() {
        const hpTbody = document.getElementById('hp_tbody');
        hpTbody.innerHTML = '<tr><td colspan="4" class="text-center py-5"><div class="ajax-spinner mx-auto"></div></td></tr>';
        fetch('admin.php?action=luggagePriceMappingPage')
            .then(r => r.json())
            .then(js => {
                if (js.success) {
                    hpTbody.innerHTML = js.rows;
                    attachMappingListeners();
                } else {
                    hpTbody.innerHTML = '<tr><td colspan="4" class="text-center py-5">Gagal memuat mapping.</td></tr>';
                }
            })
            .catch(e => {
                hpTbody.innerHTML = '<tr><td colspan="4" class="text-center py-5">Kesalahan koneksi.</td></tr>';
            });
    }

    function attachMappingListeners() {
        document.querySelectorAll('.delete-mapping').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const rute = this.dataset.rute;
                const layanan = this.dataset.layanan;
                customConfirm('Hapus pemetaan harga ini?', async () => {
                    const fd = new FormData();
                    fd.append('subAction', 'deleteMapping');
                    fd.append('rute_id', rute);
                    fd.append('layanan_id', layanan);
                    try {
                        const r = await fetch('admin.php?action=luggageServiceCRUD', { method: 'POST', body: fd });
                        const js = await r.json();
                        if (js.success) {
                            loadPriceMapping();
                        } else {
                            customAlert('Gagal: ' + js.error);
                        }
                    } catch(err) {}
                }, 'Hapus Mapping', 'danger');
            };
        });
    }

    function bindLsPagination() {
        document.querySelectorAll('#ls_pagination .ajax-page').forEach((link) => {
            link.onclick = function (e) {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page') || '1', 10) || 1;
                loadLuggageServices(page);
            };
        });
    }

    function attachActionListeners() {
        document.querySelectorAll('.luggage-service-action').forEach((btn) => {
            btn.onclick = function (e) {
                e.preventDefault();
                const action = this.dataset.action;
                const id = this.dataset.id;

                if (action === 'edit') {
                    inputId.value = id;
                    inputName.value = this.dataset.name;
                    inputPrice.value = this.dataset.price;
                    formTitle.textContent = 'Edit Layanan';
                    btnSave.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> Update Layanan';
                    btnCancel.style.display = 'inline-flex';
                    lsForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (action === 'delete') {
                    customConfirm('Hapus layanan ini?', async () => {
                        const fd = new FormData();
                        fd.append('subAction', 'delete');
                        fd.append('id', id);

                        try {
                            const r = await fetch('admin.php?action=luggageServiceCRUD', { method: 'POST', body: fd });
                            const js = await r.json();
                            if (js.success) {
                                await customAlert('Layanan berhasil dihapus', 'Sukses');
                                loadLuggageServices();
                            } else {
                                customAlert('Gagal: ' + (js.error || 'Terjadi kesalahan'), 'Gagal');
                            }
                        } catch (err) {}
                    }, 'Konfirmasi Hapus', 'danger');
                }
            };
        });
    }

    lsForm.onsubmit = async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveLs');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="ajax-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px"></div> Menyimpan...';

        const fd = new FormData();
        fd.append('subAction', 'save');
        fd.append('id', inputId.value);
        fd.append('name', inputName.value);
        fd.append('price', inputPrice.value);
        fd.append('rute_id', inputRuteId.value);

        try {
            const r = await fetch('admin.php?action=luggageServiceCRUD', { method: 'POST', body: fd });
            const js = await r.json();
            if (js.success) {
                customAlert('Layanan berhasil disimpan', 'Sukses');
                resetLsForm();
                loadLuggageServices();
                loadPriceMapping();
            } else {
                customAlert('Gagal: ' + (js.error || 'Terjadi kesalahan'), 'Gagal');
            }
        } catch (err) {
            customAlert('Kesalahan koneksi.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    };

    btnCancel.onclick = resetLsForm;

    function resetLsForm() {
        inputId.value = '0';
        inputName.value = '';
        inputPrice.value = '';
        inputRuteId.value = '0';
        formTitle.textContent = 'Master Layanan';
        btnSave.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> Simpan';
        btnCancel.style.display = 'none';
    }

    window.filterHpTable = function(val) {
        const trs = document.querySelectorAll('#hp_tbody tr');
        val = val.toLowerCase();
        trs.forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    };

    // Initial Load
    loadLuggageServices();
    loadPriceMapping();

    window.addEventListener('hashchange', function () {
        if (window.location.hash === '#luggage_services') {
            loadLuggageServices();
            loadPriceMapping();
        }
    });
})();
</script>
