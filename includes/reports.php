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
  document.addEventListener('DOMContentLoaded', function () {
    const btnGenerate = document.getElementById('btnGenerateReport');
    const btnExport = document.getElementById('btnExportCsv');
    const generateButtonContent = `
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
      </svg>
      <span>Generate</span>
    `;

    if (btnGenerate) {
      btnGenerate.onclick = async function () {
        const start = document.getElementById('report_start_date').value;
        const end = document.getElementById('report_end_date').value;

        if (!start || !end) {
          customAlert('Pilih tanggal mulai dan akhir.', 'Filter Belum Lengkap');
          return;
        }

        btnGenerate.disabled = true;
        btnGenerate.innerHTML = '<span class="spinner-small"></span><span>Generating...</span>';

        try {
          const type = document.getElementById('report_type_select').value;
          const res = await fetch(`admin.php?action=reportsPage&start_date=${start}&end_date=${end}&type=${type}`);
          const data = await res.json();

          if (data.success) {
            document.getElementById('report_summary_container').style.display = 'grid';

            document.getElementById('total_reguler_income').textContent = 'Rp ' + parseInt(data.reguler_total || 0, 10).toLocaleString('id-ID');
            document.getElementById('total_reguler_count').textContent = (data.reguler_count || 0) + ' bookings';
            document.getElementById('total_reguler_discount').textContent = 'Rp ' + parseInt(data.reguler_discount_total || 0, 10).toLocaleString('id-ID');

            document.getElementById('total_carter_income').textContent = 'Rp ' + parseInt(data.carter_total || 0, 10).toLocaleString('id-ID');
            document.getElementById('total_carter_count').textContent = (data.carter_count || 0) + ' charters';

            document.getElementById('total_luggage_income').textContent = 'Rp ' + parseInt(data.luggage_total || 0, 10).toLocaleString('id-ID');
            document.getElementById('total_luggage_count').textContent = (data.luggage_count || 0) + ' shipments';

            const grandTotal =
              parseInt(data.reguler_total || 0, 10) +
              parseInt(data.carter_total || 0, 10) +
              parseInt(data.luggage_total || 0, 10);
            document.getElementById('total_grand_income').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

            const thName = document.getElementById('th_name');
            const thPhone = document.getElementById('th_phone');
            const thRoute = document.getElementById('th_route');
            const thDiscount = document.getElementById('th_discount');

            if (type === 'reguler') {
              document.getElementById('card_reguler_income').style.display = 'block';
              document.getElementById('card_reguler_discount').style.display = 'block';
              document.getElementById('card_carter_income').style.display = 'none';
              document.getElementById('card_luggage_income').style.display = 'none';
              thDiscount.style.display = 'table-cell';
              thName.textContent = 'Nama';
              thPhone.textContent = 'No. HP';
              thRoute.textContent = 'Rute / Unit';
            } else if (type === 'bagasi') {
              document.getElementById('card_reguler_income').style.display = 'none';
              document.getElementById('card_reguler_discount').style.display = 'none';
              document.getElementById('card_carter_income').style.display = 'none';
              document.getElementById('card_luggage_income').style.display = 'block';
              thDiscount.style.display = 'none';
              thName.textContent = 'Pengirim';
              thPhone.textContent = 'Penerima';
              thRoute.textContent = 'Layanan';
            } else {
              document.getElementById('card_reguler_income').style.display = 'none';
              document.getElementById('card_reguler_discount').style.display = 'none';
              document.getElementById('card_carter_income').style.display = 'block';
              document.getElementById('card_luggage_income').style.display = 'none';
              thDiscount.style.display = 'none';
              thName.textContent = 'Nama';
              thPhone.textContent = 'No. HP';
              thRoute.textContent = 'Rute / Unit';
            }

            let title = 'Detail Penumpang (Reguler)';
            if (type === 'carter') title = 'Detail Penyewa (Carter)';
            if (type === 'bagasi') title = 'Detail Pengiriman (Bagasi)';
            document.getElementById('report_details_title').textContent = title;

            const tbody = document.getElementById('report_details_tbody');
            if (data.details && data.details.length > 0) {
              let html = '';
              data.details.forEach(item => {
                const discountCell = type === 'reguler'
                  ? `<td class="report-cell-discount">Rp ${parseInt(item.discount || 0, 10).toLocaleString('id-ID')}</td>`
                  : '';

                html += `<tr>
                  <td class="report-cell-muted">${item.tanggal}</td>
                  <td class="report-cell-strong">${item.name}</td>
                  <td class="report-cell-base">${item.phone}</td>
                  <td class="report-cell-strong">${item.rute}</td>
                  ${discountCell}
                  <td class="report-cell-amount">Rp ${parseInt(item.final_price || 0, 10).toLocaleString('id-ID')}</td>
                </tr>`;
              });
              tbody.innerHTML = html;
            } else {
              tbody.innerHTML = `<tr><td colspan="${type === 'reguler' ? 6 : 5}" class="report-table-empty">Tidak ada data pada periode ini.</td></tr>`;
            }

            if (btnExport) {
              btnExport.style.display = 'inline-flex';
            }
          } else {
            if (btnExport) {
              btnExport.style.display = 'none';
            }
            document.getElementById('report_summary_container').style.display = 'none';
            customAlert('Gagal mengambil data laporan: ' + (data.error || 'Unknown error'), 'Gagal Memuat Laporan');
          }
        } catch (e) {
          console.error(e);
          if (btnExport) {
            btnExport.style.display = 'none';
          }
          customAlert('Terjadi kesalahan koneksi.', 'Network Error');
        } finally {
          btnGenerate.disabled = false;
          btnGenerate.innerHTML = generateButtonContent;
        }
      };
    }

    if (btnExport) {
      btnExport.onclick = function () {
        const start = document.getElementById('report_start_date').value;
        const end = document.getElementById('report_end_date').value;
        const type = document.getElementById('report_type_select').value;

        if (!start || !end) {
          customAlert('Pilih tanggal mulai dan akhir.', 'Filter Belum Lengkap');
          return;
        }

        window.location.href = `admin.php?action=exportReportCsv&start_date=${start}&end_date=${end}&type=${type}`;
      };
    }
  });
</script>
