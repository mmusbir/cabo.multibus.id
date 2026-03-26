<!-- SEGMENTS -->
<section id="segments" class="card" style="display:none;">
    <div class="admin-section-header">
        <div>
            <h3 class="admin-section-title">Manajemen Segment</h3>
            <p class="admin-section-subtitle">Atur rute turunan, harga, dan jam pickup penumpang per segment.</p>
        </div>
    </div>
    <?php
    $edit_segment = null;
    if (isset($_GET['edit_segment'])) {
        $id = intval($_GET['edit_segment']);
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT id,route_id,rute,origin,destination,pickup_time,harga FROM segments WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $edit_segment = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    $segment_route_id = $edit_segment['route_id'] ?? 0;
    $segment_origin = $edit_segment['origin'] ?? '';
    $segment_destination = $edit_segment['destination'] ?? '';
    $segment_pickup_time = $edit_segment['pickup_time'] ?? '';
    $segment_harga = $edit_segment['harga'] ?? '';
    $segment_id = $edit_segment['id'] ?? 0;

    $regRoutes = [];
    $resR = $conn->query("SELECT id, name FROM routes ORDER BY name");
    while ($rr = $resR->fetch(PDO::FETCH_ASSOC)) {
        $regRoutes[] = $rr;
    }
    ?>

    <div class="modern-form-card admin-bs-panel">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="admin-bs-chip">Form</span>
            <?php echo $segment_id > 0 ? 'Edit Segment' : 'Tambah Segment'; ?>
        </div>
        <form method="post">
            <?php if ($segment_id > 0)
                echo '<input type="hidden" name="segment_id" value="' . intval($segment_id) . '">'; ?>

            <div class="modern-form-grid admin-bs-form-grid">
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Rute Induk</label>
                    <select name="segment_route_id" class="modern-select form-select" required>
                        <option value="0">-- Pilih Rute Induk --</option>
                        <?php foreach ($regRoutes as $rt): ?>
                            <option value="<?= $rt['id'] ?>" <?= $segment_route_id == $rt['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Asal Segment</label>
                    <input name="segment_origin" class="modern-input form-control" placeholder="Kota Asal" required
                        value="<?php echo htmlspecialchars($segment_origin); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Tujuan Segment</label>
                    <input name="segment_destination" class="modern-input form-control" placeholder="Kota Tujuan" required
                        value="<?php echo htmlspecialchars($segment_destination); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Jam Pickup Penumpang</label>
                    <input name="segment_pickup_time" type="time" class="modern-input form-control"
                        value="<?php echo htmlspecialchars($segment_pickup_time); ?>">
                </div>
                <div class="admin-bs-field admin-bs-col-6">
                    <label class="admin-bs-input-label">Harga Segment</label>
                    <input name="segment_harga" type="number" class="modern-input form-control" placeholder="Harga (Rp)" required
                        min="0" step="1" value="<?php echo htmlspecialchars($segment_harga); ?>">
                </div>

                <div class="admin-bs-actions admin-bs-col-12">
                    <?php if ($segment_id > 0) {
                        echo '<a href="admin.php#segments" class="btn btn-outline-secondary btn-modern secondary">Batal</a>';
                    } ?>
                    <button name="save_segment" class="btn btn-primary btn-modern">
                        <?php echo $segment_id > 0 ? 'Update Segment' : 'Simpan Segment'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-bs-toolbar">
        <div class="search-bar-modern admin-bs-search">
            <input type="text" id="filter_segment_input" class="search-input-modern"
                placeholder="Cari segment atau rute induk...">
            <button type="button" class="search-btn-icon" aria-label="Cari segment">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </div>
    <div class="admin-bs-meta">
        <div class="small" id="segments_info">Total: 0</div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <select id="segments_per_page" class="form-select form-select-sm admin-bs-select-sm">
                <option value="10">10 / halaman</option>
                <option value="25" selected>25 / halaman</option>
                <option value="50">50 / halaman</option>
                <option value="100">100 / halaman</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper customers-table-wrap">
        <table class="table align-middle mb-0 customers-admin-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Segment</th>
                    <th scope="col">Rute Induk</th>
                    <th scope="col">Pickup</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody id="segments_grid" data-colspan="6">
                <?php
                $segments = [];
                $res = $conn->query("SELECT s.*, r.name as parent_route FROM segments s LEFT JOIN routes r ON s.route_id = r.id ORDER BY r.name, s.rute");
                while ($s = $res->fetch(PDO::FETCH_ASSOC)) {
                    $segments[] = $s;
                }
                foreach ($segments as $s):
                    $harga_formatted = 'Rp ' . number_format($s['harga'], 0, ',', '.');
                    $parent = $s['parent_route'] ?: 'Belum ada rute';
                    ?>
                    <tr data-table-row="1">
                        <td><span class="customers-table-id">#<?= intval($s['id']) ?></span></td>
                        <td class="customers-table-name"><?= htmlspecialchars($s['rute']) ?></td>
                        <td class="customers-table-pickup"><?= htmlspecialchars($parent) ?></td>
                        <td class="customers-table-phone"><?= !empty($s['pickup_time']) ? htmlspecialchars($s['pickup_time']) : '-' ?></td>
                        <td class="customers-table-phone"><?= $harga_formatted ?></td>
                        <td>
                            <div class="customers-table-actions">
                                <a href="admin.php?edit_segment=<?= $s['id'] ?>#segments" class="acc-btn">Edit</a>
                                <a href="admin.php?delete_segment=<?= $s['id'] ?>#segments" class="acc-btn danger"
                                    onclick="event.preventDefault(); customConfirm('Hapus segment ini?', () => { window.location.href = 'admin.php?delete_segment=<?= $s['id'] ?>#segments'; }, 'Hapus Segment', 'danger')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="segments_pagination" class="pagination-outer"></div>
    <script>
        window.setupAdminStaticListPagination?.({
            listId: 'segments_grid',
            searchInputId: 'filter_segment_input',
            perPageId: 'segments_per_page',
            infoId: 'segments_info',
            paginationId: 'segments_pagination',
            itemSelector: 'tr[data-table-row]'
        });
    </script>
</section>

