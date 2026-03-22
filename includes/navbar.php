<?php 
  $auth = getAuthenticatedUser();
  if ($auth): 
?>
  <div class="topbar navbar navbar-expand-lg sticky-top">
    <div class="topbar-inner container-fluid">
      <div class="topbar-mobile-title">
        <span class="topbar-mobile-kicker">Admin</span>
        <strong>Control Center</strong>
      </div>
      <nav class="nav d-flex flex-wrap align-items-center" id="siteNav">
        <a href="#bookings" data-target="bookings">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
          </svg>
          <span>Bookings</span>
        </a>
        <a href="#view" data-target="view">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <span>View</span>
        </a>
        <div class="nav-more">
          <button id="moreMenuBtn" type="button" aria-haspopup="true" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="1"></circle>
              <circle cx="19" cy="12" r="1"></circle>
              <circle cx="5" cy="12" r="1"></circle>
            </svg>
            <span>More</span>
          </button>
          <div id="moreMenuDropdown">
            <div class="menu-section-header">Operasional</div>
            <a href="#customers" data-target="customers">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              Customers
            </a>
            <a href="#schedules" data-target="schedules">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              Jadwal
            </a>
            <a href="#cancellations" data-target="cancellations">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              Cancellations
            </a>
            <a href="#reports" data-target="reports">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
              </svg>
              Report
            </a>

            <div class="menu-section-header">Data Master</div>
            <a href="#routes" data-target="routes">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
              </svg>
              Rute
            </a>
            <a href="#segments" data-target="segments">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M2 12h20"></path>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                </path>
              </svg>
              Segment
            </a>
            <a href="#luggage_services" data-target="luggage_services">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                <path d="m3.3 7 8.7 5 8.7-5"></path>
                <path d="M12 22V12"></path>
              </svg>
              Layanan Bagasi
            </a>

            <div class="menu-section-header">Armada & SDM</div>
            <a href="#units" data-target="units">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
              </svg>
              Unit Kendaraan
            </a>
            <a href="#drivers" data-target="drivers">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Data Driver
            </a>

            <div class="menu-section-header">Pengaturan</div>
            <a href="#users" data-target="users">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Users
            </a>
          </div>
        </div>
      </nav>
      <div class="topbar-right d-flex align-items-center">
        <a href="index.php" class="inline-small btn-booking">Booking Area</a>

        <div class="profile-dropdown">
          <button class="profile-btn" id="profileMenuBtn" type="button" aria-haspopup="true" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span><?php echo htmlspecialchars($auth['user'] ?? 'Admin'); ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="profile-chevron">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </button>
          <div class="profile-menu" id="profileMenuDropdown">
            <a href="javascript:void(0)" id="btnOpenChangePassword">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                <path d="m9 12 2 2 4-4" />
              </svg>
              Ganti Password
            </a>
            <div class="menu-divider"></div>
            <?php if ($auth): ?>
              <a href="logout.php" class="logout-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                  <polyline points="16 17 21 12 16 7" />
                  <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Logout
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="topbar">
    <div class="topbar-inner topbar-public">
      <a href="index.php" class="inline-small btn-booking">Buat Booking</a>
    </div>
  </div>
<?php endif; ?>

