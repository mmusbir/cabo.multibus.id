<!-- LUGGAGE SERVICES MANAGEMENT -->
<section id="luggage_services" class="card" style="display:none;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0;">Manajemen Layanan Bagasi</h3>
    </div>

    <!-- FORM ADD/EDIT -->
    <div class="modern-form-card" style="margin-bottom:24px;">
        <div style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
            <span style="background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:6px;font-size:12px">MASTER</span>
            <span id="ls-form-title">Tambah Layanan Baru</span>
        </div>
        <form id="lsForm" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items: flex-end;">
            <input type="hidden" id="ls_id" value="0">
            <div class="input-group">
                <label style="font-size:11px;font-weight:700;margin-bottom:4px;display:block;color:#64748b;">Nama Layanan</label>
                <input type="text" id="ls_name" class="modern-input" placeholder="Contoh: Paket < 5kg" required style="width:100%">
            </div>
            <div class="input-group">
                <label style="font-size:11px;font-weight:700;margin-bottom:4px;display:block;color:#64748b;">Harga (Rp)</label>
                <input type="number" id="ls_price" class="modern-input" placeholder="0" required style="width:100%">
            </div>
            <div>
                <button type="submit" class="btn-modern" style="height:44px; padding:0 24px;">
                    💾 Simpan
                </button>
                <button type="button" id="btnCancelLsEdit" class="btn-modern secondary" style="height:44px; display:none;">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- SEARCH & INFO -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <div class="small" id="ls_info">Memuat layanan...</div>
    </div>

    <!-- LIST CONTAINER -->
    <div id="ls_tbody" class="booking-cards-grid" style="min-height:100px">
        <div class="small" style="grid-column:1/-1;text-align:center">Memuat data...</div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lsForm = document.getElementById('lsForm');
    const lsTbody = document.getElementById('ls_tbody');
    const lsInfo = document.getElementById('ls_info');
    const btnCancel = document.getElementById('btnCancelLsEdit');
    const inputId = document.getElementById('ls_id');
    const inputName = document.getElementById('ls_name');
    const inputPrice = document.getElementById('ls_price');
    const formTitle = document.getElementById('ls-form-title');

    function loadLuggageServices() {
        lsTbody.innerHTML = '<div class="small" style="grid-column:1/-1;text-align:center">Memuat data...</div>';
        fetch('admin/ajax.php?action=luggageServicesPage')
            .then(r => r.json())
            .then(js => {
                if (js.success) {
                    lsTbody.innerHTML = js.rows;
                    lsInfo.textContent = 'Total: ' + js.total + ' layanan';
                    attachActionListeners();
                } else {
                    lsTbody.innerHTML = '<div class="small danger" style="grid-column:1/-1;text-align:center">Gagal memuat data.</div>';
                }
            })
            .catch(e => {
                console.error(e);
                lsTbody.innerHTML = '<div class="small danger" style="grid-column:1/-1;text-align:center">Kesalahan koneksi.</div>';
            });
    }

    function attachActionListeners() {
        document.querySelectorAll('.luggage-service-action').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const action = this.dataset.action;
                const id = this.dataset.id;

                if (action === 'edit') {
                    inputId.value = id;
                    inputName.value = this.dataset.name;
                    inputPrice.value = this.dataset.price;
                    formTitle.textContent = 'Edit Layanan';
                    btnCancel.style.display = 'inline-block';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else if (action === 'delete') {
                    if (confirm('Hapus layanan ini?')) {
                        const fd = new FormData();
                        fd.append('subAction', 'delete');
                        fd.append('id', id);
                        
                        fetch('admin/ajax.php?action=luggageServiceCRUD', {
                            method: 'POST',
                            body: fd
                        })
                        .then(r => r.json())
                        .then(js => {
                            if (js.success) {
                                showToast('Layanan dihapus');
                                loadLuggageServices();
                            } else {
                                alert('Gagal: ' + js.error);
                            }
                        })
                        .catch(e => {
                            // Suppress alert, just refresh
                            loadLuggageServices();
                        });
                    }
                }
            };
        });
    }

    lsForm.onsubmit = function(e) {
        e.preventDefault();
        const fd = new FormData();
        fd.append('subAction', 'save');
        fd.append('id', inputId.value);
        fd.append('name', inputName.value);
        fd.append('price', inputPrice.value);

        fetch('admin/ajax.php?action=luggageServiceCRUD', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(js => {
            if (js.success) {
                showToast('Layanan berhasil disimpan');
                resetLsForm();
                loadLuggageServices();
            } else {
                alert('Gagal: ' + js.error);
            }
        })
        .catch(e => {
            // Suppress alert, just refresh
            resetLsForm();
            loadLuggageServices();
        });
    };

    btnCancel.onclick = function() {
        resetLsForm();
    };

    function resetLsForm() {
        inputId.value = '0';
        inputName.value = '';
        inputPrice.value = '';
        formTitle.textContent = 'Tambah Layanan Baru';
        btnCancel.style.display = 'none';
    }

    // Initial load when section shown (handled by navbar.php usually)
    // But we add a hash listener just in case it's loaded directly
    if (window.location.hash === '#luggage_services') {
        loadLuggageServices();
    }
    
    // Listen for hash changes to trigger refresh
    window.addEventListener('hashchange', function() {
        if (window.location.hash === '#luggage_services') {
            loadLuggageServices();
        }
    });
});
</script>
