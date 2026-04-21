<section id="reports" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Laporan Pendapatan</h3>
      <p class="admin-section-subtitle">Pantau pemasukan reguler, carter, dan bagasi dalam satu tampilan yang lebih rapi.</p>
    </div>
    <span class="admin-bs-chip">Revenue Report</span>
  </div>

  <div class="admin-bs-panel report-filter-panel p-3 p-lg-4">
    <div class="admin-bs-form-grid report-filter-grid">
      <div class="admin-bs-col-6 report-filter-col report-filter-col-wide">
        <label for="report_type_select" class="admin-bs-input-label">Jenis Laporan</label>
        <select id="report_type_select" class="modern-select form-select">
          <option value="reguler">Reguler (Detail Penumpang)</option>
          <option value="carter">Carter (Detail Penyewa)</option>
          <option value="bagasi">Bagasi (Detail Pengiriman)</option>
        </select>
      </div>

      <div class="admin-bs-col-6 report-filter-col">
        <label for="report_start_date" class="admin-bs-input-label">Tanggal Mulai</label>
        <input type="date" id="report_start_date" class="search-input-modern form-control"
          value="<?php echo date('Y-m-01'); ?>">
      </div>

      <div class="admin-bs-col-6 report-filter-col">
        <label for="report_end_date" class="admin-bs-input-label">Tanggal Akhir</label>
        <input type="date" id="report_end_date" class="search-input-modern form-control"
          value="<?php echo date('Y-m-t'); ?>">
      </div>

      <div class="admin-bs-col-12">
        <div class="admin-bs-actions report-filter-actions">
          <button type="button" id="btnGenerateReport" class="btn btn-modern report-action-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            <span>Generate</span>
          </button>
          <button type="button" id="btnExportCsv" class="btn btn-modern secondary report-action-btn"
            style="display:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>Export CSV</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="report_summary_container" class="report-summary-grid" style="display:none;">
    <div id="card_reguler_income" class="report-summary-card report-summary-card-reguler">
      <div class="report-summary-label">Pendapatan Reguler</div>
      <div id="total_reguler_income" class="report-summary-value">Rp 0</div>
      <div id="total_reguler_count" class="report-summary-meta">0 bookings</div>
    </div>

    <div id="card_reguler_discount" class="report-summary-card report-summary-card-discount">
      <div class="report-summary-label">Total Potongan</div>
      <div id="total_reguler_discount" class="report-summary-value">Rp 0</div>
      <div class="report-summary-meta">Total diskon yang diberikan</div>
    </div>

    <div id="card_carter_income" class="report-summary-card report-summary-card-charter">
      <div class="report-summary-label">Pendapatan Carter</div>
      <div id="total_carter_income" class="report-summary-value">Rp 0</div>
      <div id="total_carter_count" class="report-summary-meta">0 charters</div>
    </div>

    <div id="card_luggage_income" class="report-summary-card report-summary-card-luggage">
      <div class="report-summary-label">Pendapatan Bagasi</div>
      <div id="total_luggage_income" class="report-summary-value">Rp 0</div>
      <div id="total_luggage_count" class="report-summary-meta">0 shipments</div>
    </div>

    <div class="report-summary-card report-summary-card-total">
      <div class="report-summary-label">Total Keseluruhan</div>
      <div id="total_grand_income" class="report-summary-value">Rp 0</div>
      <div class="report-summary-meta">Gabungan semua layanan</div>
    </div>
  </div>

  <div class="admin-bs-panel report-details-panel">
    <div class="report-details-head" id="report_details_title">Detail Transaksi</div>
    <div class="table-responsive">
      <table class="table align-middle mb-0 report-details-table">
        <thead>
          <tr>
            <th scope="col">Tanggal</th>
            <th scope="col" id="th_name">Nama</th>
            <th scope="col" id="th_phone">No. HP</th>
            <th scope="col" id="th_route">Rute / Unit</th>
            <th scope="col" id="th_discount" class="text-end">Potongan</th>
            <th scope="col" class="text-end">Total</th>
          </tr>
        </thead>
        <tbody id="report_details_tbody">
          <tr>
            <td colspan="6" class="report-table-empty">Silakan klik "Generate Report" untuk melihat data.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
