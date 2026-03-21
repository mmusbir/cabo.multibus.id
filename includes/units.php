<!-- UNITS -->
<section id="units" class="card">
    <h3>Manajemen Unit Kendaraan</h3>
    <?php
    $unit_nopol = $edit_unit['nopol'] ?? '';
    $unit_merek = $edit_unit['merek'] ?? '';
    $unit_type = $edit_unit['type'] ?? '';
    $unit_category = 'Minibus';
    $unit_tahun = $edit_unit['tahun'] ?? '';
    $unit_kapasitas = $edit_unit['kapasitas'] ?? '';
    $unit_status = $edit_unit['status'] ?? 'Aktif';
    $unit_id = $edit_unit['id'] ?? 0;
    ?>
    <!-- FORM MODERNE -->
    <div class="modern-form-card">
        <div
            style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
            <span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:6px;font-size:12px">PLUS</span>
            <?php echo $unit_id > 0 ? 'Edit Unit Kendaraan' : 'Tambah Unit Kendaraan'; ?>
        </div>
        <form method="post">
            <?php if ($unit_id > 0)
                echo '<input type="hidden" name="unit_id" value="' . intval($unit_id) . '">'; ?>

            <div class="modern-form-grid">
                <!-- NOPOL -->
                <div class="input-group">
                    <span class="input-group-icon">🚌</span>
                    <input name="nopol" class="modern-input" placeholder="Nama Kendaraan" required
                        value="<?php echo htmlspecialchars($unit_nopol); ?>">
                </div>

                <!-- MEREK -->
                <div class="input-group">
                    <span class="input-group-icon">🚐</span>
                    <input name="merek" class="modern-input" placeholder="Merek (cth: Toyota)" required
                        value="<?php echo htmlspecialchars($unit_merek); ?>">
                </div>

                <!-- TYPE -->
                <div class="input-group">
                    <span class="input-group-icon">📄</span>
                    <input name="type" class="modern-input" placeholder="Type (cth: Hiace)" required
                        value="<?php echo htmlspecialchars($unit_type); ?>">
                </div>

                <input type="hidden" name="category" value="Minibus">

                <!-- TAHUN -->
                <div class="input-group">
                    <span class="input-group-icon">📅</span>
                    <input name="tahun" type="number" class="modern-input" placeholder="Tahun" required
                        value="<?php echo htmlspecialchars($unit_tahun); ?>">
                </div>

                <!-- KAPASITAS -->
                <div class="input-group">
                    <span class="input-group-icon">💺</span>
                    <input name="kapasitas" type="number" class="modern-input" placeholder="Kapasitas Kursi" required
                        value="<?php echo htmlspecialchars($unit_kapasitas); ?>">
                </div>

                <!-- STATUS -->
                <div class="input-group">
                    <span class="input-group-icon">🔋</span>
                    <select name="status" class="modern-select" required>
                        <option value="Aktif" <?php echo $unit_status === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Nonaktif" <?php echo $unit_status === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif
                        </option>
                    </select>
                </div>

                <!-- ACTIONS -->
                <div style="display:flex;gap:8px;grid-column:1/-1;justify-content:flex-end;margin-top:8px">
                    <?php if ($unit_id > 0) {
                        echo '<a href="admin.php#units" class="btn-modern secondary" style="text-align:center">Batal</a>';
                    } ?>
                    <button name="<?php echo $unit_id > 0 ? 'update_unit' : 'save_unit'; ?>" class="btn-modern">
                        💾 <?php echo $unit_id > 0 ? 'Update Unit' : 'Simpan Unit'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <!-- SEARCH BAR -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;margin-bottom:12px">
        <div class="search-bar-modern">
            <input type="text" id="filter_unit_input" class="search-input-modern" placeholder="Cari unit atau nopol...">
            <button type="button" class="search-btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </div>

    <div class="booking-cards-grid" id="units_grid" style="margin-top:12px;">
        <?php foreach ($units as $u): ?>
            <div class="admin-card-compact">
                <div class="acc-header">
                    <div class="acc-title"><?= htmlspecialchars($u['nopol']) ?></div>
                    <div class="acc-id"><?= htmlspecialchars($u['merek']) ?></div>
                </div>
                <div class="acc-body">
                    <div class="acc-row">
                        <div class="acc-label">Type</div>
                        <div class="acc-val"><?= htmlspecialchars($u['type']) ?></div>
                    </div>
                    <div class="acc-row">
                        <div class="acc-label">Tahun</div>
                        <div class="acc-val"><?= htmlspecialchars($u['tahun']) ?></div>
                    </div>
                    <div class="acc-row">
                        <div class="acc-label">Kapasitas</div>
                        <div class="acc-val"><?= htmlspecialchars($u['kapasitas']) ?> kursi</div>
                    </div>
                    <div class="acc-row">
                        <div class="acc-label">Status</div>
                        <div class="acc-val"><?= htmlspecialchars($u['status']) ?></div>
                    </div>
                </div>
                <div class="acc-actions">
                    <a href="admin.php?edit_unit=<?= $u['id'] ?>#units" class="acc-btn">Edit</a>
                    <button class="acc-btn edit-layout-btn" data-id="<?= $u['id'] ?>"
                        data-nopol="<?= htmlspecialchars($u['nopol']) ?>"
                        data-kapasitas="<?= htmlspecialchars($u['kapasitas']) ?>">Layout</button>
                    <form method="post" style="display:inline"
                        onsubmit="event.preventDefault(); customConfirm('Hapus unit ini?', () => this.submit(), 'Hapus Unit', 'danger');">
                        <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
                        <button type="submit" name="delete_unit" class="acc-btn danger" style="width:100%">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Popup Layout Edit -->
