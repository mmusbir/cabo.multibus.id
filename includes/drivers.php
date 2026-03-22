<!-- DRIVERS -->
<section id="drivers" class="card">
    <div class="admin-section-header">
        <div>
            <h3 class="admin-section-title">Manajemen Data Driver</h3>
            <p class="admin-section-subtitle">Kelola identitas driver dan unit kendaraan yang terhubung.</p>
        </div>
    </div>
    <?php
    $edit_driver = null;
    if (isset($_GET['edit_driver'])) {
        $id = intval($_GET['edit_driver']);
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT id,nama,phone,unit_id FROM drivers WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $edit_driver = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    $driver_nama = $edit_driver['nama'] ?? '';
    $driver_phone = $edit_driver['phone'] ?? '';
    $driver_unit_id = $edit_driver['unit_id'] ?? '';
    $driver_id = $edit_driver['id'] ?? 0;

    $units_list = [];
    $res = $conn->query("SELECT id, nopol, merek FROM units ORDER BY nopol");
    while ($u = $res->fetch(PDO::FETCH_ASSOC)) {
        $units_list[] = $u;
    }
    ?>

    <div class="modern-form-card admin-bs-panel">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="admin-bs-chip">Form</span>
            <?php echo $driver_id > 0 ? 'Edit Driver' : 'Tambah Driver'; ?>
        </div>
        <form method="post">
            <?php if ($driver_id > 0)
                echo '<input type="hidden" name="driver_id" value="' . intval($driver_id) . '">'; ?>

            <div class="modern-form-grid admin-bs-form-grid">
                <div class="input-group admin-bs-col-6">
                    <label class="admin-bs-input-label">Nama Driver</label>
                    <input name="driver_nama" class="modern-input form-control" placeholder="Nama Driver" required
                        value="<?php echo htmlspecialchars($driver_nama); ?>">
                </div>
                <div class="input-group admin-bs-col-6">
                    <label class="admin-bs-input-label">No. Telepon</label>
                    <input name="driver_phone" class="modern-input form-control" placeholder="No. HP" required
                        value="<?php echo htmlspecialchars($driver_phone); ?>">
                </div>
                <div class="input-group admin-bs-col-6">
                    <label class="admin-bs-input-label">Unit Kendaraan</label>
                    <select name="driver_unit_id" class="modern-select form-select" required>
                        <option value="">-- Pilih Unit --</option>
                        <?php foreach ($units_list as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($driver_unit_id == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nopol'] . ' - ' . $u['merek']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-bs-actions admin-bs-col-12">
                    <?php if ($driver_id > 0) {
                        echo '<a href="admin.php#drivers" class="btn btn-outline-secondary btn-modern secondary">Batal</a>';
                    } ?>
                    <button name="save_driver" class="btn btn-primary btn-modern">
                        <?php echo $driver_id > 0 ? 'Update Driver' : 'Simpan Driver'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-bs-toolbar">
        <div class="search-bar-modern admin-bs-search">
            <input type="text" id="filter_driver_input" class="search-input-modern"
                placeholder="Cari nama atau no. HP driver...">
            <button type="button" class="search-btn-icon" aria-label="Cari driver">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </div>

    <div class="booking-cards-grid admin-bs-card-grid admin-list-grid-tight" id="drivers_grid">
        <?php
        $drivers = [];
        $res = $conn->query("SELECT d.*, u.nopol, u.merek FROM drivers d LEFT JOIN units u ON d.unit_id = u.id ORDER BY d.nama");
        while ($d = $res->fetch(PDO::FETCH_ASSOC)) {
            $drivers[] = $d;
        }
        foreach ($drivers as $d):
            $unit_display = $d['nopol'] ? htmlspecialchars($d['nopol'] . ' - ' . $d['merek']) : '-';
            ?>
            <div class="admin-card-compact">
                <div class="acc-header">
                    <div class="acc-title">
                        <?= htmlspecialchars($d['nama']) ?>
                    </div>
                    <div class="acc-id">#
                        <?= intval($d['id']) ?>
                    </div>
                </div>
                <div class="acc-body">
                    <div class="acc-row">
                        <div class="acc-label">Telepon</div>
                        <div class="acc-val">
                            <?= htmlspecialchars($d['phone']) ?>
                        </div>
                    </div>
                    <div class="acc-row">
                        <div class="acc-label">Unit</div>
                        <div class="acc-val">
                            <?= $unit_display ?>
                        </div>
                    </div>
                </div>
                <div class="acc-actions">
                    <a href="admin.php?edit_driver=<?= $d['id'] ?>#drivers" class="acc-btn">Edit</a>
                    <a href="admin.php?delete_driver=<?= $d['id'] ?>#drivers" class="acc-btn danger"
                        onclick="event.preventDefault(); customConfirm('Hapus driver ini?', () => { window.location.href = 'admin.php?delete_driver=<?= $d['id'] ?>#drivers'; }, 'Hapus Driver', 'danger')">Hapus</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        document.getElementById('filter_driver_input')?.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            const cards = document.querySelectorAll('#drivers_grid .admin-card-compact');
            cards.forEach((card) => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(val) ? 'flex' : 'none';
            });
        });
    </script>
</section>