(function () {
  var btnGenerate = document.getElementById('btnGenerateReport');
  var btnExport   = document.getElementById('btnExportCsv');

  var generateButtonContent = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg><span>Generate</span>';

  function formatRp(n) {
    return 'Rp ' + parseInt(n || 0, 10).toLocaleString('id-ID');
  }

  function showAlert(msg, title) {
    if (typeof window.customAlert === 'function') {
      window.customAlert(msg, title);
    } else {
      alert(title + ': ' + msg);
    }
  }

  if (btnGenerate && !btnGenerate.dataset.reportBound) {
    btnGenerate.dataset.reportBound = '1';

    btnGenerate.addEventListener('click', function () {
      var start = document.getElementById('report_start_date') ? document.getElementById('report_start_date').value : '';
      var end   = document.getElementById('report_end_date')   ? document.getElementById('report_end_date').value   : '';
      var type  = document.getElementById('report_type_select') ? document.getElementById('report_type_select').value : 'reguler';

      if (!start || !end) {
        showAlert('Pilih tanggal mulai dan akhir.', 'Filter Belum Lengkap');
        return;
      }

      btnGenerate.disabled = true;
      btnGenerate.innerHTML = '<span class="spinner-small"></span><span>Generating...</span>';

      fetch('admin.php?action=reportsPage&start_date=' + encodeURIComponent(start) + '&end_date=' + encodeURIComponent(end) + '&type=' + encodeURIComponent(type), {
        credentials: 'same-origin'
      })
        .then(function (res) {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then(function (data) {
          if (!data.success) {
            if (btnExport) btnExport.style.display = 'none';
            var summaryEl = document.getElementById('report_summary_container');
            if (summaryEl) summaryEl.style.display = 'none';
            showAlert('Gagal mengambil data laporan: ' + (data.error || 'Unknown error'), 'Gagal Memuat Laporan');
            return;
          }

          // Summary cards
          var summaryEl = document.getElementById('report_summary_container');
          if (summaryEl) summaryEl.style.display = 'grid';

          var el;
          el = document.getElementById('total_reguler_income');   if (el) el.textContent = formatRp(data.reguler_total);
          el = document.getElementById('total_reguler_count');    if (el) el.textContent = (data.reguler_count || 0) + ' bookings';
          el = document.getElementById('total_reguler_discount'); if (el) el.textContent = formatRp(data.reguler_discount_total);
          el = document.getElementById('total_carter_income');    if (el) el.textContent = formatRp(data.carter_total);
          el = document.getElementById('total_carter_count');     if (el) el.textContent = (data.carter_count || 0) + ' charters';
          el = document.getElementById('total_luggage_income');   if (el) el.textContent = formatRp(data.luggage_total);
          el = document.getElementById('total_luggage_count');    if (el) el.textContent = (data.luggage_count || 0) + ' shipments';

          var grandTotal = parseInt(data.reguler_total || 0) + parseInt(data.carter_total || 0) + parseInt(data.luggage_total || 0);
          el = document.getElementById('total_grand_income'); if (el) el.textContent = formatRp(grandTotal);

          // Column headers & card visibility
          var thName     = document.getElementById('th_name');
          var thPhone    = document.getElementById('th_phone');
          var thRoute    = document.getElementById('th_route');
          var thDiscount = document.getElementById('th_discount');

          var cardReguler  = document.getElementById('card_reguler_income');
          var cardDiscount = document.getElementById('card_reguler_discount');
          var cardCarter   = document.getElementById('card_carter_income');
          var cardLuggage  = document.getElementById('card_luggage_income');

          if (type === 'reguler') {
            if (cardReguler)  cardReguler.style.display  = 'block';
            if (cardDiscount) cardDiscount.style.display = 'block';
            if (cardCarter)   cardCarter.style.display   = 'none';
            if (cardLuggage)  cardLuggage.style.display  = 'none';
            if (thDiscount)   thDiscount.style.display   = 'table-cell';
            if (thName)  thName.textContent  = 'Nama';
            if (thPhone) thPhone.textContent = 'No. HP';
            if (thRoute) thRoute.textContent = 'Rute / Unit';
          } else if (type === 'bagasi') {
            if (cardReguler)  cardReguler.style.display  = 'none';
            if (cardDiscount) cardDiscount.style.display = 'none';
            if (cardCarter)   cardCarter.style.display   = 'none';
            if (cardLuggage)  cardLuggage.style.display  = 'block';
            if (thDiscount)   thDiscount.style.display   = 'none';
            if (thName)  thName.textContent  = 'Pengirim';
            if (thPhone) thPhone.textContent = 'Penerima';
            if (thRoute) thRoute.textContent = 'Layanan';
          } else {
            if (cardReguler)  cardReguler.style.display  = 'none';
            if (cardDiscount) cardDiscount.style.display = 'none';
            if (cardCarter)   cardCarter.style.display   = 'block';
            if (cardLuggage)  cardLuggage.style.display  = 'none';
            if (thDiscount)   thDiscount.style.display   = 'none';
            if (thName)  thName.textContent  = 'Nama';
            if (thPhone) thPhone.textContent = 'No. HP';
            if (thRoute) thRoute.textContent = 'Rute / Unit';
          }

          // Detail title
          var titleMap = { reguler: 'Detail Penumpang (Reguler)', carter: 'Detail Penyewa (Carter)', bagasi: 'Detail Pengiriman (Bagasi)' };
          el = document.getElementById('report_details_title');
          if (el) el.textContent = titleMap[type] || 'Detail Transaksi';

          // Detail table
          var tbody = document.getElementById('report_details_tbody');
          if (tbody) {
            if (data.details && data.details.length > 0) {
              var html = '';
              data.details.forEach(function (item) {
                var discCell = (type === 'reguler')
                  ? '<td class="report-cell-discount">' + formatRp(item.discount || 0) + '</td>'
                  : '';
                html += '<tr>';
                html += '<td class="report-cell-muted">'   + (item.tanggal    || '-') + '</td>';
                html += '<td class="report-cell-strong">'  + (item.name       || '-') + '</td>';
                html += '<td class="report-cell-base">'    + (item.phone      || '-') + '</td>';
                html += '<td class="report-cell-strong">'  + (item.rute       || '-') + '</td>';
                html += discCell;
                html += '<td class="report-cell-amount">'  + formatRp(item.final_price) + '</td>';
                html += '</tr>';
              });
              tbody.innerHTML = html;
            } else {
              tbody.innerHTML = '<tr><td colspan="' + (type === 'reguler' ? 6 : 5) + '" class="report-table-empty">Tidak ada data pada periode ini.</td></tr>';
            }
          }

          if (btnExport) btnExport.style.display = 'inline-flex';
        })
        .catch(function (err) {
          console.error('[Reports] Error:', err);
          if (btnExport) btnExport.style.display = 'none';
          showAlert('Terjadi kesalahan koneksi: ' + err.message, 'Network Error');
        })
        .finally(function () {
          btnGenerate.disabled = false;
          btnGenerate.innerHTML = generateButtonContent;
        });
    });
  }

  if (btnExport && !btnExport.dataset.reportBound) {
    btnExport.dataset.reportBound = '1';
    btnExport.addEventListener('click', function () {
      var start = document.getElementById('report_start_date') ? document.getElementById('report_start_date').value : '';
      var end   = document.getElementById('report_end_date')   ? document.getElementById('report_end_date').value   : '';
      var type  = document.getElementById('report_type_select') ? document.getElementById('report_type_select').value : 'reguler';
      if (!start || !end) {
        showAlert('Pilih tanggal mulai dan akhir.', 'Filter Belum Lengkap');
        return;
      }
      window.location.href = 'admin.php?action=exportReportCsv&start_date=' + encodeURIComponent(start) + '&end_date=' + encodeURIComponent(end) + '&type=' + encodeURIComponent(type);
    });
  }
})();
</script>