<!-- Bottom Navbar for Mobile -->
<?php if ($auth): ?>
  <nav class="bottom-nav" id="bottomNav">
    <a href="#bookings" class="nav-btn" data-target="bookings" id="navBookings">
      <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
          <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        </svg></div>
      <span class="nav-label">Bookings</span>
    </a>
    <a href="#view" class="nav-btn" data-target="view" id="navView">
      <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg></div>
      <span class="nav-label">View</span>
    </a>
    <button class="nav-btn" id="navMore">
      <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="1"></circle>
          <circle cx="19" cy="12" r="1"></circle>
          <circle cx="5" cy="12" r="1"></circle>
        </svg></div>
      <span class="nav-label">More</span>
    </button>
  </nav>
  <div class="bottom-more-modal" id="bottomMoreModal">
    <div class="bottom-more-content">
      <div class="sheet-handle"></div>
      
      <div class="menu-section-header">Operasional</div>
      <div class="menu-grid">
        <a href="#customers" class="nav-btn" data-target="customers">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg></div>
          <span class="nav-label">Customers</span>
        </a>
        <a href="#schedules" class="nav-btn" data-target="schedules">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg></div>
          <span class="nav-label">Jadwal</span>
        </a>
        <a href="#cancellations" class="nav-btn" data-target="cancellations">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg></div>
          <span class="nav-label">Cancellations</span>
        </a>
        <a href="#reports" class="nav-btn" data-target="reports">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="20" x2="18" y2="10"></line>
              <line x1="12" y1="20" x2="12" y2="4"></line>
              <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg></div>
          <span class="nav-label">Report</span>
        </a>
      </div>

      <div class="menu-section-header">Data Master</div>
      <div class="menu-grid">
        <a href="#routes" class="nav-btn" data-target="routes">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
              <line x1="8" y1="2" x2="8" y2="18"></line>
              <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg></div>
          <span class="nav-label">Rute</span>
        </a>
        <a href="#segments" class="nav-btn" data-target="segments">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M2 12h20"></path>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg></div>
          <span class="nav-label">Segment</span>
        </a>
        <a href="#luggage_services" class="nav-btn" data-target="luggage_services">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
              <path d="m3.3 7 8.7 5 8.7-5"></path>
              <path d="M12 22V12"></path>
            </svg></div>
          <span class="nav-label">Layanan Bagasi</span>
        </a>
      </div>

      <div class="menu-section-header">Armada & SDM</div>
      <div class="menu-grid">
        <a href="#units" class="nav-btn" data-target="units">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="15" height="13"></rect>
              <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
              <circle cx="5.5" cy="18.5" r="2.5"></circle>
              <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg></div>
          <span class="nav-label">Unit Kendaraan</span>
        </a>
        <a href="#drivers" class="nav-btn" data-target="drivers">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg></div>
          <span class="nav-label">Data Driver</span>
        </a>
      </div>

      <div class="menu-section-header">Sistem</div>
      <div class="menu-grid">
        <a href="#users" class="nav-btn" data-target="users">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg></div>
          <span class="nav-label">Users</span>
        </a>
        <button class="nav-btn nav-btn-close" id="closeMoreModal" type="button">
          <div class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg></div>
          <span class="nav-label">Tutup</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Floating Action Button for Mobile -->
  <a href="index.php" class="fab-booking" title="Buat Booking">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
      <line x1="12" y1="5" x2="12" y2="19"></line>
      <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
  </a>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    function setDropdownState(button, isOpen) {
      if (!button) return;
      button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function syncAdminNavState(target) {
      const selector = '.nav > a[data-target], #moreMenuDropdown a[data-target], .bottom-nav .nav-btn[data-target], .bottom-more-content .nav-btn[data-target]';
      document.querySelectorAll(selector).forEach(link => {
        link.classList.toggle('active', link.getAttribute('data-target') === target);
      });

      const desktopMoreBtn = document.getElementById('moreMenuBtn');
      const mobileMoreBtn = document.getElementById('navMore');
      const isPrimary = target === 'bookings' || target === 'view';
      if (desktopMoreBtn) desktopMoreBtn.classList.toggle('active', !isPrimary);
      if (mobileMoreBtn) mobileMoreBtn.classList.toggle('active', !isPrimary);
    }

    window.syncAdminNavState = syncAdminNavState;

    // Desktop More menu
    const moreBtn = document.getElementById('moreMenuBtn');
    const moreDropdown = document.getElementById('moreMenuDropdown');
    if (moreBtn && moreDropdown) {
      moreBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = moreDropdown.style.display === 'block';
        moreDropdown.style.display = isOpen ? 'none' : 'block';
        setDropdownState(moreBtn, !isOpen);
        if (profileDropdown) {
          profileDropdown.style.display = 'none';
          setDropdownState(profileBtn, false);
        }
      });
      document.addEventListener('click', function (e) {
        if (!moreDropdown.contains(e.target) && e.target !== moreBtn) {
          moreDropdown.style.display = 'none';
          setDropdownState(moreBtn, false);
        }
      });

      // Close dropdown when clicking a link inside
      moreDropdown.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function () {
          moreDropdown.style.display = 'none';
          setDropdownState(moreBtn, false);
        });
      });
    }

    // Profile Dropdown logic
    const profileBtn = document.getElementById('profileMenuBtn');
    const profileDropdown = document.getElementById('profileMenuDropdown');
    if (profileBtn && profileDropdown) {
      profileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = profileDropdown.style.display === 'block';
        profileDropdown.style.display = isOpen ? 'none' : 'block';
        setDropdownState(profileBtn, !isOpen);
        if (moreDropdown) {
          moreDropdown.style.display = 'none';
          setDropdownState(moreBtn, false);
        }
      });
      document.addEventListener('click', function (e) {
        if (!profileDropdown.contains(e.target) && e.target !== profileBtn) {
          profileDropdown.style.display = 'none';
          setDropdownState(profileBtn, false);
        }
      });
    }

    function showTargetSection(target) {
      if (!target) return;
      const currentHash = window.location.hash.replace('#', '');
      syncAdminNavState(target);
      if (currentHash === target) {
        if (typeof window.showSectionById === 'function') {
          window.showSectionById(target);
        }
        return;
      }
      window.location.hash = target;
    }

    // Desktop navigation active state
    const navLinks = document.querySelectorAll('.nav > a, #moreMenuDropdown a');
    navLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('data-target');
        showTargetSection(target);
      });
    });

    // Handle initial load from hash
    const hash = window.location.hash.substring(1);
    if (hash) {
      syncAdminNavState(hash);
    } else {
      syncAdminNavState('bookings');
    }

    // Mobile More Menu
    const navMore = document.getElementById('navMore');
    const bottomMoreModal = document.getElementById('bottomMoreModal');
    const closeMoreModal = document.getElementById('closeMoreModal');

    if (navMore && bottomMoreModal) {
      navMore.onclick = function () {
        bottomMoreModal.style.display = 'flex';
        navMore.classList.add('active');
        // Force reflow
        bottomMoreModal.offsetHeight;
        bottomMoreModal.classList.add('show');
      };
    }
    if (closeMoreModal && bottomMoreModal) {
      closeMoreModal.onclick = function () {
        bottomMoreModal.classList.remove('show');
        const activeTarget = window.location.hash.replace('#', '') || 'bookings';
        syncAdminNavState(activeTarget);
        setTimeout(() => {
          if (!bottomMoreModal.classList.contains('show')) {
            bottomMoreModal.style.display = 'none';
          }
        }, 300);
      };
    }
    if (bottomMoreModal) {
      bottomMoreModal.onclick = function (e) {
        if (e.target === this) closeMoreModal.click();
      };

      // Close modal when clicking a navigation link
      bottomMoreModal.querySelectorAll('.nav-btn[data-target]').forEach(btn => {
        btn.addEventListener('click', function () {
          bottomMoreModal.style.display = 'none';
          bottomMoreModal.classList.remove('show');
        });
      });
    }

    // Mobile bottom nav active states
    const bottomNavBtns = document.querySelectorAll('.bottom-nav .nav-btn[data-target], .bottom-more-content .nav-btn[data-target]');
    bottomNavBtns.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('data-target');
        showTargetSection(target);
      });
    });
  });
</script>
