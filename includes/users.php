<!-- USERS -->
<div id="users" class="card">
  <h3>Users</h3>
  <div class="muted">Kelola akun pengguna sistem.</div>
  <?php
  $edit_user_name = $edit_user['username'] ?? '';
  $edit_user_full = $edit_user['fullname'] ?? '';
  ?>
  <!-- FORM USER -->
  <div class="modern-form-card">
    <div
      style="margin-bottom:16px;font-weight:700;color:var(--neu-text-dark);font-size:15px;display:flex;align-items:center;gap:8px;">
      <span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:6px;font-size:12px">PLUS</span>
      <?php echo !empty($edit_user) ? 'Edit User' : 'Tambah User'; ?>
    </div>
    <form method="post">
      <?php if (!empty($edit_user)) {
        echo '<input type="hidden" name="user_id" value="' . intval($edit_user['id']) . '">';
      } ?>

      <div class="modern-form-grid">
        <!-- USERNAME -->
        <div class="input-group">
          <span class="input-group-icon">👤</span>
          <input name="username" class="modern-input" placeholder="Username" required
            value="<?php echo htmlspecialchars($edit_user_name); ?>">
        </div>

        <!-- PASSWORD -->
        <div class="input-group">
          <span class="input-group-icon">🔑</span>
          <input type="password" name="password" class="modern-input" placeholder="Password" required>
        </div>

        <!-- FULLNAME -->
        <div class="input-group">
          <span class="input-group-icon">📛</span>
          <input name="fullname" class="modern-input" placeholder="Full Name"
            value="<?php echo htmlspecialchars($edit_user_full); ?>">
        </div>

        <button name="add_user" class="btn-modern" style="min-width:auto;margin-left:auto">
          💾 <?php echo !empty($edit_user) ? 'Update' : 'Buat'; ?>
        </button>
      </div>
    </form>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;margin-bottom:12px">
    <div class="search-bar-modern">
      <input type="text" id="search_user_input" class="search-input-modern" placeholder="Cari username atau nama...">
      <button type="button" id="searchUserBtn" class="search-btn-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </div>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
    <div class="small" id="users_info">Memuat users...</div>
    <div style="display:flex;gap:8px;align-items:center"><label class="small">Per page</label><select
        id="users_per_page">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>

  <div id="users_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>
  <div id="users_tbody" class="booking-cards-grid" style="margin-top:12px;min-height:100px">
    <div class="small" style="grid-column:1/-1;text-align:center">Loading...</div>
  </div>
  <div class="table-wrapper" style="display:none"></div>
  <div id="users_pagination" style="margin-top:8px"></div>
</div>