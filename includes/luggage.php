<!-- LUGGAGE DATA (Bagasi) -->
<section id="luggage" class="card" style="display:none;">
    <div class="admin-section-header">
      <div>
        <h3 class="admin-section-title">Data Bagasi</h3>
        <p class="admin-section-subtitle">Kelola data pengiriman bagasi penumpang</p>
      </div>
      <button class="btn btn-primary btn-modern" type="button" onclick="openLuggageModal()">
        <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">add</span> Tambah Bagasi
      </button>
    </div>

    <!-- Filters -->
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button class="btn btn-sm toggle-btn active" id="luggage-filter-all" onclick="filterLuggage('all')">Semua</button>
      <button class="btn btn-sm toggle-btn" id="luggage-filter-pending" onclick="filterLuggage('pending')">Pending</button>
      <button class="btn btn-sm toggle-btn" id="luggage-filter-active" onclick="filterLuggage('active')">Terangkut</button>
      <button class="btn btn-sm toggle-btn" id="luggage-filter-finished" onclick="filterLuggage('finished')">Selesai</button>
    </div>

    <!-- Search -->
    <div class="admin-bs-meta admin-meta-gap">
       <div class="d-flex align-items-center gap-2 flex-grow-1">
          <input type="text" id="luggage_search" class="form-control modern-input" placeholder="Cari nama pengirim/penerima..." oninput="loadLuggageData()">
       </div>
       <div class="small" id="luggage_info">Memuat data bagasi...</div>
    </div>

    <div id="luggage_spinner_wrap" class="spinner-wrap" style="display:none">
        <div class="ajax-spinner"></div>
    </div>

    <div id="luggage_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid">
        <div class="small admin-grid-message">Loading...</div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="bottom-more-modal admin-modal-overlay" id="luggageModal">
      <div class="modal-popup-content admin-modal-card admin-modal-card-lg admin-modal-card-form">
        <h3 class="modal-popup-title admin-modal-heading">Input Bagasi Baru</h3>
        <form id="luggageForm" novalidate class="admin-modal-form">
          <div id="luggageErrorMsg" class="admin-modal-error"></div>

          <div class="admin-modal-grid admin-modal-grid-2">
              <div class="admin-modal-field">
                <label class="admin-modal-label">Nama Pengirim</label>
                <input type="text" id="lug_sender_name" class="form-control admin-modal-control" required>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">HP Pengirim</label>
                <input type="tel" id="lug_sender_phone" class="form-control admin-modal-control" required>
              </div>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Alamat Pengirim</label>
            <input type="text" id="lug_sender_address" class="form-control admin-modal-control">
          </div>

          <hr class="my-2 opacity-25">

          <div class="admin-modal-grid admin-modal-grid-2">
              <div class="admin-modal-field">
                <label class="admin-modal-label">Nama Penerima</label>
                <input type="text" id="lug_receiver_name" class="form-control admin-modal-control" required>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">HP Penerima</label>
                <input type="tel" id="lug_receiver_phone" class="form-control admin-modal-control" required>
              </div>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Alamat Penerima</label>
            <input type="text" id="lug_receiver_address" class="form-control admin-modal-control">
          </div>

          <hr class="my-2 opacity-25">

          <div class="admin-modal-grid admin-modal-grid-3">
              <div class="admin-modal-field">
                <label class="admin-modal-label">Layanan / Tipe</label>
                <select id="lug_service_id" class="form-control admin-modal-control" required>
                  <?php
                    $luggageServicesForModal = $conn->query("SELECT * FROM luggage_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($luggageServicesForModal as $ls) {
                      echo '<option value="' . intval($ls['id']) . '" data-price="' . htmlspecialchars($ls['price']) . '">' . htmlspecialchars($ls['name']) . ' - Rp ' . number_format($ls['price'],0,',','.') . '</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">Jumlah (Koli)</label>
                <input type="number" id="lug_quantity" class="form-control admin-modal-control" value="1" min="1" required>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">Total Harga (Rp)</label>
                <input type="number" id="lug_price" class="form-control admin-modal-control" required>
              </div>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Catatan</label>
            <input type="text" id="lug_notes" class="form-control admin-modal-control" placeholder="Misal: Barang mudah pecah">
          </div>

          <div class="admin-modal-actions">
            <button type="submit" class="btn btn-primary btn-modern">Simpan Bagasi</button>
            <button type="button" class="btn btn-outline-secondary btn-modern secondary" onclick="closeLuggageModal()">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      window.currentLuggageFilter = 'all';

      function loadLuggageData() {
        const search = document.getElementById('luggage_search')?.value || '';
        const filter = window.currentLuggageFilter || 'all';
        const spinnerWrap = document.getElementById('luggage_spinner_wrap');
        const tbody = document.getElementById('luggage_tbody');
        const info = document.getElementById('luggage_info');
        if (spinnerWrap) spinnerWrap.style.display = 'flex';

        const url = new URL('admin/ajax.php', window.location.origin);
        url.searchParams.set('action', 'luggageDataPage');
        url.searchParams.set('search', search);
        url.searchParams.set('status_filter', filter);

        fetch(url)
          .then(r => r.json())
          .then(data => {
            if (spinnerWrap) spinnerWrap.style.display = 'none';
            if (data.success) {
              if (tbody) tbody.innerHTML = data.rows;
              if (info) info.textContent = data.total + ' data bagasi';
            }
          })
          .catch(() => {
            if (spinnerWrap) spinnerWrap.style.display = 'none';
            if (tbody) tbody.innerHTML = '<div class="small admin-grid-message">Gagal memuat data</div>';
          });
      }

      function filterLuggage(status) {
        window.currentLuggageFilter = status;
        document.querySelectorAll('[id^="luggage-filter-"]').forEach(b => b.classList.remove('active'));
        const activeBtn = document.getElementById('luggage-filter-' + status);
        if (activeBtn) activeBtn.classList.add('active');
        loadLuggageData();
      }

      function openLuggageModal() {
        document.getElementById('luggageForm').reset();
        document.getElementById('luggageErrorMsg').textContent = '';
        const modal = document.getElementById('luggageModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
      }

      function closeLuggageModal() {
        const modal = document.getElementById('luggageModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
      }

      // Auto calculate price
      document.getElementById('lug_service_id').onchange = function() {
        const price = this.options[this.selectedIndex].getAttribute('data-price');
        const qty = document.getElementById('lug_quantity').value;
        document.getElementById('lug_price').value = Math.round(price * qty);
      };
      document.getElementById('lug_quantity').oninput = function() {
        const sel = document.getElementById('lug_service_id');
        const price = sel.options[sel.selectedIndex].getAttribute('data-price');
        document.getElementById('lug_price').value = Math.round(price * this.value);
      };

      // Save
      document.getElementById('luggageForm').onsubmit = async function(e) {
        e.preventDefault();
        const errDiv = document.getElementById('luggageErrorMsg');

        const formData = new FormData();
        formData.append('sender_name', document.getElementById('lug_sender_name').value);
        formData.append('sender_phone', document.getElementById('lug_sender_phone').value);
        formData.append('sender_address', document.getElementById('lug_sender_address').value);
        formData.append('receiver_name', document.getElementById('lug_receiver_name').value);
        formData.append('receiver_phone', document.getElementById('lug_receiver_phone').value);
        formData.append('receiver_address', document.getElementById('lug_receiver_address').value);
        formData.append('service_id', document.getElementById('lug_service_id').value);
        formData.append('quantity', document.getElementById('lug_quantity').value);
        formData.append('price', document.getElementById('lug_price').value);
        formData.append('notes', document.getElementById('lug_notes').value);

        try {
          const res = await fetch('admin/ajax.php?action=inputLuggageRaw', { method: 'POST', body: formData });
          const data = await res.json();
          if (data.success) {
            closeLuggageModal();
            loadLuggageData();
            if (typeof customAlert === 'function') customAlert(data.message || 'Bagasi berhasil ditambahkan');
          } else {
            errDiv.textContent = data.error || 'Gagal menyimpan';
          }
        } catch (err) {
          errDiv.textContent = 'Kesalahan koneksi';
        }
      };

      // Status update action (called from AJAX-rendered cards)
      window.luggageUpdateStatus = async function(id, status) {
        if (!confirm('Ubah status bagasi ini?')) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        try {
          const res = await fetch('admin/ajax.php?action=updateLuggageSimple', { method: 'POST', body: formData });
          const data = await res.json();
          if (data.success) {
            loadLuggageData();
            if (typeof customAlert === 'function') customAlert(data.message || 'Status diperbarui');
          } else {
            alert('Gagal: ' + (data.error || 'Kesalahan'));
          }
        } catch (err) {
          alert('Kesalahan koneksi');
        }
      };
    </script>
</section>
