<!-- LUGGAGE DATA (Bagasi) -->
<section id="luggage" class="card" style="display:none;">
    <div class="admin-section-header">
      <div>
        <h3 class="admin-section-title"><i class="fa-solid fa-boxes-stacked fa-icon" style="color:var(--neu-primary); margin-right:8px;"></i> Data Bagasi</h3>
        <p class="admin-section-subtitle">Monitoring dan pengelolaan status pengiriman paket/bagasi</p>
      </div>
      <a href="#luggage-create" class="btn btn-primary btn-modern" data-target="luggage-create">
        <i class="fa-solid fa-plus fa-icon"></i> Tambah Bagasi
      </a>
    </div>

    <!-- Enhanced Filters -->
    <div class="admin-bs-panel mb-4">
       <div class="modern-form-grid" style="grid-template-columns: auto 1fr auto auto; gap: 1rem;">
          <div class="admin-bs-field d-flex align-items-center">
             <div class="booking-scope-toggle" role="tablist" style="margin: 0;">
                <button type="button" class="booking-scope-chip active" id="luggage_scope_active" onclick="setLuggageScope('active')">Aktif</button>
                <button type="button" class="booking-scope-chip" id="luggage_scope_history" onclick="setLuggageScope('history')">History</button>
             </div>
             <input type="hidden" id="luggage_scope_input" value="active">
          </div>
          <div class="admin-bs-field">
             <div class="search-bar-modern" style="width:100%;">
                <input type="text" id="luggage_search" class="search-input-modern" placeholder="Cari Nama Pengirim, Penerima, atau Resi..." oninput="loadLuggageData(1)">
                <button type="button" class="search-btn-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
             </div>
          </div>
          <div class="admin-bs-field d-flex align-items-center">
             <div id="luggage_standalone_info" class="admin-bs-chip">Memuat...</div>
          </div>
       </div>
    </div>

    <div id="luggage_standalone_spinner_wrap" class="spinner-wrap" style="display:none">
        <div class="ajax-spinner"></div>
    </div>

    <div id="luggage_standalone_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid">
        <div class="small admin-grid-message"><div class="ajax-spinner" style="display:inline-block;margin-right:8px;"></div> Memuat data...</div>
    </div>
    
    <div id="luggage_standalone_pagination" class="pagination-outer"></div>

    <!-- Tracking Modal -->
    <div id="trackingModal" class="modal-modern" style="display:none;">
      <div class="modal-content-modern">
        <div class="modal-header-modern">
          <h4 class="modal-title-modern">Riwayat Tracking Bagasi</h4>
          <button type="button" class="btn-close-modern" onclick="document.getElementById('trackingModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body-modern">
          <div id="trackingResiTitle" style="font-weight:bold; margin-bottom:15px; font-size:16px;"></div>
          <div id="trackingLogsWrap" class="tracking-logs-wrap">
            <div class="small">Memuat riwayat...</div>
          </div>
        </div>
        <div class="modal-footer-modern">
          <button type="button" class="btn btn-secondary btn-modern" onclick="document.getElementById('trackingModal').style.display='none'">Tutup</button>
        </div>
      </div>
    </div>

    <script>
      function setLuggageScope(scope) {
        document.getElementById('luggage_scope_input').value = scope;
        document.getElementById('luggage_scope_active').classList.toggle('active', scope === 'active');
        document.getElementById('luggage_scope_history').classList.toggle('active', scope === 'history');
        loadLuggageData(1);
      }

      function loadLuggageData(page = 1) {
        const perPage = 25;
        const search = document.getElementById('luggage_search')?.value || '';
        const scope = document.getElementById('luggage_scope_input')?.value || 'active';
        
        // Show spinner
        const spinnerWrap = document.getElementById('luggage_standalone_spinner_wrap');
        if (spinnerWrap) spinnerWrap.style.display = 'flex';
        
        const url = new URL('admin.php', window.location.origin);
        url.searchParams.set('action', 'luggagePage');
        url.searchParams.set('page', page);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('scope', scope);
        if (search) url.searchParams.set('search', search);
        
        fetch(url.toString(), { credentials: 'same-origin' })
          .then(res => res.json())
          .then(js => {
            const tbody = document.getElementById('luggage_standalone_tbody');
            const pagination = document.getElementById('luggage_standalone_pagination');
            const info = document.getElementById('luggage_standalone_info');
            
            if (js.success) {
              tbody.innerHTML = js.rows || '';
              pagination.innerHTML = js.pagination || '';
              info.textContent = 'Total: ' + (js.total || 0);
              
              // Attach event handlers to the luggage action buttons
              if (typeof attachLuggageHandlers === 'function') {
                attachLuggageHandlers();
              }
            } else {
              tbody.innerHTML = '<div class="small admin-grid-message admin-grid-message-error">Error: ' + (js.error || 'Gagal memuat data') + '</div>';
            }
          })
          .catch(err => {
            const tbody = document.getElementById('luggage_standalone_tbody');
            tbody.innerHTML = '<div class="small admin-grid-message admin-grid-message-error">Kesalahan koneksi</div>';
            console.error('Error loading luggage data:', err);
          })
          .finally(() => {
            if (spinnerWrap) spinnerWrap.style.display = 'none';
          });
      }
      
      window.loadLuggageData = loadLuggageData;

      function attachLuggageHandlers() {
        document.querySelectorAll('.luggage-action').forEach(btn => {
          btn.onclick = function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const id = this.dataset.id;
            const resi = this.dataset.resi;

            if (action === 'trackBagasi') {
              showTracking(resi);
              return;
            }

            // Other actions (Input, Bayar, Batal)
            if (action === 'inputLuggage' || action === 'markLuggagePaid' || action === 'cancelLuggage') {
              const confirmMsg = action === 'cancelLuggage' ? 'Batalkan bagasi ini?' : (action === 'markLuggagePaid' ? 'Tandai lunas?' : 'Input bagasi?');
              const confirmTitle = action === 'cancelLuggage' ? 'Batalkan' : (action === 'markLuggagePaid' ? 'Bayar' : 'Input');
              const confirmType = action === 'cancelLuggage' ? 'danger' : 'success';

              customConfirm(confirmMsg, async () => {
                const fd = new FormData();
                fd.append('id', id);
                try {
                  const r = await fetch('admin.php?action=' + action, { method: 'POST', body: fd });
                  const js = await r.json();
                  if (js.success) {
                    await customAlert(js.message || 'Berhasil', 'Sukses');
                    loadLuggageData();
                  } else {
                    customAlert('Gagal: ' + (js.error || 'Terjadi kesalahan'), 'Gagal');
                  }
                } catch (err) { console.error(err); }
              }, confirmTitle, confirmType);
            }
          };
        });
      }

      async function showTracking(resi) {
        if (!resi) return;
        const modal = document.getElementById('trackingModal');
        const title = document.getElementById('trackingResiTitle');
        const logsWrap = document.getElementById('trackingLogsWrap');

        title.innerText = 'Resi: ' + resi;
        logsWrap.innerHTML = '<div class="ajax-spinner"></div>';
        modal.style.display = 'flex';

        try {
          const r = await fetch('admin.php?action=getTrackingLogs&resi=' + encodeURIComponent(resi));
          const js = await r.json();
          if (js.success && js.logs && js.logs.length > 0) {
            let html = '<ul class="tracking-history-list">';
            js.logs.forEach(log => {
              const date = new Date(log.created_at).toLocaleString('id-ID');
              html += `<li style="margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <div style="font-weight:bold; color:var(--primary-color);">${log.status}</div>
                <div style="font-size:14px;">${log.notes}</div>
                <div style="font-size:11px; color:var(--text-muted);">${date} - ${log.created_by_username}</div>
              </li>`;
            });
            html += '</ul>';
            logsWrap.innerHTML = html;
          } else {
            logsWrap.innerHTML = '<div class="small">Belum ada riwayat status.</div>';
          }
        } catch (err) {
          logsWrap.innerHTML = '<div class="small text-danger">Gagal memuat riwayat.</div>';
        }
      }
      
      window.attachLuggageHandlers = attachLuggageHandlers;
    </script>
</section>
