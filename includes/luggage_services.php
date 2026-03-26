<!-- LUGGAGE SERVICES MANAGEMENT -->
<section id="luggage_services" class="card" style="display:none;">
    <div class="admin-section-header">
        <div>
            <h3 class="admin-section-title">Manajemen Layanan Bagasi</h3>
            <p class="admin-section-subtitle">Kelola master layanan bagasi dan harga dasar yang dipakai saat input.</p>
        </div>
    </div>

    <div class="modern-form-card admin-bs-panel admin-panel-gap">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="admin-bs-chip">Master</span>
            <span id="ls-form-title">Tambah Layanan Baru</span>
        </div>
        <form id="lsForm" class="modern-form-grid admin-bs-form-grid">
            <input type="hidden" id="ls_id" value="0">
            <div class="admin-bs-field admin-bs-col-6">
                <label class="admin-bs-input-label" for="ls_name">Nama Layanan</label>
                <input type="text" id="ls_name" class="modern-input form-control" placeholder="Contoh: Paket < 5kg" required>
            </div>
            <div class="admin-bs-field admin-bs-col-6">
                <label class="admin-bs-input-label" for="ls_price">Harga (Rp)</label>
                <input type="number" id="ls_price" class="modern-input form-control" placeholder="0" required>
            </div>
            <div class="admin-bs-actions admin-bs-col-12">
                <button type="submit" id="btnSaveLs" class="btn btn-primary btn-modern">Simpan Layanan</button>
                <button type="button" id="btnCancelLsEdit" class="btn btn-outline-secondary btn-modern secondary" style="display:none;">Batal</button>
            </div>
        </form>
    </div>

    <div class="admin-bs-meta admin-meta-gap">
        <div class="small" id="ls_info">Memuat layanan...</div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <select id="ls_per_page" class="form-select form-select-sm admin-bs-select-sm">
                <option value="10">10 / halaman</option>
                <option value="25" selected>25 / halaman</option>
                <option value="50">50 / halaman</option>
                <option value="100">100 / halaman</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper customers-table-wrap">
        <table class="table align-middle mb-0 customers-admin-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nama Layanan</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody id="ls_tbody" data-colspan="5">
                <tr>
                    <td colspan="5" class="customers-table-empty">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="ls_pagination" class="pagination-outer"></div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lsForm = document.getElementById('lsForm');
    const lsTbody = document.getElementById('ls_tbody');
    const lsInfo = document.getElementById('ls_info');
    const btnCancel = document.getElementById('btnCancelLsEdit');
    const btnSave = document.getElementById('btnSaveLs');
    const inputId = document.getElementById('ls_id');
    const inputName = document.getElementById('ls_name');
    const inputPrice = document.getElementById('ls_price');
    const formTitle = document.getElementById('ls-form-title');

    function loadLuggageServices(page = 1) {
        const perPage = parseInt(document.getElementById('ls_per_page')?.value || '25', 10);
        lsTbody.innerHTML = '<tr><td colspan="5" class="customers-table-empty">Memuat data...</td></tr>';
        fetch('admin.php?action=luggageServicesPage&page=' + page + '&per_page=' + perPage)
            .then((r) => r.json())
            .then((js) => {
                if (js.success) {
                    lsTbody.innerHTML = js.rows;
                    lsInfo.textContent = 'Total: ' + js.total;
                    document.getElementById('ls_pagination').innerHTML = js.pagination || '';
                    bindLsPagination();
                    attachActionListeners();
                } else {
                    lsTbody.innerHTML = '<tr><td colspan="5" class="customers-table-empty">Gagal memuat data.</td></tr>';
                }
            })
            .catch((e) => {
                console.error(e);
                lsTbody.innerHTML = '<tr><td colspan="5" class="customers-table-empty">Kesalahan koneksi.</td></tr>';
            });
    }

    window.loadLuggageServices = loadLuggageServices;
    window.goToLsPage = function (page) {
        loadLuggageServices(page);
    };

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
                    btnSave.textContent = 'Update Layanan';
                    btnCancel.style.display = 'inline-flex';
                    document.getElementById('luggage_services')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else if (action === 'delete') {
                    customConfirm('Hapus layanan ini?', async () => {
                        const fd = new FormData();
                        fd.append('subAction', 'delete');
                        fd.append('id', id);

                        try {
                            const r = await fetch('admin.php?action=luggageServiceCRUD', {
                                method: 'POST',
                                body: fd
                            });
                            const js = await r.json();
                            if (js.success) {
                                await customAlert('Layanan dihapus', 'Sukses');
                                loadLuggageServices();
                            } else {
                                customAlert('Gagal: ' + (js.error || 'Tidak diketahui'), 'Gagal');
                            }
                        } catch (err) {
                            loadLuggageServices();
                        }
                    }, 'Hapus Layanan', 'danger');
                }
            };
        });
    }

    lsForm.onsubmit = async function (e) {
        e.preventDefault();
        const fd = new FormData();
        fd.append('subAction', 'save');
        fd.append('id', inputId.value);
        fd.append('name', inputName.value);
        fd.append('price', inputPrice.value);

        try {
            const r = await fetch('admin.php?action=luggageServiceCRUD', {
                method: 'POST',
                body: fd
            });
            const js = await r.json();
            if (js.success) {
                await customAlert('Layanan berhasil disimpan', 'Sukses');
                resetLsForm();
                loadLuggageServices();
            } else {
                customAlert('Gagal: ' + (js.error || 'Tidak diketahui'), 'Gagal');
            }
        } catch (err) {
            resetLsForm();
            loadLuggageServices();
        }
    };

    btnCancel.onclick = function () {
        resetLsForm();
    };

    document.getElementById('ls_per_page')?.addEventListener('change', function () {
        loadLuggageServices(1);
    });

    function resetLsForm() {
        inputId.value = '0';
        inputName.value = '';
        inputPrice.value = '';
        formTitle.textContent = 'Tambah Layanan Baru';
        btnSave.textContent = 'Simpan Layanan';
        btnCancel.style.display = 'none';
    }

    if (window.location.hash === '#luggage_services') {
        loadLuggageServices();
    }

    window.addEventListener('hashchange', function () {
        if (window.location.hash === '#luggage_services') {
            loadLuggageServices();
        }
    });
});
</script>
