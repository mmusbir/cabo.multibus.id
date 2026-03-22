<!-- USERS -->
<section id="users" class="card">
  <div class="admin-section-header">
    <div>
      <h3 class="admin-section-title">Users</h3>
      <p class="admin-section-subtitle">Kelola akun admin dan kredensial login sistem.</p>
    </div>
  </div>
  <?php
  $edit_user = null;
  if (isset($_GET['edit_user'])) {
    $id = intval($_GET['edit_user']);
    if ($id > 0) {
      $stmt = $conn->prepare("SELECT id, username, fullname FROM users WHERE id=? LIMIT 1");
      $stmt->execute([$id]);
      $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  }

  $edit_user_name = $edit_user['username'] ?? '';
  $edit_user_full = $edit_user['fullname'] ?? '';
  ?>

  <div class="modern-form-card admin-bs-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="admin-bs-chip">Form</span>
      <?php echo !empty($edit_user) ? 'Edit User' : 'Tambah User'; ?>
    </div>
    <form method="post">
      <?php if (!empty($edit_user)) {
        echo '<input type="hidden" name="user_id" value="' . intval($edit_user['id']) . '">';
      } ?>

      <div class="modern-form-grid admin-bs-form-grid">
        <div class="input-group admin-bs-col-6">
          <label class="admin-bs-input-label">Username</label>
          <input name="username" class="modern-input form-control" placeholder="Username" required
            value="<?php echo htmlspecialchars($edit_user_name); ?>">
        </div>
        <div class="input-group admin-bs-col-6">
          <label class="admin-bs-input-label">Password</label>
          <input type="password" name="password" class="modern-input form-control"
            placeholder="<?php echo !empty($edit_user) ? 'Kosongkan jika tidak diubah' : 'Password'; ?>"
            <?php echo empty($edit_user) ? 'required' : ''; ?>>
        </div>
        <div class="input-group admin-bs-col-6">
          <label class="admin-bs-input-label">Full Name</label>
          <input name="fullname" class="modern-input form-control" placeholder="Nama Lengkap"
            value="<?php echo htmlspecialchars($edit_user_full); ?>">
        </div>
        <div class="admin-bs-actions admin-bs-col-12">
          <?php if (!empty($edit_user)) {
            echo '<a href="admin.php#users" class="btn btn-outline-secondary btn-modern secondary">Batal</a>';
          } ?>
          <button name="add_user" class="btn btn-primary btn-modern">
            <?php echo !empty($edit_user) ? 'Update User' : 'Buat User'; ?>
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="admin-bs-toolbar">
    <div class="search-bar-modern admin-bs-search">
      <input type="text" id="search_user_input" class="search-input-modern" placeholder="Cari username atau nama...">
      <button type="button" id="searchUserBtn" class="search-btn-icon" aria-label="Cari user">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>

  <div class="admin-bs-meta">
    <div class="small" id="users_info">Memuat users...</div>
    <div class="d-flex gap-2 align-items-center">
      <label class="small" for="users_per_page">Per page</label>
      <select id="users_per_page" class="form-select form-select-sm">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
  </div>

  <div id="users_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>
  <div id="users_tbody" class="booking-cards-grid admin-bs-card-grid admin-list-grid">
    <div class="small admin-grid-message">Loading...</div>
  </div>
  <div class="table-wrapper" style="display:none"></div>
  <div id="users_pagination" class="admin-pagination-host"></div>
</section>
