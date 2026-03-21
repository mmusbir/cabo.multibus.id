<!-- SEGMENTS -->
<section id="segments" class="card">
    <h3>Manajemen Segment</h3>
    <?php
    $edit_segment = null;
    if (isset($_GET['edit_segment'])) {
        $id = intval($_GET['edit_segment']);
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT id,route_id,rute,harga FROM segments WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $edit_segment = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    $segment_route_id = $edit_segment['route_id'] ?? 0;
    $segment_rute = $edit_segment['rute'] ?? '';
    $segment_harga = $edit_segment['harga'] ?? '';
    $segment_id = $edit_segment['id'] ?? 0;

    // Fetch regular routes for dropdown
    $regRoutes = [];
    $resR = $conn->query("SELECT id, name FROM routes ORDER BY name");
    while ($rr = $resR->fetch(PDO::FETCH_ASSOC))
        $regRoutes[] = $rr;
    ?>
    <!-- FORM -->
    <div class="modern-form-card">
        <div
            style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
            <span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:6px;font-size:12px">PLUS</span>
            <?php echo $segment_id > 0 ? 'Edit Segment' : 'Tambah Segment'; ?>
        </div>
        <form method="post">
            <?php if ($segment_id > 0)
                echo '<input type="hidden" name="segment_id" value="' . intval($segment_id) . '">'; ?>

            <div class="modern-form-grid">
                <!-- RUTE MASTER -->
                <div class="input-group">
                    <span class="input-group-icon">🛣️</span>
                    <select name="segment_route_id" class="modern-select" required>
                        <option value="0">-- Pilih Rute Induk --</option>
                        <?php foreach ($regRoutes as $rt): ?>
                            <option value="<?= $rt['id'] ?>" <?= $segment_route_id == $rt['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- NAMA SEGMENT -->
                <div class="input-group">
                    <span class="input-group-icon">🗺️</span>
                    <input name="segment_rute" class="modern-input" placeholder="Nama Segment (cth: PRE - MKS)" required
                        value="<?php echo htmlspecialchars($segment_rute); ?>">
                </div>

                <div class="input-group">
                    <span class="input-group-icon">💰</span>
                    <input name="segment_harga" type="number" class="modern-input" placeholder="Harga (Rp)" required
                        min="0" step="1" value="<?php echo htmlspecialchars($segment_harga); ?>">
                </div>

                <!-- ACTIONS -->
                <div style="display:flex;gap:8px;grid-column:1/-1;justify-content:flex-end;margin-top:8px">
                    <?php if ($segment_id > 0) {
                        echo '<a href="admin.php#segments" class="btn-modern secondary" style="text-align:center">Batal</a>';
                    } ?>
                    <button name="save_segment" class="btn-modern">
                        💾
                        <?php echo $segment_id > 0 ? 'Update Segment' : 'Simpan Segment'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- SEARCH BAR -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;margin-bottom:12px">
        <div class="search-bar-modern">
            <input type="text" id="filter_segment_input" class="search-input-modern"
                placeholder="Cari segment atau rute induk...">
            <button type="button" class="search-btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </div>

    <!-- LIST SEGMENTS -->
    <div class="booking-cards-grid" id="segments_grid" style="margin-top:12px;">
        <?php
        $segments = [];
        $res = $conn->query("SELECT s.*, r.name as parent_route FROM segments s LEFT JOIN routes r ON s.route_id = r.id ORDER BY r.name, s.rute");
        while ($s = $res->fetch(PDO::FETCH_ASSOC))
            $segments[] = $s;
        foreach ($segments as $s):
            $harga_formatted = 'Rp ' . number_format($s['harga'], 0, ',', '.');
            $parent = $s['parent_route'] ?: '<span style="color:var(--neu-danger)">Belum ada rute</span>';
            ?>
            <div class="admin-card-compact">
                <div class="acc-header">
                    <div class="acc-title">
                        <?= htmlspecialchars($s['rute']) ?>
                    </div>
                    <div class="acc-id">#
                        <?= intval($s['id']) ?>
                    </div>
                </div>
                <div class="acc-body">
                    <div class="acc-row">
                        <div class="acc-label">🛣️</div>
                        <div class="acc-val" style="font-size:12px">
                            <?= $parent ?>
                        </div>
                    </div>
                    <div class="acc-row">
                        <div class="acc-label">💰</div>
                        <div class="acc-val" style="color:var(--neu-success);font-weight:600">
                            <?= $harga_formatted ?>
                        </div>
                    </div>
                </div>
                <div class="acc-actions">
                    <a href="admin.php?edit_segment=<?= $s['id'] ?>#segments" class="acc-btn">Edit</a>
                    <a href="admin.php?delete_segment=<?= $s['id'] ?>#segments" class="acc-btn danger"
                        onclick="event.preventDefault(); customConfirm('Hapus segment ini?', () => { window.location.href = 'admin.php?delete_segment=<?= $s['id'] ?>#segments'; }, 'Hapus Segment', 'danger')">Hapus</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        document.getElementById('filter_segment_input')?.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            const cards = document.querySelectorAll('#segments_grid .admin-card-compact');
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(val) ? 'flex' : 'none';
            });
        });
    </script>
</section>