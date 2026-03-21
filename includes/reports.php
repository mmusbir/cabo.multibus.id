<section id="reports" class="card" style="display:none;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0;">Laporan Pendapatan</h3>
    </div>

    <!-- FILTER BOX -->
    <div
        style="background:rgba(255,255,255,0.5); padding:20px; border-radius:16px; border:1px solid #e2e8f0; margin-bottom:24px;">
        <div class="report-filter-row">
            <div class="report-filter-col">
                <label
                    style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase;">Jenis
                    Laporan</label>
                <select id="report_type_select" class="modern-select"
                    style="width:100%; height:44px; padding: 0 35px 0 15px !important; background-position: right 10px center; font-size: 13px;">
                    <option value="reguler">Reguler (Detail Penumpang)</option>
                    <option value="carter">Carter (Detail Penyewa)</option>
                    <option value="bagasi">Bagasi (Detail Pengiriman)</option>
                </select>
            </div>
            <div class="report-filter-col">
                <label
                    style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase;">Tanggal
                    Mulai</label>
                <input type="date" id="report_start_date" class="search-input-modern"
                    style="width:100%; background:#fff; height:44px;" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="report-filter-col">
                <label
                    style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase;">Tanggal
                    Akhir</label>
                <input type="date" id="report_end_date" class="search-input-modern"
                    style="width:100%; background:#fff; height:44px;" value="<?php echo date('Y-m-t'); ?>">
            </div>
            <button type="button" id="btnGenerateReport" class="btn-report-generate"
                style="height:44px; padding:0 24px; border-radius:12px; border:none; background:#0f172a; color:#fff; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                    style="margin-right:8px">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Generate
            </button>
            <button type="button" id="btnExportCsv" class="btn-report-export"
                style="height:44px; padding:0 16px; border-radius:12px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-weight:700; cursor:pointer; display:none; align-items:center; justify-content:center; gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    <style>
        .report-filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .report-filter-col {
            flex: 1;
            min-width: 250px;
        }

        /* Khusus jenis laporan beri ruang lebih */
        .report-filter-col:first-child {
            flex: 1.5;
        }

        @media (max-width: 600px) {
            .report-filter-col {
                flex: 1 1 100%;
            }

            .btn-report-generate,
            .btn-report-export {
                width: 100%;
                margin-top: 4px;
            }
        }
    </style>

    <!-- SUMMARY CARDS -->
    <div id="report_summary_container"
        style="display:none; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
        <!-- Reguler Income -->
        <div id="card_reguler_income"
            style="background:linear-gradient(135deg, #fff 0%, #f0f9ff 100%); padding:20px; border-radius:16px; border:1px solid #e0f2fe; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="font-size:12px; font-weight:700; color:#0369a1; text-transform:uppercase; margin-bottom:8px;">
                Pendapatan Reguler</div>
            <div id="total_reguler_income" style="font-size:24px; font-weight:800; color:#0c4a6e;">Rp 0</div>
            <div id="total_reguler_count" style="font-size:12px; color:#64748b; margin-top:4px;">0 bookings</div>
        </div>
        <!-- Reguler Discount -->
        <div id="card_reguler_discount"
            style="background:linear-gradient(135deg, #fff 0%, #fff1f2 100%); padding:20px; border-radius:16px; border:1px solid #ffe4e6; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="font-size:12px; font-weight:700; color:#e11d48; text-transform:uppercase; margin-bottom:8px;">
                Total Potongan</div>
            <div id="total_reguler_discount" style="font-size:24px; font-weight:800; color:#881337;">Rp 0</div>
            <div style="font-size:12px; color:#64748b; margin-top:4px;">Total diskon yang diberikan</div>
        </div>
        <!-- Carter Income -->
        <div id="card_carter_income"
            style="background:linear-gradient(135deg, #fff 0%, #f0fdf4 100%); padding:20px; border-radius:16px; border:1px solid #dcfce7; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="font-size:12px; font-weight:700; color:#15803d; text-transform:uppercase; margin-bottom:8px;">
                Pendapatan Carter</div>
            <div id="total_carter_income" style="font-size:24px; font-weight:800; color:#14532d;">Rp 0</div>
            <div id="total_carter_count" style="font-size:12px; color:#64748b; margin-top:4px;">0 charters</div>
        </div>
        <!-- Bagasi Income -->
        <div id="card_luggage_income"
            style="background:linear-gradient(135deg, #fff 0%, #fff7ed 100%); padding:20px; border-radius:16px; border:1px solid #ffedd5; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="font-size:12px; font-weight:700; color:#c2410c; text-transform:uppercase; margin-bottom:8px;">
                Pendapatan Bagasi</div>
            <div id="total_luggage_income" style="font-size:24px; font-weight:800; color:#7c2d12;">Rp 0</div>
            <div id="total_luggage_count" style="font-size:12px; color:#64748b; margin-top:4px;">0 shipments</div>
        </div>
        <!-- Total Combined -->
        <div
            style="background:linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); padding:20px; border-radius:16px; box-shadow:0 10px 15px -3px rgba(79,70,229,0.3); color:#fff;">
            <div
                style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.8); text-transform:uppercase; margin-bottom:8px;">
                Total Keseluruhan</div>
            <div id="total_grand_income" style="font-size:28px; font-weight:900;">Rp 0</div>
            <div style="font-size:12px; color:rgba(255,255,255,0.7); margin-top:4px;">Gabungan Semua Layanan</div>
        </div>
    </div>

    <!-- DETAILS TABLE -->
    <div
        style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:700; color:#334155;"
            id="report_details_title">
            Detail Transaksi</div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f1f5f9; text-align:left;">
                        <th
                            style="padding:12px 20px; color:#64748b; font-weight:600; text-transform:uppercase; font-size:10px;">
                            Tanggal</th>
                        <th id="th_name"
                            style="padding:12px 20px; color:#64748b; font-weight:600; text-transform:uppercase; font-size:10px;">
                            Nama</th>
                        <th id="th_phone"
                            style="padding:12px 20px; color:#64748b; font-weight:600; text-transform:uppercase; font-size:10px;">
                            No. HP</th>
                        <th id="th_route"
                            style="padding:12px 20px; color:#64748b; font-weight:600; text-transform:uppercase; font-size:10px;">
                            Rute / Unit</th>
                        <th id="th_discount"
                            style="padding:12px 20px; color:#64748b; font-weight:600; text-transform:uppercase; font-size:10px; text-align:right;">
                            Potongan</th>
                        <th
                            style="padding:12px 20px; color:#64748b; font-weight:600; text-transform:uppercase; font-size:10px; text-align:right;">
                            Total</th>
                    </tr>
                </thead>
                <tbody id="report_details_tbody">
                    <tr>
                        <td colspan="5" style="padding:30px; text-align:center; color:#94a3b8;">Silahkan klik "Generate
                            Report" untuk melihat data.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnGenerate = document.getElementById('btnGenerateReport');
        if (btnGenerate) {
            btnGenerate.onclick = async function () {
                const start = document.getElementById('report_start_date').value;
                const end = document.getElementById('report_end_date').value;

                if (!start || !end) {
                    alert('Pilih tanggal mulai dan akhir');
                    return;
                }

                btnGenerate.disabled = true;
                btnGenerate.innerHTML = '<span class="spinner-small" style="margin-right:8px"></span> Generating...';

                try {
                    const type = document.getElementById('report_type_select').value;
                    const res = await fetch(`admin.php?action=reportsPage&start_date=${start}&end_date=${end}&type=${type}`);
                    const data = await res.json();

                    if (data.success) {
                        // Show Container
                        document.getElementById('report_summary_container').style.display = 'grid';

                        // Update Summary
                        document.getElementById('total_reguler_income').textContent = 'Rp ' + parseInt(data.reguler_total).toLocaleString('id-ID');
                        document.getElementById('total_reguler_count').textContent = data.reguler_count + ' bookings';
                        document.getElementById('total_reguler_discount').textContent = 'Rp ' + parseInt(data.reguler_discount_total || 0).toLocaleString('id-ID');

                        document.getElementById('total_carter_income').textContent = 'Rp ' + parseInt(data.carter_total).toLocaleString('id-ID');
                        document.getElementById('total_carter_count').textContent = data.carter_count + ' charters';

                        document.getElementById('total_luggage_income').textContent = 'Rp ' + parseInt(data.luggage_total || 0).toLocaleString('id-ID');
                        document.getElementById('total_luggage_count').textContent = (data.luggage_count || 0) + ' shipments';

                        const grandTotal = parseInt(data.reguler_total) + parseInt(data.carter_total) + parseInt(data.luggage_total || 0);
                        document.getElementById('total_grand_income').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

                        // Toggle Card Visibility & Table Headers
                        const thName = document.getElementById('th_name');
                        const thPhone = document.getElementById('th_phone');
                        const thRoute = document.getElementById('th_route');

                        if (type === 'reguler') {
                            document.getElementById('card_reguler_income').style.display = 'block';
                            document.getElementById('card_reguler_discount').style.display = 'block';
                            document.getElementById('card_carter_income').style.display = 'none';
                            document.getElementById('card_luggage_income').style.display = 'none';
                            document.getElementById('th_discount').style.display = 'table-cell';
                            thName.textContent = 'Nama';
                            thPhone.textContent = 'No. HP';
                            thRoute.textContent = 'Rute / Unit';
                        } else if (type === 'bagasi') {
                            document.getElementById('card_reguler_income').style.display = 'none';
                            document.getElementById('card_reguler_discount').style.display = 'none';
                            document.getElementById('card_carter_income').style.display = 'none';
                            document.getElementById('card_luggage_income').style.display = 'block';
                            document.getElementById('th_discount').style.display = 'none';
                            thName.textContent = 'Pengirim';
                            thPhone.textContent = 'Penerima';
                            thRoute.textContent = 'Layanan';
                        } else {
                            document.getElementById('card_reguler_income').style.display = 'none';
                            document.getElementById('card_reguler_discount').style.display = 'none';
                            document.getElementById('card_carter_income').style.display = 'block';
                            document.getElementById('card_luggage_income').style.display = 'none';
                            document.getElementById('th_discount').style.display = 'none';
                            thName.textContent = 'Nama';
                            thPhone.textContent = 'No. HP';
                            thRoute.textContent = 'Rute / Unit';
                        }

                        // Update Title
                        let title = 'Detail Penumpang (Reguler)';
                        if (type === 'carter') title = 'Detail Penyewa (Carter)';
                        if (type === 'bagasi') title = 'Detail Pengiriman (Bagasi)';
                        document.getElementById('report_details_title').textContent = title;

                        // Update Details
                        const tbody = document.getElementById('report_details_tbody');
                        if (data.details && data.details.length > 0) {
                            let html = '';
                            data.details.forEach(item => {
                                const discCol = type === 'reguler' ? `<td style="padding:12px 20px; text-align:right; color:#e11d48;">Rp ${parseInt(item.discount || 0).toLocaleString('id-ID')}</td>` : '';
                                
                                html += `<tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:12px 20px; color:#64748b;">${item.tanggal}</td>
                                <td style="padding:12px 20px; font-weight:600; color:#1e293b;">${item.name}</td>
                                <td style="padding:12px 20px; color:#475569;">${item.phone}</td>
                                <td style="padding:12px 20px; color:#1e293b;">${item.rute}</td>
                                ${discCol}
                                <td style="padding:12px 20px; text-align:right; font-weight:700; color:#10b981;">Rp ${parseInt(item.final_price).toLocaleString('id-ID')}</td>
                            </tr>`;
                            });
                            tbody.innerHTML = html;
                        } else {
                            tbody.innerHTML = `<tr><td colspan="${type === 'reguler' ? 6 : 5}" style="padding:30px; text-align:center; color:#94a3b8;">Tidak ada data pada periode ini.</td></tr>`;
                        }
                    } else {
                        alert('Gagal mengambil data laporan: ' + (data.error || 'Unknown error'));
                        document.getElementById('report_summary_container').style.display = 'none';
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan koneksi');
                } finally {
                    btnGenerate.disabled = false;
                    btnGenerate.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg> Generate';
                    if (document.getElementById('btnExportCsv')) document.getElementById('btnExportCsv').style.display = 'flex';
                }
            };
        }

        const btnExport = document.getElementById('btnExportCsv');
        if (btnExport) {
            btnExport.onclick = function () {
                const start = document.getElementById('report_start_date').value;
                const end = document.getElementById('report_end_date').value;
                const type = document.getElementById('report_type_select').value;

                if (!start || !end) {
                    alert('Pilih tanggal mulai dan akhir');
                    return;
                }

                window.location.href = `admin.php?action=exportReportCsv&start_date=${start}&end_date=${end}&type=${type}`;
            };
        }
    });
</script>