<div class="popup-bg" id="popup-bg">
    <div class="popup-content" id="popup-content">
        <span class="popup-close" id="popup-close">&times;</span>
        <h3 id="popup-title">Edit Layout Kursi</h3>
        <div class="layout-info"
            style="margin:8px 0;padding:8px 12px;background:#f3f4f6;border-radius:6px;font-size:13px;">
            <div><strong>Kendaraan:</strong> <span id="nopol-display"></span></div>
            <div><strong>Kapasitas:</strong> <span id="kapasitas-display"></span> kursi</div>
            <div><strong>Kursi Aktif:</strong> <span id="current-seats-display">0</span></div>
        </div>

        <div class="mode-selector"
            style="margin:12px 0;padding:8px;background:#fff;border-radius:6px;border:1px solid #e5e7eb;">
            <label style="margin-right:16px;cursor:pointer;">
                <input type="radio" name="addMode" value="seat" checked>
                <span style="margin-left:4px;">Tambah Kursi</span>
            </label>
            <label style="cursor:pointer;">
                <input type="radio" name="addMode" value="bagasi">
                <span style="margin-left:4px;">Tambah Bagasi</span>
            </label>
        </div>

        <div class="layout-hint" style="font-size:12px;color:#6b7280;margin-bottom:8px;">
            Klik <strong>+</strong> untuk menambah kursi/bagasi. Klik kursi untuk menghapus.
        </div>

        <div id="seat-grid" class="seat-grid"></div>

        <div class="actions" style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button id="add-row-btn" class="inline-small">+ Baris</button>
            <button id="remove-row-btn" class="inline-small" style="color:#dc3545;">- Baris</button>
            <button id="add-col-btn" class="inline-small">+ Kolom</button>
            <button id="remove-col-btn" class="inline-small" style="color:#dc3545;">- Kolom</button>
        </div>

        <div class="actions" style="display:flex;gap:12px;margin-top:12px;">
            <button id="reset-btn" class="inline-small">Reset Layout</button>
            <button id="save-btn" class="btn-bright" style="flex:1;background:#2563eb;color:#fff;">Simpan
                Layout</button>
        </div>

        <div id="layout-msg" style="margin-top:12px;font-size:14px;padding:8px;border-radius:6px;display:none;"></div>
    </div>
</div>

<style>
    .popup-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.4);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .popup-content {
        background: #fff;
        padding: 24px;
        border-radius: 12px;
        min-width: 320px;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    .popup-close {
        position: absolute;
        top: 12px;
        right: 18px;
        font-size: 28px;
        cursor: pointer;
        color: #6b7280;
        line-height: 1;
    }

    .popup-close:hover {
        color: #374151;
    }

    .seat-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
    }

    .grid-row {
        display: grid;
        grid-template-columns: repeat(var(--cols, 3), 1fr);
        gap: 8px;
    }

    .cell {
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        background: #e5e7eb;
        transition: all 0.2s ease;
        user-select: none;
    }

    .cell.fixed {
        cursor: not-allowed;
        opacity: 0.9;
    }

    .cell.driver {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #000;
        border: 2px solid #d97706;
    }

    .cell.bagasi {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: #fff;
        border: 2px solid #374151;
    }

    .cell.clickable {
        cursor: pointer;
        background: #ffffff;
        border: 2px dashed #d1d5db;
    }

    .cell.clickable:hover {
        background: #e0e7ef;
        border-color: #158303;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(21, 131, 3, 0.2);
    }

    .cell.seat {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
        border: 2px solid #3b82f6;
        cursor: pointer;
    }

    .cell.seat:hover {
        background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
    }

    .cell.bagasi-custom {
        background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%);
        color: #1f2937;
        border: 2px solid #6b7280;
        cursor: pointer;
    }

    .cell.bagasi-custom:hover {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        transform: translateY(-2px);
    }

    @media (max-width: 600px) {
        .popup-content {
            padding: 16px;
            width: 95vw;
            max-height: 85vh;
        }

        .seat-grid {
            padding: 12px;
            gap: 6px;
        }

        .cell {
            min-height: 42px;
            font-size: 12px;
        }

        .grid-row {
            gap: 6px;
        }
    }
