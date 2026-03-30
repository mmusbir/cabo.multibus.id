<?php
global $conn, $auth;

external_api_ensure_table($conn);

$editApiKey = null;
if (isset($_GET['edit_api_key'])) {
  $editId = intval($_GET['edit_api_key']);
  if ($editId > 0) {
    $editStmt = $conn->prepare("SELECT id, name, status, notes, api_key_prefix, created_by_username, last_used_at, created_at FROM external_api_keys WHERE id=? LIMIT 1");
    $editStmt->execute([$editId]);
    $editApiKey = $editStmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }
}

$generatedApiKey = $_SESSION['generated_external_api_key'] ?? null;
unset($_SESSION['generated_external_api_key']);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$apiBase = $scheme . '://' . $host . '/api.php';
?>
<section id="api_access" class="card" style="display:none;">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">API</h3>
      <p class="admin-section-subtitle">Kelola API key integrasi untuk add, update, dan cancel booking penumpang dari aplikasi eksternal.</p>
    </div>
  </div>

  <?php if (!empty($generatedApiKey['plain_key'])): ?>
    <div class="modern-form-card admin-bs-panel">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="admin-bs-chip">API Key Baru</span>
        Simpan key ini sekarang. Setelah ditutup, key tidak akan ditampilkan lagi.
      </div>
      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-12">
          <label class="admin-bs-input-label">Nama Integrasi</label>
          <div class="customers-table-name"><?= htmlspecialchars((string) ($generatedApiKey['name'] ?? '-')) ?></div>
        </div>
        <div class="admin-bs-field admin-bs-col-12">
          <label class="admin-bs-input-label">Plain API Key</label>
          <div class="api-access-secret-wrap">
            <textarea id="generated_api_key" class="modern-input form-control admin-textarea-sm" rows="2" readonly><?= htmlspecialchars((string) $generatedApiKey['plain_key']) ?></textarea>
            <button type="button" class="btn btn-primary btn-modern" id="copyGeneratedApiKeyBtn">Copy Key</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <?= $editApiKey ? 'Edit API Key' : 'Generate API Key'; ?>
    </div>
    <form method="post">
      <?php if ($editApiKey): ?>
        <input type="hidden" name="api_key_id" value="<?= intval($editApiKey['id']) ?>">
      <?php endif; ?>
      <div class="modern-form-grid admin-bs-form-grid">
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Nama Integrasi</label>
          <input type="text" name="api_key_name" class="modern-input form-control" placeholder="Contoh: Aplikasi Agen Mobile" required value="<?= htmlspecialchars((string) ($editApiKey['name'] ?? '')) ?>">
        </div>
        <div class="admin-bs-field admin-bs-col-6">
          <label class="admin-bs-input-label">Status</label>
          <select name="api_key_status" class="modern-select form-select">
            <option value="active" <?= (($editApiKey['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= (($editApiKey['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="admin-bs-field admin-bs-col-12">
          <label class="admin-bs-input-label">Catatan</label>
          <textarea name="api_key_notes" class="modern-input form-control admin-textarea-sm" placeholder="Catatan penggunaan atau pemilik integrasi..."><?= htmlspecialchars((string) ($editApiKey['notes'] ?? '')) ?></textarea>
        </div>
        <div class="admin-bs-actions admin-bs-col-12">
          <?php if ($editApiKey): ?>
            <a href="admin.php#api_access" class="btn btn-outline-secondary btn-modern secondary">Batal</a>
            <button type="submit" name="regenerate_api_key" value="1" class="btn btn-outline-secondary btn-modern secondary" onclick="return confirm('Regenerate API key ini? Key lama akan langsung tidak berlaku.');">Regenerate Key</button>
          <?php endif; ?>
          <button type="submit" name="save_api_key" value="1" class="btn btn-primary btn-modern"><?= $editApiKey ? 'Update API Key' : 'Generate API Key' ?></button>
        </div>
      </div>
    </form>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Endpoint</span>
      Gunakan header <code>X-API-Key</code> pada aplikasi eksternal Anda.
    </div>
    <div class="table-wrapper customers-table-wrap api-access-docs-wrap">
      <table class="table align-middle mb-0 customers-admin-table">
        <thead>
          <tr>
            <th>Operasi</th>
            <th>Method</th>
            <th>Endpoint</th>
            <th>Payload Minimal</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Create Booking</td>
            <td>POST</td>
            <td><code><?= htmlspecialchars($apiBase) ?>?action=externalCreateBooking</code></td>
            <td><code>rute, tanggal, jam, unit, seats/seat, name, phone</code></td>
          </tr>
          <tr>
            <td>Update Booking</td>
            <td>POST</td>
            <td><code><?= htmlspecialchars($apiBase) ?>?action=externalUpdateBooking</code></td>
            <td><code>booking_id</code> atau <code>rute, tanggal, jam, unit, current_seat</code></td>
          </tr>
          <tr>
            <td>Cancel Booking</td>
            <td>POST</td>
            <td><code><?= htmlspecialchars($apiBase) ?>?action=externalCancelBooking</code></td>
            <td><code>booking_id</code> atau <code>rute, tanggal, jam, unit, seat</code></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Contoh Request</span>
      Dokumentasi singkat untuk integrasi aplikasi eksternal.
    </div>
    <div class="modern-form-grid admin-bs-form-grid">
      <div class="admin-bs-field admin-bs-col-12">
        <label class="admin-bs-input-label">Header Wajib</label>
        <pre class="api-access-code-block"><code>X-API-Key: API_KEY_ANDA
Content-Type: application/json</code></pre>
      </div>

      <div class="admin-bs-field admin-bs-col-12">
        <label class="admin-bs-input-label">Create Booking</label>
        <pre class="api-access-code-block"><code>curl -X POST "<?= htmlspecialchars($apiBase) ?>?action=externalCreateBooking" \
-H "X-API-Key: API_KEY_ANDA" \
-H "Content-Type: application/json" \
-d '{
  "rute": "PINRANG - MAKASSAR",
  "tanggal": "2026-03-30",
  "jam": "09:30",
  "unit": 1,
  "seats": ["3", "4"],
  "name": "BUDI",
  "phone": "08123456789",
  "pickup_point": "Terminal",
  "pembayaran": "Belum Lunas"
}'</code></pre>
      </div>

      <div class="admin-bs-field admin-bs-col-6">
        <label class="admin-bs-input-label">Update Booking</label>
        <pre class="api-access-code-block"><code>{
  "booking_id": 123,
  "name": "BUDI SANTOSO",
  "phone": "08123456789",
  "seat": "5",
  "pickup_point": "Bandara",
  "pembayaran": "Lunas"
}</code></pre>
      </div>

      <div class="admin-bs-field admin-bs-col-6">
        <label class="admin-bs-input-label">Cancel Booking</label>
        <pre class="api-access-code-block"><code>{
  "booking_id": 123,
  "reason": "Dibatalkan dari aplikasi agen"
}</code></pre>
      </div>

      <div class="admin-bs-field admin-bs-col-6">
        <label class="admin-bs-input-label">Contoh Response Sukses</label>
        <pre class="api-access-code-block"><code>{
  "success": true,
  "added": 2,
  "booking_ids": [101, 102]
}</code></pre>
      </div>

      <div class="admin-bs-field admin-bs-col-6">
        <label class="admin-bs-input-label">Contoh Response Error</label>
        <pre class="api-access-code-block"><code>{
  "success": false,
  "error": "conflict",
  "conflict": ["3", "4"]
}</code></pre>
      </div>
    </div>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_api_access_input" class="search-input-modern" placeholder="Cari nama integrasi atau creator...">
      <button type="button" id="searchApiAccessBtn" class="search-btn-icon" aria-label="Cari API key">
        <i class="fa-solid fa-magnifying-glass fa-icon"></i>
      </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="api_access_info">Memuat API key...</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <select id="api_access_per_page" class="form-select form-select-sm admin-bs-select-sm">
        <option value="10">10 / halaman</option>
        <option value="25" selected>25 / halaman</option>
        <option value="50">50 / halaman</option>
        <option value="100">100 / halaman</option>
      </select>
    </div>
  </div>

  <div id="api_access_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div class="table-wrapper customers-table-wrap">
    <table class="table align-middle mb-0 customers-admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Integrasi</th>
          <th>Prefix Key</th>
          <th>Status</th>
          <th>Dibuat Oleh</th>
          <th>Terakhir Dipakai</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="api_access_tbody" data-colspan="8">
        <tr>
          <td colspan="8" class="customers-table-empty">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="api_access_pagination" class="pagination-outer"></div>
</section>
