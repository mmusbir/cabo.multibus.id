<!-- UNITS -->
<section id="units" class="card">
    <div class="admin-section-header">
        <div>
            <h3 class="admin-section-title">Manajemen Unit Kendaraan</h3>
            <p class="admin-section-subtitle">Kelola data armada, kapasitas kursi, status kendaraan, dan akses layout.</p>
        </div>
    </div>
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

    <div class="modern-form-card admin-bs-panel">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="admin-bs-chip">Form</span>
            <?php echo $unit_id > 0 ? 'Edit Unit Kendaraan' : 'Tambah Unit Kendaraan'; ?>
        </div>
        <form method="post">
            <?php if ($unit_id > 0)
                echo '<input type="hidden" name="unit_id" value="' . intval($unit_id) . '">'; ?>

            <div class="modern-form-grid admin-bs-form-grid">
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Nama Kendaraan / Nopol</label>
                    <input name="nopol" class="modern-input form-control" placeholder="Nama Kendaraan" required
                        value="<?php echo htmlspecialchars($unit_nopol); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Merek</label>
                    <input name="merek" class="modern-input form-control" placeholder="Contoh: Toyota" required
                        value="<?php echo htmlspecialchars($unit_merek); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Type</label>
                    <input name="type" class="modern-input form-control" placeholder="Contoh: Hiace" required
                        value="<?php echo htmlspecialchars($unit_type); ?>">
                </div>

                <input type="hidden" name="category" value="Minibus">

                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Tahun</label>
                    <input name="tahun" type="number" class="modern-input form-control" placeholder="Tahun" required
                        value="<?php echo htmlspecialchars($unit_tahun); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Kapasitas Kursi</label>
                    <input name="kapasitas" type="number" class="modern-input form-control" placeholder="Kapasitas Kursi" required
                        value="<?php echo htmlspecialchars($unit_kapasitas); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Status</label>
                    <select name="status" class="modern-select form-select" required>
                        <option value="Aktif" <?php echo $unit_status === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Nonaktif" <?php echo $unit_status === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="admin-bs-actions admin-bs-col-12">
                    <?php if ($unit_id > 0) {
                        echo '<a href="admin.php#units" class="btn btn-outline-secondary btn-modern secondary">Batal</a>';
                    } ?>
                    <button name="<?php echo $unit_id > 0 ? 'update_unit' : 'save_unit'; ?>" class="btn btn-primary btn-modern">
                        <?php echo $unit_id > 0 ? 'Update Unit' : 'Simpan Unit'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-bs-toolbar">
        <div class="search-bar-modern admin-bs-search">
            <input type="text" id="filter_unit_input" class="search-input-modern" placeholder="Cari unit atau nopol...">
            <button type="button" class="search-btn-icon" aria-label="Cari unit">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </div>

    <div class="booking-cards-grid admin-bs-card-grid admin-list-grid-tight" id="units_grid">
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
                    <form method="post" class="admin-inline-form"
                        onsubmit="event.preventDefault(); customConfirm('Hapus unit ini?', () => this.submit(), 'Hapus Unit', 'danger');">
                        <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
                        <button type="submit" name="delete_unit" class="acc-btn danger w-100">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<!-- Popup Layout Edit -->
<div class="popup-bg layout-popup" id="popup-bg">
    <div class="popup-content layout-popup-card" id="popup-content">
        <button type="button" class="popup-close layout-popup-close" id="popup-close" aria-label="Tutup popup">&times;</button>
        <h3 id="popup-title">Edit Layout Kursi</h3>
        <div class="layout-info layout-popup-info">
            <div class="layout-popup-meta"><strong>Kendaraan:</strong> <span id="nopol-display"></span></div>
            <div class="layout-popup-meta"><strong>Kapasitas:</strong> <span id="kapasitas-display"></span> kursi</div>
            <div class="layout-popup-meta"><strong>Kursi Aktif:</strong> <span id="current-seats-display">0</span></div>
        </div>

        <div class="mode-selector layout-mode-selector">
            <label class="layout-mode-option">
                <input type="radio" name="addMode" value="seat" checked>
                <span>Tambah Kursi</span>
            </label>
            <label class="layout-mode-option">
                <input type="radio" name="addMode" value="bagasi">
                <span>Tambah Bagasi</span>
            </label>
        </div>

        <div class="layout-hint">
            Klik <strong>+</strong> untuk menambah kursi/bagasi. Klik kursi untuk menghapus.
        </div>

        <div id="seat-grid" class="seat-grid"></div>

        <div class="actions layout-grid-actions">
            <button type="button" id="add-row-btn" class="inline-small">+ Baris</button>
            <button type="button" id="remove-row-btn" class="inline-small">- Baris</button>
            <button type="button" id="add-col-btn" class="inline-small">+ Kolom</button>
            <button type="button" id="remove-col-btn" class="inline-small">- Kolom</button>
        </div>

        <div class="actions layout-grid-actions layout-grid-actions-primary">
            <button type="button" id="reset-btn" class="inline-small">Reset Layout</button>
            <button type="button" id="save-btn" class="btn-bright layout-primary-action">Simpan
                Layout</button>
        </div>

        <div id="layout-msg" class="layout-message"></div>
    </div>
</div>

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
                        cellDiv.textContent = 'Driver';
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
                        cellDiv.textContent = 'Bagasi';
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

