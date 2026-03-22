<?php
  $auth = getAuthenticatedUser();
  $userLabel = $auth['user'] ?? 'Admin';
  $userInitial = strtoupper(substr((string) $userLabel, 0, 1));
  if ($auth):
?>
  <div class="topbar kinetic-topbar navbar navbar-expand-lg sticky-top">
    <div class="topbar-inner container-fluid kinetic-topbar-inner">
      <div class="kinetic-topbar-brand-wrap">
        <a href="#bookings" class="kinetic-topbar-brand" data-target="bookings" data-booking-mode="bookings" data-nav-key="dashboard">
          <span class="material-symbols-outlined kinetic-brand-icon">directions_bus</span>
          <span class="kinetic-brand-text">KINETIC COMMAND</span>
        </a>
      </div>

      <nav class="nav kinetic-primary-nav d-none d-lg-flex" id="siteNav">
        <a href="#bookings" data-target="bookings" data-booking-mode="bookings" data-nav-key="dashboard">Dashboard</a>
        <a href="#view" data-target="view" data-nav-key="booking">Booking</a>
        <a href="#bookings" data-target="bookings" data-booking-mode="charters" data-nav-key="charter">Carter</a>
        <a href="#reports" data-target="reports" data-nav-key="reports">Laporan</a>
      </nav>

      <div class="topbar-right kinetic-topbar-right d-flex align-items-center">
        <button class="kinetic-icon-btn" id="adminSearchFocusBtn" type="button" aria-label="Fokus pencarian">
          <span class="material-symbols-outlined">search</span>
        </button>
        <button class="kinetic-icon-btn" id="moreMenuBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Buka menu admin">
          <span class="material-symbols-outlined">apps</span>
        </button>

        <div class="profile-dropdown kinetic-profile-dropdown">
          <button class="profile-btn kinetic-profile-btn" id="profileMenuBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Buka menu profil">
            <span class="kinetic-profile-avatar"><?php echo htmlspecialchars($userInitial); ?></span>
          </button>
          <div class="profile-menu kinetic-profile-menu" id="profileMenuDropdown">
            <div class="kinetic-profile-meta">
              <div class="kinetic-profile-name"><?php echo htmlspecialchars($userLabel); ?></div>
              <div class="kinetic-profile-role">Admin Panel</div>
            </div>
            <a href="javascript:void(0)" id="btnOpenChangePassword">
              <span class="material-symbols-outlined">lock</span>
              Ganti Password
            </a>
            <a href="index.php">
              <span class="material-symbols-outlined">add_circle</span>
              Booking Area
            </a>
            <div class="menu-divider"></div>
            <a href="logout.php" class="logout-link">
              <span class="material-symbols-outlined">logout</span>
              Logout
            </a>
          </div>
        </div>
      </div>

      <div id="moreMenuDropdown" class="kinetic-more-dropdown">
        <div class="menu-section-header">Operasional</div>
        <a href="#customers" data-target="customers"><span class="material-symbols-outlined">groups</span>Customers</a>
        <a href="#schedules" data-target="schedules"><span class="material-symbols-outlined">calendar_month</span>Jadwal</a>
        <a href="#cancellations" data-target="cancellations"><span class="material-symbols-outlined">cancel</span>Cancellations</a>
        <a href="#reports" data-target="reports"><span class="material-symbols-outlined">assessment</span>Laporan</a>

        <div class="menu-section-header">Data Master</div>
        <a href="#routes" data-target="routes"><span class="material-symbols-outlined">route</span>Rute</a>
        <a href="#segments" data-target="segments"><span class="material-symbols-outlined">conversion_path</span>Segment</a>
        <a href="#luggage_services" data-target="luggage_services"><span class="material-symbols-outlined">inventory_2</span>Layanan Bagasi</a>

        <div class="menu-section-header">Armada &amp; SDM</div>
        <a href="#units" data-target="units"><span class="material-symbols-outlined">airport_shuttle</span>Unit Kendaraan</a>
        <a href="#drivers" data-target="drivers"><span class="material-symbols-outlined">badge</span>Data Driver</a>

        <div class="menu-section-header">Sistem</div>
        <a href="#users" data-target="users"><span class="material-symbols-outlined">admin_panel_settings</span>Users</a>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="topbar kinetic-topbar">
    <div class="topbar-inner topbar-public kinetic-topbar-inner">
      <a href="login.php" class="kinetic-topbar-brand public-brand">
        <span class="material-symbols-outlined kinetic-brand-icon">directions_bus</span>
        <span class="kinetic-brand-text">KINETIC COMMAND</span>
      </a>
      <a href="index.php" class="inline-small btn-booking kinetic-public-booking">Buat Booking</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($auth): ?>
  <nav class="bottom-nav kinetic-bottom-nav" id="bottomNav">
    <a href="#bookings" class="nav-btn" data-target="bookings" data-booking-mode="bookings" data-nav-key="dashboard" id="navDashboard">
      <span class="material-symbols-outlined">dashboard</span>
      <span class="nav-label">Dashboard</span>
    </a>
    <a href="#view" class="nav-btn" data-target="view" data-nav-key="booking" id="navBooking">
      <span class="material-symbols-outlined">confirmation_number</span>
      <span class="nav-label">Booking</span>
    </a>
    <a href="#bookings" class="nav-btn" data-target="bookings" data-booking-mode="charters" data-nav-key="charter" id="navCharter">
      <span class="material-symbols-outlined">airport_shuttle</span>
      <span class="nav-label">Carter</span>
    </a>
    <a href="#reports" class="nav-btn" data-target="reports" data-nav-key="reports" id="navReports">
      <span class="material-symbols-outlined">assessment</span>
      <span class="nav-label">Laporan</span>
    </a>
  </nav>

  <div class="bottom-more-modal" id="bottomMoreModal">
    <div class="bottom-more-content">
      <div class="sheet-handle"></div>

      <div class="menu-section-header">Operasional</div>
      <div class="menu-grid">
        <a href="#customers" class="nav-btn" data-target="customers"><span class="material-symbols-outlined">groups</span><span class="nav-label">Customers</span></a>
        <a href="#schedules" class="nav-btn" data-target="schedules"><span class="material-symbols-outlined">calendar_month</span><span class="nav-label">Jadwal</span></a>
        <a href="#cancellations" class="nav-btn" data-target="cancellations"><span class="material-symbols-outlined">cancel</span><span class="nav-label">Cancel</span></a>
        <a href="#reports" class="nav-btn" data-target="reports"><span class="material-symbols-outlined">assessment</span><span class="nav-label">Laporan</span></a>
      </div>

      <div class="menu-section-header">Data Master</div>
      <div class="menu-grid">
        <a href="#routes" class="nav-btn" data-target="routes"><span class="material-symbols-outlined">route</span><span class="nav-label">Rute</span></a>
        <a href="#segments" class="nav-btn" data-target="segments"><span class="material-symbols-outlined">conversion_path</span><span class="nav-label">Segment</span></a>
        <a href="#luggage_services" class="nav-btn" data-target="luggage_services"><span class="material-symbols-outlined">inventory_2</span><span class="nav-label">Bagasi</span></a>
      </div>

      <div class="menu-section-header">Armada &amp; SDM</div>
      <div class="menu-grid">
        <a href="#units" class="nav-btn" data-target="units"><span class="material-symbols-outlined">airport_shuttle</span><span class="nav-label">Unit</span></a>
        <a href="#drivers" class="nav-btn" data-target="drivers"><span class="material-symbols-outlined">badge</span><span class="nav-label">Driver</span></a>
        <a href="#users" class="nav-btn" data-target="users"><span class="material-symbols-outlined">admin_panel_settings</span><span class="nav-label">Users</span></a>
      </div>

      <div class="menu-section-header">Akun</div>
      <div class="menu-grid">
        <a href="index.php" class="nav-btn"><span class="material-symbols-outlined">add_circle</span><span class="nav-label">Booking</span></a>
        <button class="nav-btn nav-btn-close" id="closeMoreModal" type="button"><span class="material-symbols-outlined">close</span><span class="nav-label">Tutup</span></button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    function setDropdownState(button, isOpen) {
      if (!button) return;
      button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    const moreBtn = document.getElementById('moreMenuBtn');
    const moreDropdown = document.getElementById('moreMenuDropdown');
    const profileBtn = document.getElementById('profileMenuBtn');
    const profileDropdown = document.getElementById('profileMenuDropdown');
    const bottomMoreModal = document.getElementById('bottomMoreModal');
    const closeMoreModal = document.getElementById('closeMoreModal');
    const searchFocusBtn = document.getElementById('adminSearchFocusBtn');

    function getPrimaryNavKey(target) {
      if (target === 'view') return 'booking';
      if (target === 'reports') return 'reports';
      if (target === 'bookings') {
        const bookingMode = window.bookingDashboardState && window.bookingDashboardState.active;
        if (bookingMode === 'charters') return 'charter';
        return 'dashboard';
      }
      return '';
    }

    function syncAdminNavState(target) {
      const primaryKey = getPrimaryNavKey(target);
      document.querySelectorAll('[data-nav-key]').forEach(link => {
        link.classList.toggle('active', !!primaryKey && link.getAttribute('data-nav-key') === primaryKey);
      });

      document.querySelectorAll('#moreMenuDropdown a[data-target], .bottom-more-content .nav-btn[data-target]').forEach(link => {
        link.classList.toggle('active', link.getAttribute('data-target') === target);
      });
    }

    window.syncAdminNavState = syncAdminNavState;

    function closeDesktopMenus() {
      if (moreDropdown) moreDropdown.style.display = 'none';
      if (profileDropdown) profileDropdown.style.display = 'none';
      setDropdownState(moreBtn, false);
      setDropdownState(profileBtn, false);
    }

    function openMobileMore() {
      if (!bottomMoreModal) return;
      bottomMoreModal.style.display = 'flex';
      bottomMoreModal.offsetHeight;
      bottomMoreModal.classList.add('show');
    }

    function closeMobileMore() {
      if (!bottomMoreModal) return;
      bottomMoreModal.classList.remove('show');
      setTimeout(() => {
        if (!bottomMoreModal.classList.contains('show')) {
          bottomMoreModal.style.display = 'none';
        }
      }, 280);
    }

    function focusActiveSearch() {
      const candidates = [
        '#search_name_input',
        '#search_cancellations_input',
        '#search_customer_input',
        '#search_user_input',
        '#view_rute'
      ];
      for (const selector of candidates) {
        const el = document.querySelector(selector);
        if (el && el.offsetParent !== null) {
          el.focus();
          return;
        }
      }
    }

    if (searchFocusBtn) {
      searchFocusBtn.addEventListener('click', focusActiveSearch);
    }

    if (moreBtn) {
      moreBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.matchMedia('(max-width: 991.98px)').matches) {
          openMobileMore();
          return;
        }
        const isOpen = moreDropdown && moreDropdown.style.display === 'block';
        closeDesktopMenus();
        if (moreDropdown && !isOpen) {
          moreDropdown.style.display = 'block';
          setDropdownState(moreBtn, true);
        }
      });
    }

    if (profileBtn && profileDropdown) {
      profileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = profileDropdown.style.display === 'block';
        closeDesktopMenus();
        if (!isOpen) {
          profileDropdown.style.display = 'block';
          setDropdownState(profileBtn, true);
        }
      });
    }

    document.addEventListener('click', function (e) {
      if (moreDropdown && !moreDropdown.contains(e.target) && e.target !== moreBtn) {
        moreDropdown.style.display = 'none';
        setDropdownState(moreBtn, false);
      }
      if (profileDropdown && !profileDropdown.contains(e.target) && e.target !== profileBtn) {
        profileDropdown.style.display = 'none';
        setDropdownState(profileBtn, false);
      }
    });

    if (bottomMoreModal) {
      bottomMoreModal.addEventListener('click', function (e) {
        if (e.target === bottomMoreModal) closeMobileMore();
      });
    }
    if (closeMoreModal) {
      closeMoreModal.addEventListener('click', closeMobileMore);
    }

    function applyBookingMode(mode) {
      if (mode && typeof window.switchAdminView === 'function') {
        window.switchAdminView(mode);
      }
    }

    function showTargetSection(target, bookingMode) {
      if (!target) return;
      closeDesktopMenus();
      if (bottomMoreModal && bottomMoreModal.classList.contains('show')) {
        closeMobileMore();
      }

      if (typeof window.showSectionById === 'function') {
        window.showSectionById(target);
      }
      if (window.location.hash.replace('#', '') !== target) {
        window.location.hash = target;
      }
      if (target === 'bookings') {
        setTimeout(function () {
          applyBookingMode(bookingMode || 'bookings');
          syncAdminNavState('bookings');
        }, 0);
      } else {
        syncAdminNavState(target);
      }
    }

    document.querySelectorAll('.kinetic-primary-nav a[data-target], #moreMenuDropdown a[data-target], .bottom-nav .nav-btn[data-target], .bottom-more-content .nav-btn[data-target], .kinetic-topbar-brand[data-target]').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('data-target');
        const bookingMode = this.getAttribute('data-booking-mode');
        showTargetSection(target, bookingMode);
      });
    });

    window.addEventListener('hashchange', function () {
      const hash = window.location.hash.replace('#', '') || 'bookings';
      setTimeout(function () {
        syncAdminNavState(hash);
      }, 0);
    });

    const initialTarget = window.location.hash.replace('#', '') || 'bookings';
    syncAdminNavState(initialTarget);
  });
</script>
