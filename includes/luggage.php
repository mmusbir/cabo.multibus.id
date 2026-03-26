<!-- LUGGAGE DATA (Bagasi) -->
<section id="luggage" class="card" style="display:none;">
    <div class="admin-section-header">
      <div>
        <h3 class="admin-section-title">Data Bagasi</h3>
        <p class="admin-section-subtitle">Kelola data pengiriman bagasi penumpang</p>
      </div>
      <button class="btn btn-primary btn-modern" type="button" onclick="openLuggageModal()">
        <i class="fa-solid fa-plus fa-icon" style="font-size:18px;vertical-align:middle;"></i> Tambah Bagasi
      </button>
    </div>

    <!-- Filters -->
    <div class="admin-bs-meta admin-meta-gap">
       <div class="d-flex align-items-center gap-2 flex-grow-1">
          <input type="text" id="luggage_search" class="form-control modern-input" placeholder="Cari nama pengirim/penerima..." oninput="ajaxListLoad('luggage', { page: 1, search: this.value })">
       </div>
       <div class="small" id="luggage_info">Memuat data bagasi...</div>
    </div>

    <div id="luggage_spinner_wrap" class="spinner-wrap" style="display:none">
        <div class="ajax-spinner"></div>
    </div>

    <div id="luggage_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid">
        <div class="small admin-grid-message">Loading...</div>
    </div>
    
    <div id="luggage_pagination" class="pagination-outer"></div>

    <!-- Add Modal -->
    <div class="bottom-more-modal admin-modal-overlay" id="luggageModal">
      <div class="modal-popup-content admin-modal-card admin-modal-card-lg admin-modal-card-form">
        <h3 class="modal-popup-title admin-modal-heading">Input Bagasi Baru</h3>
        <form id="luggageForm" novalidate class="admin-modal-form">
          <div id="luggageErrorMsg" class="admin-modal-error"></div>
          <!-- (Form fields preserved from original) -->
          <div class="admin-modal-grid admin-modal-grid-2">
              <div class="admin-modal-field">
                <label class="admin-modal-label">Nama Pengirim</label>
                <input type="text" id="lug_sender_name" name="sender_name" class="form-control admin-modal-control" required>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">HP Pengirim</label>
                <input type="tel" id="lug_sender_phone" name="sender_phone" class="form-control admin-modal-control" required>
              </div>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Alamat Pengirim</label>
            <input type="text" id="lug_sender_address" name="sender_address" class="form-control admin-modal-control">
          </div>
          <hr class="my-2 opacity-25">
          <div class="admin-modal-grid admin-modal-grid-2">
              <div class="admin-modal-field">
                <label class="admin-modal-label">Nama Penerima</label>
                <input type="text" id="lug_receiver_name" name="receiver_name" class="form-control admin-modal-control" required>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">HP Penerima</label>
                <input type="tel" id="lug_receiver_phone" name="receiver_phone" class="form-control admin-modal-control" required>
              </div>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Alamat Penerima</label>
            <input type="text" id="lug_receiver_address" name="receiver_address" class="form-control admin-modal-control">
          </div>
          <hr class="my-2 opacity-25">
          <div class="admin-modal-grid admin-modal-grid-3">
              <div class="admin-modal-field">
                <label class="admin-modal-label">Layanan / Tipe</label>
                <select id="lug_service_id" name="service_id" class="form-control admin-modal-control" required>
                  <?php
                    $lsList = $conn->query("SELECT * FROM luggage_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($lsList as $ls) {
                      echo '<option value="' . intval($ls['id']) . '" data-price="' . htmlspecialchars($ls['price']) . '">' . htmlspecialchars($ls['name']) . '</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">Jumlah (Koli)</label>
                <input type="number" id="lug_quantity" name="quantity" class="form-control admin-modal-control" value="1" min="1" required>
              </div>
              <div class="admin-modal-field">
                <label class="admin-modal-label">Total Harga (Rp)</label>
                <input type="number" id="lug_price" name="price" class="form-control admin-modal-control" required>
              </div>
          </div>
          <div class="admin-modal-actions">
            <button type="submit" class="btn btn-primary btn-modern">Simpan Bagasi</button>
            <button type="button" class="btn btn-outline-secondary btn-modern secondary" onclick="closeLuggageModal()">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openLuggageModal() {
        document.getElementById('luggageForm').reset();
        const modal = document.getElementById('luggageModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
      }
      function closeLuggageModal() {
        const modal = document.getElementById('luggageModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
      }
      // Simple handler for calculations
      document.getElementById('lug_service_id').addEventListener('change', function() {
        const price = this.options[this.selectedIndex].getAttribute('data-price') || 0;
        document.getElementById('lug_price').value = price * (document.getElementById('lug_quantity').value || 1);
      });
      document.getElementById('luggageForm').onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const res = await fetch('admin.php?action=inputLuggage', { method: 'POST', body: formData });
        const js = await res.json();
        if (js.success) {
           closeLuggageModal();
           ajaxListLoad('luggage', { page: 1 });
        } else {
           alert(js.error || 'Gagal');
        }
      };
    </script>
</section>
