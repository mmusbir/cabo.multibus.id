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
            <div class="input-group admin-bs-col-6">
                <label class="admin-bs-input-label" for="ls_name">Nama Layanan</label>
                <input type="text" id="ls_name" class="modern-input form-control" placeholder="Contoh: Paket < 5kg" required>
            </div>
            <div class="input-group admin-bs-col-6">
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
    </div>

    <div id="ls_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid-min">
        <div class="small admin-grid-message">Memuat data...</div>
    </div>
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

    function loadLuggageServices() {
        lsTbody.innerHTML = '<div class="small admin-empty-state admin-grid-message">Memuat data...</div>';
        fetch('admin.php?action=luggageServicesPage')
            .then((r) => r.json())
            .then((js) => {
                if (js.success) {
                    lsTbody.innerHTML = js.rows;
                    lsInfo.textContent = 'Total: ' + js.total + ' layanan';
                    attachActionListeners();
                } else {
                    lsTbody.innerHTML = '<div class="small admin-empty-state admin-grid-message">Gagal memuat data.</div>';
                }
            })
            .catch((e) => {
                console.error(e);
                lsTbody.innerHTML = '<div class="small admin-empty-state admin-grid-message">Kesalahan koneksi.</div>';
            });
    }

    window.loadLuggageServices = loadLuggageServices;

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
