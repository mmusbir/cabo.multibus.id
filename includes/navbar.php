<?php
  $auth = getAuthenticatedUser();
  $userLabel = $auth['user'] ?? 'Admin';
  $userInitial = strtoupper(substr((string) $userLabel, 0, 1));
  if ($auth):
?>
  <aside class="kinetic-sidebar d-none d-lg-flex">
    <div class="kinetic-sidebar-head">
      <a href="#dashboard" class="kinetic-sidebar-brand" data-target="dashboard" data-nav-key="dashboard">
        <i class="kinetic-brand-icon fa-solid fa-bus fa-icon"></i>
        <span class="kinetic-brand-text">Admin Panel</span>
      </a>
      <button class="kinetic-sidebar-floating-toggle d-none d-lg-inline-flex" id="desktopSidebarToggle" type="button" aria-label="Sembunyikan sidebar" aria-expanded="true">
        <i class="fa-solid fa-angles-left fa-icon" data-sidebar-toggle-icon></i>
      </button>
    </div>

    <div class="kinetic-sidebar-scroll">
      <nav class="kinetic-sidebar-primary">
        <a href="#dashboard" data-target="dashboard" data-nav-key="dashboard"><i class="fa-solid fa-table-columns fa-icon"></i>Dashboard</a>
        <a href="#bookings" data-target="bookings" data-booking-mode="bookings" data-nav-key="booking"><i class="fa-solid fa-receipt fa-icon"></i>Booking</a>
        <a href="#bookings" data-target="bookings" data-booking-mode="charters" data-nav-key="charter"><i class="fa-solid fa-van-shuttle fa-icon"></i>Carter</a>
        <a href="#luggage" data-target="luggage" data-nav-key="luggage"><i class="fa-solid fa-suitcase-rolling fa-icon"></i>Bagasi</a>
        <a href="#reports" data-target="reports" data-nav-key="reports"><i class="fa-solid fa-chart-column fa-icon"></i>Laporan</a>
      </nav>

      <div class="kinetic-sidebar-section kinetic-sidebar-submenu is-collapsed" id="customerSidebarSection">
        <button class="kinetic-sidebar-section-toggle" id="customerSidebarToggle" type="button" aria-expanded="false" aria-controls="customerSidebarLinks">
          <span class="kinetic-sidebar-section-title"><i class="fa-solid fa-users fa-icon"></i><span>Customer</span></span>
          <i class="fa-solid fa-chevron-down fa-icon kinetic-sidebar-section-caret"></i>
        </button>
        <div class="kinetic-sidebar-links" id="customerSidebarLinks">
          <a href="#customers" data-target="customers"><i class="fa-solid fa-user-check fa-icon"></i>Reguler</a>
          <a href="#customer_bagasi" data-target="customer_bagasi"><i class="fa-solid fa-users-viewfinder fa-icon"></i>Bagasi</a>
          <a href="#customer_charter" data-target="customer_charter"><i class="fa-solid fa-users-rectangle fa-icon"></i>Carter</a>
        </div>
      </div>

      <div class="kinetic-sidebar-section kinetic-sidebar-submenu is-collapsed" id="settingsSidebarSection">
        <button class="kinetic-sidebar-section-toggle" id="settingsSidebarToggle" type="button" aria-expanded="false" aria-controls="settingsSidebarLinks">
          <span class="kinetic-sidebar-section-title"><i class="fa-solid fa-gear fa-icon"></i><span>Pengaturan</span></span>
          <i class="fa-solid fa-chevron-down fa-icon kinetic-sidebar-section-caret"></i>
        </button>
        <div class="kinetic-sidebar-links" id="settingsSidebarLinks">
          <a href="#schedules" data-target="schedules"><i class="fa-solid fa-calendar-days fa-icon"></i>Jadwal</a>
          <a href="#cancellations" data-target="cancellations"><i class="fa-solid fa-clock-rotate-left fa-icon"></i>Logs</a>
          <a href="#routes" data-target="routes"><i class="fa-solid fa-route fa-icon"></i>Rute Reguler</a>
          <a href="#routes_carter" data-target="routes_carter"><i class="fa-solid fa-map-location-dot fa-icon"></i>Rute Carter</a>
          <a href="#segments" data-target="segments"><i class="fa-solid fa-shuffle fa-icon"></i>Segment</a>
          <a href="#luggage_services" data-target="luggage_services"><i class="fa-solid fa-suitcase-rolling fa-icon"></i>Layanan Bagasi</a>
          <a href="#units" data-target="units"><i class="fa-solid fa-van-shuttle fa-icon"></i>Unit Kendaraan</a>
          <a href="#drivers" data-target="drivers"><i class="fa-solid fa-id-badge fa-icon"></i>Data Driver</a>
          <a href="#users" data-target="users"><i class="fa-solid fa-user-shield fa-icon"></i>Users</a>
        </div>
      </div>
    </div>

  </aside>


  <div class="kinetic-desktop-navbar d-none d-lg-flex">
    <div class="kinetic-desktop-navbar-inner">
      <div class="kinetic-desktop-navbar-spacer"></div>
      <button class="kinetic-icon-btn theme-toggle-btn" type="button" data-theme-toggle aria-label="Ubah tema">
        <i class="fa-solid fa-sun fa-icon" data-theme-icon></i>
      </button>
      <div class="profile-dropdown kinetic-profile-dropdown">
        <button class="profile-btn kinetic-profile-btn" id="desktopProfileMenuBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Buka menu profil desktop">
          <span class="kinetic-profile-avatar"><?php echo htmlspecialchars($userInitial); ?></span>
        </button>
        <div class="profile-menu kinetic-profile-menu" id="desktopProfileMenuDropdown">
          <div class="kinetic-profile-meta">
            <div class="kinetic-profile-name"><?php echo htmlspecialchars($userLabel); ?></div>
            <div class="kinetic-profile-role">Admin Panel</div>
          </div>
          <a href="javascript:void(0)" data-open-change-password>
            <i class="fa-solid fa-lock fa-icon"></i>
            Ganti Password
          </a>
          <div class="menu-divider"></div>
          <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket fa-icon"></i>
            Logout
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="topbar kinetic-topbar kinetic-mobile-topbar d-lg-none">
    <div class="topbar-inner container-fluid kinetic-topbar-inner">
      <a href="#dashboard" class="kinetic-topbar-brand" data-target="dashboard" data-nav-key="dashboard">
        <i class="kinetic-brand-icon fa-solid fa-bus fa-icon"></i>
        <span class="kinetic-brand-text">Admin Panel</span>
      </a>

      <div class="topbar-right kinetic-topbar-right d-flex align-items-center">
        <button class="kinetic-icon-btn theme-toggle-btn" type="button" data-theme-toggle aria-label="Ubah tema">
          <i class="fa-solid fa-sun fa-icon" data-theme-icon></i>
        </button>
        <button class="kinetic-icon-btn" id="moreMenuBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Buka menu admin">
          <i class="fa-solid fa-table-cells-large fa-icon"></i>
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
            <a href="javascript:void(0)" data-open-change-password>
              <i class="fa-solid fa-lock fa-icon"></i>
              Ganti Password
            </a>
            <a href="index.php">
              <i class="fa-solid fa-plus fa-icon"></i>
              Booking Area
            </a>
            <div class="menu-divider"></div>
            <a href="logout.php" class="logout-link">
              <i class="fa-solid fa-right-from-bracket fa-icon"></i>
              Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="topbar kinetic-topbar">
    <div class="topbar-inner topbar-public kinetic-topbar-inner">
      <a href="login.php" class="kinetic-topbar-brand public-brand">
        <i class="kinetic-brand-icon fa-solid fa-bus fa-icon"></i>
        <span class="kinetic-brand-text">CAHAYA BONE</span>
      </a>
      <a href="index.php" class="inline-small btn-booking kinetic-public-booking">Buat Booking</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($auth): ?>
  <nav class="bottom-nav kinetic-bottom-nav" id="bottomNav">
    <a href="#dashboard" class="nav-btn" data-target="dashboard" data-nav-key="dashboard" id="navDashboard">
      <i class="fa-solid fa-table-columns fa-icon"></i>
      <span class="nav-label">Dashboard</span>
    </a>
    <a href="#bookings" class="nav-btn" data-target="bookings" data-booking-mode="bookings" data-nav-key="booking" id="navBooking">
      <i class="fa-solid fa-receipt fa-icon"></i>
      <span class="nav-label">Booking</span>
    </a>
    <a href="#bookings" class="nav-btn" data-target="bookings" data-booking-mode="charters" data-nav-key="charter" id="navCharter">
      <i class="fa-solid fa-van-shuttle fa-icon"></i>
      <span class="nav-label">Carter</span>
    </a>
    <a href="#luggage" class="nav-btn" data-target="luggage" data-nav-key="luggage" id="navLuggage">
      <i class="fa-solid fa-suitcase-rolling fa-icon"></i>
      <span class="nav-label">Bagasi</span>
    </a>
    <a href="#reports" class="nav-btn" data-target="reports" data-nav-key="reports" id="navReports">
      <i class="fa-solid fa-chart-column fa-icon"></i>
      <span class="nav-label">Laporan</span>
    </a>
  </nav>

  <div class="bottom-more-modal" id="bottomMoreModal">
    <div class="bottom-more-content">
      <div class="sheet-handle"></div>

      <div class="menu-section-header">Customer</div>
      <div class="menu-grid">
        <a href="#customers" class="nav-btn" data-target="customers"><i class="fa-solid fa-users fa-icon"></i><span class="nav-label">Reguler</span></a>
        <a href="#customer_bagasi" class="nav-btn" data-target="customer_bagasi"><i class="fa-solid fa-users-viewfinder fa-icon"></i><span class="nav-label">Bagasi</span></a>
        <a href="#customer_charter" class="nav-btn" data-target="customer_charter"><i class="fa-solid fa-users-rectangle fa-icon"></i><span class="nav-label">Carter</span></a>
      </div>

      <div class="menu-section-header">Pengaturan</div>
      <div class="menu-grid">
        <a href="#schedules" class="nav-btn" data-target="schedules"><i class="fa-solid fa-calendar-days fa-icon"></i><span class="nav-label">Jadwal</span></a>
        <a href="#cancellations" class="nav-btn" data-target="cancellations"><i class="fa-solid fa-clock-rotate-left fa-icon"></i><span class="nav-label">Logs</span></a>
        <a href="#routes" class="nav-btn" data-target="routes"><i class="fa-solid fa-route fa-icon"></i><span class="nav-label">Rute Reguler</span></a>
        <a href="#routes_carter" class="nav-btn" data-target="routes_carter"><i class="fa-solid fa-map-location-dot fa-icon"></i><span class="nav-label">Rute Carter</span></a>
        <a href="#segments" class="nav-btn" data-target="segments"><i class="fa-solid fa-shuffle fa-icon"></i><span class="nav-label">Segment</span></a>
        <a href="#luggage_services" class="nav-btn" data-target="luggage_services"><i class="fa-solid fa-suitcase-rolling fa-icon"></i><span class="nav-label">Bagasi</span></a>
        <a href="#units" class="nav-btn" data-target="units"><i class="fa-solid fa-van-shuttle fa-icon"></i><span class="nav-label">Unit</span></a>
        <a href="#drivers" class="nav-btn" data-target="drivers"><i class="fa-solid fa-id-badge fa-icon"></i><span class="nav-label">Driver</span></a>
        <a href="#users" class="nav-btn" data-target="users"><i class="fa-solid fa-user-shield fa-icon"></i><span class="nav-label">Users</span></a>
      </div>

      <div class="menu-section-header">Akun</div>
      <div class="menu-grid">
        <a href="index.php" class="nav-btn"><i class="fa-solid fa-plus fa-icon"></i><span class="nav-label">Booking</span></a>
        <button class="nav-btn nav-btn-close" id="closeMoreModal" type="button"><i class="fa-solid fa-xmark fa-icon"></i><span class="nav-label">Tutup</span></button>
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
    const profileBtn = document.getElementById('profileMenuBtn');
    const profileDropdown = document.getElementById('profileMenuDropdown');
    const desktopProfileBtn = document.getElementById('desktopProfileMenuBtn');
    const desktopProfileDropdown = document.getElementById('desktopProfileMenuDropdown');
    const bottomMoreModal = document.getElementById('bottomMoreModal');
    const closeMoreModal = document.getElementById('closeMoreModal');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const sidebarStorageKey = 'adminSidebarHidden';
    const settingsSidebarSection = document.getElementById('settingsSidebarSection');
    const settingsSidebarToggle = document.getElementById('settingsSidebarToggle');
    const settingsMenuStorageKey = 'adminSettingsMenuCollapsed';
    const settingsTargets = ['schedules', 'cancellations', 'routes', 'routes_carter', 'segments', 'luggage_services', 'units', 'drivers', 'users'];

    const customerSidebarSection = document.getElementById('customerSidebarSection');
    const customerSidebarToggle = document.getElementById('customerSidebarToggle');
    const customerMenuStorageKey = 'adminCustomerMenuCollapsed';
    const customerTargets = ['customers', 'customer_bagasi', 'customer_charter'];

    function syncDesktopSidebarButton() {
      const isHidden = document.body.classList.contains('sidebar-hidden');
      const icon = desktopSidebarToggle ? desktopSidebarToggle.querySelector('[data-sidebar-toggle-icon]') : null;
      if (desktopSidebarToggle) {
        desktopSidebarToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
        desktopSidebarToggle.setAttribute('aria-label', isHidden ? 'Tampilkan sidebar' : 'Sembunyikan sidebar');
      }
      if (icon) {
        icon.className = 'fa-solid fa-icon ' + (isHidden ? 'fa-angles-right' : 'fa-angles-left');
      }
    }

    function setDesktopSidebarHidden(isHidden) {
      document.body.classList.toggle('sidebar-hidden', !!isHidden);
      try {
        window.localStorage.setItem(sidebarStorageKey, isHidden ? '1' : '0');
      } catch (err) {
        // Ignore storage issues in private mode.
      }
      syncDesktopSidebarButton();
    }

    function isSettingsTarget(target) {
      return settingsTargets.includes(target);
    }

    function setSettingsMenuCollapsed(isCollapsed, persist = true) {
      if (!settingsSidebarSection || !settingsSidebarToggle) return;
      settingsSidebarSection.classList.toggle('is-collapsed', !!isCollapsed);
      settingsSidebarToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
      if (persist) {
        try {
          window.localStorage.setItem(settingsMenuStorageKey, isCollapsed ? '1' : '0');
        } catch (err) {
          // Ignore unavailable storage.
        }
      }
    }

    function isCustomerTarget(target) {
      return customerTargets.includes(target);
    }

    function setCustomerMenuCollapsed(isCollapsed, persist = true) {
      if (!customerSidebarSection || !customerSidebarToggle) return;
      customerSidebarSection.classList.toggle('is-collapsed', !!isCollapsed);
      customerSidebarToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
      if (persist) {
        try {
          window.localStorage.setItem(customerMenuStorageKey, isCollapsed ? '1' : '0');
        } catch (err) {
          // Ignore unavailable storage.
        }
      }
    }

    function getPrimaryNavKey(target) {
      if (target === 'dashboard') return 'dashboard';
      if (target === 'reports') return 'reports';
      if (target === 'luggage') return 'luggage';
      if (target === 'booking-detail') return 'booking';
      if (target === 'charter-create') return 'charter';
      if (target === 'bookings') {
        const bookingMode = window.bookingDashboardState && window.bookingDashboardState.active;
        if (bookingMode === 'charters') return 'charter';
        if (bookingMode === 'luggage') return 'luggage';
        return 'booking';
      }
      return '';
    }

    function syncAdminNavState(target) {
      const primaryKey = getPrimaryNavKey(target);
      document.querySelectorAll('[data-nav-key]').forEach(link => {
        link.classList.toggle('active', !!primaryKey && link.getAttribute('data-nav-key') === primaryKey);
      });

      document.querySelectorAll('.kinetic-sidebar-links a[data-target], .bottom-more-content .nav-btn[data-target]').forEach(link => {
        link.classList.toggle('active', link.getAttribute('data-target') === target);
      });

      if (isSettingsTarget(target)) {
        setSettingsMenuCollapsed(false, false);
      }
      if (isCustomerTarget(target)) {
        setCustomerMenuCollapsed(false, false);
      }
    }

    window.syncAdminNavState = syncAdminNavState;

    function closeProfileMenu() {
      if (profileDropdown) profileDropdown.style.display = 'none';
      setDropdownState(profileBtn, false);
      if (desktopProfileDropdown) desktopProfileDropdown.style.display = 'none';
      setDropdownState(desktopProfileBtn, false);
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
        '#search_user_input'
      ];
      for (const selector of candidates) {
        const el = document.querySelector(selector);
        if (el && el.offsetParent !== null) {
          el.focus();
          return;
        }
      }
    }

    document.querySelectorAll('[data-focus-admin-search]').forEach(btn => {
      btn.addEventListener('click', focusActiveSearch);
    });

    if (desktopSidebarToggle) {
      desktopSidebarToggle.addEventListener('click', function () {
        setDesktopSidebarHidden(!document.body.classList.contains('sidebar-hidden'));
      });
    }

    if (settingsSidebarToggle) {
      settingsSidebarToggle.addEventListener('click', function () {
        const willCollapse = !settingsSidebarSection.classList.contains('is-collapsed');
        setSettingsMenuCollapsed(willCollapse, true);
      });
    }

    if (customerSidebarToggle) {
      customerSidebarToggle.addEventListener('click', function () {
        const willCollapse = !customerSidebarSection.classList.contains('is-collapsed');
        setCustomerMenuCollapsed(willCollapse, true);
      });
    }

    if (moreBtn) {
      moreBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openMobileMore();
      });
    }

    if (profileBtn && profileDropdown) {
      profileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = profileDropdown.style.display === 'block';
        closeProfileMenu();
        if (!isOpen) {
          profileDropdown.style.display = 'block';
          setDropdownState(profileBtn, true);
        }
      });
    }

    if (desktopProfileBtn && desktopProfileDropdown) {
      desktopProfileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = desktopProfileDropdown.style.display === 'block';
        closeProfileMenu();
        if (!isOpen) {
          desktopProfileDropdown.style.display = 'block';
          setDropdownState(desktopProfileBtn, true);
        }
      });
    }

    document.addEventListener('click', function (e) {
      const outsideMobileProfile = !profileDropdown || (!profileDropdown.contains(e.target) && e.target !== profileBtn);
      const outsideDesktopProfile = !desktopProfileDropdown || (!desktopProfileDropdown.contains(e.target) && e.target !== desktopProfileBtn);
      if (outsideMobileProfile && outsideDesktopProfile) {
        closeProfileMenu();
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
      closeProfileMenu();
      if (bottomMoreModal && bottomMoreModal.classList.contains('show')) {
        closeMobileMore();
      }

      if (typeof window.showSectionById === 'function') {
        window.showSectionById(target);
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
      } else {
        // Redirection for standalone pages back to admin Dashboard
        window.location.href = 'admin.php' + (bookingMode ? '?booking_mode=' + bookingMode : '') + '#' + target;
      }
    }

    document.querySelectorAll('.kinetic-sidebar a[data-target], .kinetic-topbar-brand[data-target], .bottom-nav .nav-btn[data-target], .bottom-more-content .nav-btn[data-target]').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('data-target');
        const bookingMode = this.getAttribute('data-booking-mode');
        showTargetSection(target, bookingMode);
      });
    });

    window.addEventListener('hashchange', function () {
      const hash = window.location.hash.replace('#', '') || 'dashboard';
      setTimeout(function () {
        syncAdminNavState(hash);
      }, 0);
    });

    const initialTarget = window.location.hash.replace('#', '') || 'dashboard';
    try {
      if (window.matchMedia('(min-width: 992px)').matches && window.localStorage.getItem(sidebarStorageKey) === '1') {
        document.body.classList.add('sidebar-hidden');
      }
      
      const storedSettingsState = window.localStorage.getItem(settingsMenuStorageKey);
      if (storedSettingsState === '1' || (!storedSettingsState && !isSettingsTarget(initialTarget))) {
        setSettingsMenuCollapsed(true, false);
      } else if (storedSettingsState === '0') {
        setSettingsMenuCollapsed(false, false);
      }

      const storedCustomerState = window.localStorage.getItem(customerMenuStorageKey);
      if (storedCustomerState === '1' || (!storedCustomerState && !isCustomerTarget(initialTarget))) {
        setCustomerMenuCollapsed(true, false);
      } else if (storedCustomerState === '0') {
        setCustomerMenuCollapsed(false, false);
      }
    } catch (err) {
      // Ignore unavailable storage.
    }
    syncDesktopSidebarButton();
    syncAdminNavState(initialTarget);
  });
</script>