</style>

<script>
    document.getElementById('filter_unit_input')?.addEventListener('input', function () {
        const val = this.value.toLowerCase();
        const cards = document.querySelectorAll('#units_grid .admin-card-compact');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(val) ? 'flex' : 'none';
        });
    });
    // Layout State
    let currentUnitId = null;
    let currentNopol = '';
    let currentKapasitas = 12;
    let layout = [];

    // Initialize default layout (3 columns) - matches index.html display order
    function initLayout() {
        const grid = [];

        // Row 1: Seat 1 + Seat 2 + Driver (Driver at top right)
        grid.push([
            { type: 'seat', label: '1', seatNumber: 1, fixed: false },
            { type: 'seat', label: '2', seatNumber: 2, fixed: false },
            { type: 'driver', label: 'Driver', fixed: true }
        ]);

        // Middle rows: 3 columns each (4 rows)
        for (let i = 0; i < 4; i++) {
            grid.push([
                { type: 'empty', label: '', fixed: false },
                { type: 'empty', label: '', fixed: false },
                { type: 'empty', label: '', fixed: false }
            ]);
        }

        // Last row: Bagasi (3 cells but mark as bagasi)
        grid.push([
            { type: 'bagasi', label: 'Bagasi', fixed: true, colspan: 3 },
            { type: 'bagasi', label: '', fixed: true, hidden: true },
            { type: 'bagasi', label: '', fixed: true, hidden: true }
        ]);

        return grid;
    }

    // Render the seat grid
    function renderLayout() {
        const grid = document.getElementById('seat-grid');
        grid.innerHTML = '';

        layout.forEach((row, rowIdx) => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'grid-row';
            const colCount = row.length;
            rowDiv.style.setProperty('--cols', colCount);

            // Check if this is bagasi row (full width or spanning)
            const isBagasiRow = row.length > 0 && row[0].type === 'bagasi' && row[0].colspan > 0;

            if (isBagasiRow) {
                // Render single bagasi cell spanning multiple columns
                const cellDiv = document.createElement('div');
                cellDiv.className = 'cell bagasi fixed';
                cellDiv.style.gridColumn = 'span ' + row[0].colspan;
                cellDiv.textContent = 'BAGASI';
                rowDiv.appendChild(cellDiv);
            } else {
                row.forEach((cell, colIdx) => {
                    if (cell.hidden) return;

                    const cellDiv = document.createElement('div');
                    cellDiv.className = 'cell';

                    if (cell.fixed) {
                        cellDiv.classList.add('fixed');
                    }

                    if (cell.type === 'driver') {
                        cellDiv.classList.add('driver');
                        cellDiv.innerHTML = '🚗 Driver';
                    } else if (cell.type === 'empty') {
                        cellDiv.classList.add('clickable');
                        cellDiv.textContent = '+';
                        cellDiv.onclick = () => addItem(rowIdx, colIdx);
                    } else if (cell.type === 'seat') {
                        cellDiv.classList.add('seat');
                        cellDiv.textContent = cell.label;
                        cellDiv.onclick = () => removeItem(rowIdx, colIdx);
                    } else if (cell.type === 'bagasi-custom') {
                        cellDiv.classList.add('bagasi-custom');
                        cellDiv.textContent = '📦 Bagasi';
                        cellDiv.onclick = () => removeItem(rowIdx, colIdx);
                    }

                    rowDiv.appendChild(cellDiv);
                });
            }

            grid.appendChild(rowDiv);
        });

        updateSeatCount();
    }

    // Get current seat count
    function getSeatCount() {
        let count = 0;
        layout.forEach(row => {
            row.forEach(cell => {
                if (cell.type === 'seat') count++;
            });
        });
        return count;
    }

    // Update seat count display
    function updateSeatCount() {
        const count = getSeatCount();
        document.getElementById('current-seats-display').textContent = count;
    }

    // Renumber all seats sequentially
    function renumberSeats() {
        let seatNum = 1;
        layout.forEach(row => {
            row.forEach(cell => {
                if (cell.type === 'seat') {
                    cell.label = String(seatNum);
                    cell.seatNumber = seatNum;
                    seatNum++;
                }
            });
        });
    }

    // Add item to cell
    function addItem(rowIdx, colIdx) {
        const mode = document.querySelector('input[name="addMode"]:checked').value;
        const cell = layout[rowIdx][colIdx];

        if (isViewMode) return;
        if (cell.fixed || cell.type !== 'empty') return;

        if (mode === 'seat') {
            const seatCount = getSeatCount();
            if (seatCount >= currentKapasitas) {
                showMessage(`Maksimal ${currentKapasitas} kursi sesuai kapasitas unit`, 'error');
                return;
            }
            cell.type = 'seat';
            cell.label = String(seatCount + 1);
            cell.seatNumber = seatCount + 1;
        } else if (mode === 'bagasi') {
            cell.type = 'bagasi-custom';
            cell.label = 'Bagasi';
        }

        renderLayout();
    }

    // Remove item from cell
    function removeItem(rowIdx, colIdx) {
        const cell = layout[rowIdx][colIdx];

        if (isViewMode) return;
        if (cell.fixed) return;

        customConfirm('Hapus item ini?', () => {
            cell.type = 'empty';
            cell.label = '';
            delete cell.seatNumber;

            // Renumber remaining seats
            renumberSeats();
            renderLayout();
        }, 'Hapus Item', 'danger');
    }

    // Add new row
    document.getElementById('add-row-btn').onclick = function () {
        // Find the bagasi row index
        let bagasiRowIdx = layout.findIndex(row => row[0].type === 'bagasi' && row[0].colspan >= 3);

        const colCount = layout[0]?.length || 3;
        const newRow = [];
        for (let i = 0; i < colCount; i++) {
            newRow.push({ type: 'empty', label: '', fixed: false });
        }

        if (bagasiRowIdx >= 0) {
            layout.splice(bagasiRowIdx, 0, newRow);
        } else {
            layout.push(newRow);
        }

        renderLayout();
        showMessage('Baris baru ditambahkan', 'success');
    };

    // Remove last editable row
    document.getElementById('remove-row-btn').onclick = function () {
        // Find last editable row (not bagasi, not first row with driver)
        let lastEditableIdx = -1;
        for (let i = layout.length - 1; i >= 1; i--) {
            const row = layout[i];
            const isBagasi = row[0].type === 'bagasi';
            const hasDriver = row.some(c => c.type === 'driver');
            if (!isBagasi && !hasDriver) {
                lastEditableIdx = i;
                break;
            }
        }

        if (lastEditableIdx > 0) {
            customConfirm('Hapus baris terakhir?', () => {
                layout.splice(lastEditableIdx, 1);
                renumberSeats();
                renderLayout();
                showMessage('Baris dihapus', 'success');
            }, 'Hapus Baris', 'danger');
        } else {
            showMessage('Tidak ada baris yang bisa dihapus', 'error');
        }
    };

    // Add new column
    document.getElementById('add-col-btn').onclick = function () {
        layout.forEach(row => {
            const isBagasi = row[0].type === 'bagasi';
            if (isBagasi) {
                row[0].colspan = (row[0].colspan || 3) + 1;
                row.push({ type: 'bagasi', label: '', fixed: true, hidden: true });
            } else {
                row.push({ type: 'empty', label: '', fixed: false });
            }
        });
        renderLayout();
        showMessage('Kolom baru ditambahkan', 'success');
    };

    // Remove column
    document.getElementById('remove-col-btn').onclick = function () {
        const colCount = layout[0]?.length || 0;
        if (colCount <= 1) {
            showMessage('Minimal harus ada 1 kolom', 'error');
            return;
        }

        customConfirm('Hapus kolom terakhir? Item di kolom tersebut akan hilang.', () => {
            layout.forEach(row => {
                const isBagasi = row[0].type === 'bagasi';
                if (isBagasi) {
                    row[0].colspan = Math.max(1, (row[0].colspan || 3) - 1);
                }
                row.pop();
            });
            renumberSeats();
            renderLayout();
            showMessage('Kolom dihapus', 'success');
        }, 'Hapus Kolom', 'danger');
    };

    // Reset layout
    document.getElementById('reset-btn').onclick = function () {
        customConfirm('Reset layout ke default? Semua perubahan akan hilang.', () => {
            layout = initLayout();
            renderLayout();
            showMessage('Layout direset', 'success');
        }, 'Reset Layout', 'danger');
    };

    // Save layout
    document.getElementById('save-btn').onclick = function () {
        const seatCount = getSeatCount();
        if (seatCount === 0) {
            showMessage('Tambahkan minimal 1 kursi sebelum menyimpan', 'error');
            return;
        }

        // Validasi lagi sebelum simpan
        if (seatCount > currentKapasitas) {
            showMessage(`Maksimal ${currentKapasitas} kursi!`, 'error');
            return;
        }

        fetch('admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                save_layout: 1,
                unit_id: currentUnitId,
                layout: layout
            })
        })
            .then(res => res.json())
            .then(js => {
                if (js.success) {
                    showMessage('Layout berhasil disimpan! (' + seatCount + ' kursi terpasang dari kapasitas ' + currentKapasitas + ')', 'success');
                } else {
                    showMessage('Gagal menyimpan layout: ' + (js.error || ''), 'error');
                }
            })
            .catch((err) => {
                showMessage('Gagal menyimpan layout', 'error');
                console.error(err);
            });
    };

    // Show message
    function showMessage(msg, type) {
        const msgDiv = document.getElementById('layout-msg');
        msgDiv.textContent = msg;
        msgDiv.style.display = 'block';
        msgDiv.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
        msgDiv.style.color = type === 'success' ? '#065f46' : '#991b1b';
        msgDiv.style.border = type === 'success' ? '1px solid #6ee7b7' : '1px solid #fca5a5';

        setTimeout(() => {
            msgDiv.style.display = 'none';
        }, 3000);
    }

    // Open popup
    function openLayoutPopup(unitId, nopol, kapasitas, isSchedule = false) {
        currentUnitId = unitId;
        currentNopol = nopol;
        currentKapasitas = parseInt(kapasitas) || 12;

        const titlePrefix = isSchedule ? 'View Layout Jadwal: ' : 'Edit Layout: ';
        document.getElementById('popup-title').textContent = titlePrefix + nopol;
        document.getElementById('nopol-display').textContent = nopol;
        document.getElementById('kapasitas-display').textContent = kapasitas ? kapasitas : '-';

        // Hide edit actions if viewing schedule
        const editActions = document.querySelectorAll('.popup-content .actions, .popup-content .mode-selector, .popup-content .layout-hint');
        editActions.forEach(el => el.style.display = isSchedule ? 'none' : 'flex');

        // Fetch existing layout or use default
        const url = isSchedule ? 'admin.php?get_layout=' + unitId + '&type=schedule' : 'admin.php?get_layout=' + unitId;
        fetch(url)
            .then(res => res.json())
            .then(js => {
                if (js.success && js.layout && Array.isArray(js.layout) && js.layout.length > 0) {
                    layout = js.layout;
                } else {
                    layout = initLayout();
                }
                renderLayout();
            })
            .catch(() => {
                layout = initLayout();
                renderLayout();
            });

        document.getElementById('popup-bg').style.display = 'flex';
    }

    // Close popup
    document.getElementById('popup-close').onclick = function () {
        document.getElementById('popup-bg').style.display = 'none';
        isViewMode = false;
        currentUnitId = null;
    };

    document.getElementById('popup-bg').onclick = function (e) {
        if (e.target.id === 'popup-bg') {
            document.getElementById('popup-bg').style.display = 'none';
            isViewMode = false;
            currentUnitId = null;
        }
    };

    let isViewMode = false;

    // Attach click handlers to layout buttons (delegated)
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('edit-layout-btn')) {
            const id = e.target.dataset.id;
            const nopol = e.target.dataset.nopol;
            const kapasitas = e.target.dataset.kapasitas;
            isViewMode = false;
            openLayoutPopup(id, nopol, kapasitas, false);
        } else if (e.target.classList.contains('view-schedule-layout')) {
            const id = e.target.dataset.id;
            const rute = e.target.dataset.rute;
            isViewMode = true;
            // For schedule, we use ID as ID, and Rute as "Nopol" display
            openLayoutPopup(id, rute, null, true);
        }
    });
</script>