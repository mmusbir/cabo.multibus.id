<!-- BOOKINGS -->
<section id="bookings" class="card kinetic-command-bookings" data-active-mode="bookings" style="display:none;">
  <div class="kinetic-command-header">
    <div>
      <div class="kinetic-command-kicker" id="bookingPageKicker">Admin Panel</div>
      <h3 class="kinetic-command-title" id="bookingPageTitle">Data Keberangkatan</h3>
      <div class="booking-history-note" id="bookingHistoryNote" style="display:none;">Riwayat keberangkatan yang sudah lewat pada bulan ini.</div>
    </div>
  </div>

  <div class="kinetic-command-toolbar">
    <div class="kinetic-command-toolbar-start">
      <a href="index.php" id="bookingPrimaryAction" class="kinetic-command-add-btn">
        <i id="bookingPrimaryActionIcon" class="fa-solid fa-plus fa-icon"></i>
        <span id="bookingPrimaryActionText">Tambah Booking</span>
      </a>
    </div>

    <div class="kinetic-command-toolbar-actions">
      <div id="bookingFilterControls" class="booking-filter-controls">
        <div class="booking-scope-toggle" role="tablist" aria-label="Mode daftar booking">
          <button type="button" class="booking-scope-chip active" data-booking-scope="active">Aktif</button>
          <button type="button" class="booking-scope-chip" data-booking-scope="history">History</button>
        </div>
        <label class="booking-date-filter" for="booking_date_filter">
          <input type="date" id="booking_date_filter" class="form-control kinetic-command-select">
        </label>
        <button type="button" id="bookingDateReset" class="kinetic-command-refresh booking-filter-reset">
          <i class="fa-solid fa-xmark fa-icon"></i>
          Reset
        </button>
      </div>
      <div class="kinetic-command-toolbar-meta booking-per-page-meta">
        <select id="bookings_per_page" class="form-control kinetic-command-select">
          <option value="10">10</option>
          <option value="25" selected>25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
      <button type="button" class="kinetic-command-refresh" id="bookingToolbarRefresh">
        <i class="fa-solid fa-rotate-right fa-icon"></i>
        Refresh
      </button>
    </div>
  </div>


  <div class="kinetic-mobile-list-head">
    <h4 class="kinetic-mobile-list-title" id="bookingMobileListTitle">
      <i class="fa-regular fa-calendar-check fa-icon"></i>
      Jadwal Mendatang
    </h4>
    <div class="kinetic-mobile-list-actions">
      <button type="button" class="kinetic-mobile-icon-btn" id="bookingMobileRefresh" aria-label="Refresh daftar booking">
        <i class="fa-solid fa-rotate-right fa-icon"></i>
      </button>
    </div>
  </div>

  <div id="charterFilterRow" class="charter-command-filters no-scrollbar" style="display:none">
    <button type="button" class="charter-filter-chip active">Semua</button>
    <button type="button" class="charter-filter-chip">Belum Lunas</button>
    <button type="button" class="charter-filter-chip">Lunas Semua</button>
  </div>

  <div id="bookings_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="bookings_tbody" class="booking-cards-grid admin-bs-card-grid kinetic-command-list">
    <div class="small admin-grid-message">Loading...</div>
  </div>

  <div id="charters_tbody" class="booking-cards-grid admin-bs-card-grid kinetic-command-list" style="display:none">
    <div class="small admin-grid-message">Loading Charters...</div>
  </div>

  <div id="luggage_tbody" class="booking-cards-grid admin-bs-card-grid kinetic-command-list" style="display:none">
    <div class="small admin-grid-message">Loading Luggage...</div>
  </div>

  <script>
    function getBookingIconClass(iconName) {
      const map = {
        add: 'fa-solid fa-plus',
        add_circle: 'fa-solid fa-plus'
      };
      return map[iconName] || 'fa-solid fa-plus';
    }

    function getBookingListTitleIconHtml() {
      return '<i class="fa-regular fa-calendar-check fa-icon"></i>';
    }

    window.bookingDashboardState = window.bookingDashboardState || {
      active: 'bookings',
      totals: {
        bookings: 0,
        charters: 0,
        luggage: 0,
      },
      filters: {
        bookings: {
          scope: 'active',
          tanggal: '',
        }
      }
    };

    function getBookingFilters() {
      if (!window.bookingDashboardState.filters) {
        window.bookingDashboardState.filters = {};
      }
      if (!window.bookingDashboardState.filters.bookings) {
        window.bookingDashboardState.filters.bookings = { scope: 'active', tanggal: '' };
      }
      return window.bookingDashboardState.filters.bookings;
    }

    function syncBookingFilterUi() {
      const filters = getBookingFilters();
      const bookingsMode = window.bookingDashboardState.active === 'bookings';
      const filterControls = document.getElementById('bookingFilterControls');
      const dateInput = document.getElementById('booking_date_filter');
      const pageTitle = document.getElementById('bookingPageTitle');
      const mobileListTitle = document.getElementById('bookingMobileListTitle');
      const historyNote = document.getElementById('bookingHistoryNote');

      if (filterControls) {
        filterControls.style.display = bookingsMode ? 'flex' : 'none';
      }
      document.querySelectorAll('[data-booking-scope]').forEach((chip) => {
        chip.classList.toggle('active', chip.getAttribute('data-booking-scope') === filters.scope);
      });
      if (dateInput) {
        dateInput.value = filters.tanggal || '';
      }
      if (bookingsMode && pageTitle) {
        pageTitle.textContent = filters.scope === 'history' ? 'History Booking Bulan Ini' : 'Data Keberangkatan';
      }
      if (historyNote) {
        historyNote.style.display = bookingsMode && filters.scope === 'history' ? 'block' : 'none';
      }
      if (bookingsMode && mobileListTitle) {
        const titleText = filters.scope === 'history' ? 'History Bulan Ini' : 'Jadwal Mendatang';
        mobileListTitle.innerHTML = getBookingListTitleIconHtml() + titleText;
      }
    }

    function getBookingListQueryParams(overrides = {}) {
      const filters = getBookingFilters();
      const params = Object.assign({}, overrides);
      params.scope = filters.scope || 'active';
      if (filters.tanggal) {
        params.tanggal = filters.tanggal;
      } else {
        delete params.tanggal;
      }
      return params;
    }

    window.getAdminListParams = function (target, baseParams = {}) {
      if (target !== 'bookings') {
        return baseParams;
      }
      return getBookingListQueryParams(baseParams);
    };

    function getBookingModeMeta(mode) {
      if (mode === 'charters') {
        return {
          label: 'Carter',
          totalLabel: 'Charters',
          state: 'loading',
          badge: 'ACTIVE',
          headline: 'Data Carter',
          info: 'Pantau data carter, status konfirmasi, customer, dan armada pada satu command canvas.',
          tag: 'Fleet Operations',
          context: 'Queue',
          pageKicker: 'Fleet Operations',
          pageTitle: 'Data Carter',
          pageSubtitle: 'Kelola semua order carter dengan tampilan list editorial yang fokus pada customer, rute, jadwal, dan status operasional.',
          searchPlaceholder: 'Cari ID carter, customer, driver, atau rute...',
          mobileTitle: 'Data Carter',
          primaryActionHref: '#charter-create',
          primaryActionText: 'Tambah Carter',
          primaryActionIcon: 'add_circle',
        };
      }
      if (mode === 'luggage') {
        return {
          label: 'Bagasi',
          totalLabel: 'Shipments',
          state: 'scheduled',
          badge: 'SCHEDULED',
          headline: 'Layanan Bagasi',
          info: 'Kelola input, pembayaran, dan pembatalan bagasi pada tampilan komando yang sama.',
          tag: 'Cargo Flow',
          context: 'Queue',
          pageKicker: 'Cargo Command',
          pageTitle: 'Data Bagasi',
          pageSubtitle: 'Kelola pengiriman bagasi, status pembayaran, dan tindak lanjut operasional dari satu halaman.',
          searchPlaceholder: 'Cari pengirim, tujuan, nomor bagasi, atau penerima...',
          mobileTitle: 'Data Bagasi',
          primaryActionHref: 'index.php',
          primaryActionText: 'Tambah Booking',
          primaryActionIcon: 'add',
        };
      }
      return {
        label: 'Reguler',
        totalLabel: 'Trip Schedules',
        state: 'neutral',
        badge: '',
        headline: 'Data Booking',
        info: 'Pantau keberangkatan, driver, dan total booking customer per jadwal sebelum membuka detail booking.',
        tag: 'Manifest Queue',
        context: 'Live',
          pageKicker: 'Admin Panel',
        pageTitle: 'Data Keberangkatan',
        pageSubtitle: 'Real-time schedule monitoring and dispatch control untuk operasional keberangkatan, carter, dan bagasi.',
        searchPlaceholder: 'Cari rute, driver, penumpang, atau jam...',
        mobileTitle: 'Jadwal Mendatang',
        primaryActionHref: 'index.php',
        primaryActionText: 'Tambah Booking',
        primaryActionIcon: 'add',
      };
    }

    function updateBookingModeMeta(mode) {
      const meta = getBookingModeMeta(mode);
      const total = Number(window.bookingDashboardState.totals[mode] || 0);
      const metricMode = document.getElementById('bookingMetricMode');
      const metricContext = document.getElementById('bookingMetricContext');
      const metricTotal = document.getElementById('bookingMetricTotal');
      const metricLabel = document.getElementById('bookingMetricLabel');
      const summaryHeadline = document.getElementById('bookingSummaryHeadline');
      const info = document.getElementById('bookings_info');
      const pageKicker = document.getElementById('bookingPageKicker');
      const pageTitle = document.getElementById('bookingPageTitle');
      const pageSubtitle = document.getElementById('bookingPageSubtitle');
      const mobileListTitle = document.getElementById('bookingMobileListTitle');
      const bookingsSection = document.getElementById('bookings');
      const charterFilterRow = document.getElementById('charterFilterRow');
      const primaryAction = document.getElementById('bookingPrimaryAction');
      const primaryActionText = document.getElementById('bookingPrimaryActionText');
      const primaryActionIcon = document.getElementById('bookingPrimaryActionIcon');

      if (metricMode) metricMode.textContent = meta.label;
      if (metricContext) metricContext.textContent = meta.context;
      if (metricTotal) metricTotal.textContent = total.toLocaleString('id-ID');
      if (metricLabel) metricLabel.textContent = meta.totalLabel;
      const modeChipLabel = document.getElementById('bookingModeChipLabel');
      if (modeChipLabel) modeChipLabel.textContent = meta.label;
      if (summaryHeadline) summaryHeadline.textContent = meta.headline;
      if (info) info.textContent = meta.info;
      if (pageKicker) pageKicker.textContent = meta.pageKicker;
      if (pageTitle) pageTitle.textContent = meta.pageTitle;
      if (pageSubtitle) pageSubtitle.textContent = meta.pageSubtitle;
      if (primaryAction) {
        primaryAction.href = meta.primaryActionHref || 'index.php';
        if (mode === 'charters') {
          primaryAction.setAttribute('data-target', 'charter-create');
          primaryAction.setAttribute('data-booking-mode', 'charters');
        } else {
          primaryAction.removeAttribute('data-target');
          primaryAction.removeAttribute('data-booking-mode');
        }
      }
      if (primaryActionText) primaryActionText.textContent = meta.primaryActionText || 'Tambah Booking';
      if (primaryActionIcon) {
        primaryActionIcon.className = getBookingIconClass(meta.primaryActionIcon || 'add') + ' fa-icon';
      }
      if (mobileListTitle) {
        mobileListTitle.innerHTML = getBookingListTitleIconHtml() + meta.mobileTitle;
      }
      if (bookingsSection) bookingsSection.setAttribute('data-active-mode', mode);
      if (charterFilterRow) charterFilterRow.style.display = mode === 'charters' ? 'flex' : 'none';
    }

    window.updateBookingCommandSummary = function (mode, total) {
      if (!window.bookingDashboardState.totals) {
        window.bookingDashboardState.totals = { bookings: 0, charters: 0, luggage: 0 };
      }
      window.bookingDashboardState.totals[mode] = Number(total || 0);
      if (window.bookingDashboardState.active === mode) {
        updateBookingModeMeta(mode);
        if (mode === 'bookings') syncBookingFilterUi();
      }
    };

    function formatBookingTripDate(tanggalRaw) {
      if (!tanggalRaw) return '-';
      const d = new Date(tanggalRaw + 'T00:00:00');
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return months[d.getMonth()] + ' ' + String(d.getDate()).padStart(2, '0') + ', ' + d.getFullYear();
    }

    function buildBookingTripCopyText(root, meta) {
      const occupied = [];
      root.querySelectorAll('.seat-block').forEach(block => {
        const name = block.querySelector('.sb-val.name')?.innerText.trim() || '';
        if (!name) return;
        occupied.push({
          seat: block.querySelector('.seat-badge-num')?.innerText.replace('Kursi ', '').trim() || '',
          name,
          phone: block.querySelector('.sb-val.phone')?.innerText.trim() || '',
          pickup: block.querySelector('.sb-val.pickup')?.innerText.trim() || '',
          gmaps: block.querySelector('.sb-val.gmaps')?.innerText.trim() || '',
          pay: block.querySelector('.sb-val.pay')?.innerText.trim() || ''
        });
      });

      const driverInfo = root.querySelector('#departureInfoCard');
      const driverName = driverInfo ? (driverInfo.getAttribute('data-driver-name') || '-') : '-';
      const tanggalFormatted = formatBookingTripDate(meta.tanggal);
      const jamFormatted = meta.jam ? meta.jam.replace(':', '.') : '';

      let text = `Info Pemberangkatan\nTanggal & Jam: ${tanggalFormatted} - ${jamFormatted}\nRute: ${meta.rute}\nTotal Penumpang: ${occupied.length}\nDriver: ${driverName}\n\n`;
      occupied.forEach(s => {
        text += `- Kursi: ${s.seat}\nNama: ${s.name}\nNo. HP: ${s.phone}\nTitik Jemput: ${s.pickup}\nGmaps: ${s.gmaps}\nPembayaran: ${s.pay}\n\n`;
      });

      const summaryDiv = root.querySelector('#passengerSummary');
      if (summaryDiv) {
        const paid = parseInt(summaryDiv.getAttribute('data-paid') || '0', 10);
        const unpaid = parseInt(summaryDiv.getAttribute('data-unpaid') || '0', 10);
        text += `Ringkasan Pembayaran\n`;
        text += `Sudah Lunas: Rp ${paid.toLocaleString('id-ID')}\n`;
        text += `Belum Lunas: Rp ${unpaid.toLocaleString('id-ID')}\n`;
        text += `Total Estimasi: Rp ${(paid + unpaid).toLocaleString('id-ID')}\n`;
      }

      return text;
    }

    function fallbackBookingTripCopy(text) {
      const temp = document.createElement('textarea');
      temp.value = text;
      document.body.appendChild(temp);
      temp.select();
      try {
        document.execCommand('copy');
        customAlert('Semua detail penumpang berhasil disalin!');
      } catch (e) {
        customAlert('Gagal menyalin ke clipboard.');
      }
      document.body.removeChild(temp);
    }

    window.copyBookingTripManifest = async function (trigger) {
      const meta = {
        rute: trigger.getAttribute('data-rute') || '',
        tanggal: trigger.getAttribute('data-tanggal') || '',
        jam: trigger.getAttribute('data-jam') || '',
        unit: trigger.getAttribute('data-unit') || '1',
      };

      try {
        const url = new URL('admin.php', window.location.origin);
        url.searchParams.set('action', 'getPassengers');
        url.searchParams.set('rute', meta.rute);
        url.searchParams.set('tanggal', meta.tanggal);
        url.searchParams.set('jam', meta.jam);
        url.searchParams.set('unit', meta.unit);

        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        const js = typeof window.parseAdminApiResponse === 'function'
          ? await window.parseAdminApiResponse(res)
          : await res.json();
        if (!js.success || !js.html) {
          customAlert('Tidak ada data booking untuk jadwal ini.');
          return;
        }

        const parser = new DOMParser();
        const doc = parser.parseFromString(js.html, 'text/html');
        const text = buildBookingTripCopyText(doc, meta);

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
          navigator.clipboard.writeText(text).then(() => {
            customAlert('Semua detail penumpang berhasil disalin!');
          }).catch(() => fallbackBookingTripCopy(text));
        } else {
          fallbackBookingTripCopy(text);
        }
      } catch (e) {
        customAlert('Gagal memuat data detail booking.');
      }
    };

    window.openBookingTripDetail = async function (trigger) {
      const rute = trigger.getAttribute('data-rute') || '';
      const tanggal = trigger.getAttribute('data-tanggal') || '';
      const jam = trigger.getAttribute('data-jam') || '';
      const unit = trigger.getAttribute('data-unit') || '1';

      const viewRoute = document.getElementById('booking_detail_rute');
      const viewTanggal = document.getElementById('booking_detail_tanggal');
      const viewJam = document.getElementById('booking_detail_jam');
      const viewUnit = document.getElementById('booking_detail_unit');

      if (!viewRoute || !viewTanggal || !viewJam || !viewUnit) {
        customAlert('Panel detail booking belum tersedia.');
        return;
      }

      viewRoute.value = rute;
      viewTanggal.value = tanggal;
      viewJam.value = jam;
      viewUnit.value = unit;

      if (typeof window.showSectionById === 'function') {
        window.showSectionById('booking-detail');
      }
      window.location.hash = '#booking-detail';
      document.getElementById('booking-detail')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (typeof window.loadBookingDetailPassengers === 'function') {
        await window.loadBookingDetailPassengers();
      }
    };

    function refreshActiveBookingMode() {
      const target = window.bookingDashboardState.active || 'bookings';
      let params = {
        page: 1,
        per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10),
        search: ''
      };
      if (target === 'bookings') {
        params = getBookingListQueryParams(params);
      }
      ajaxListLoad(target, params);
    }

    function switchAdminView(mode) {
      window.bookingDashboardState.active = mode;

      document.getElementById('bookings_tbody').style.display = 'none';
      document.getElementById('charters_tbody').style.display = 'none';
      document.getElementById('luggage_tbody').style.display = 'none';

      updateBookingModeMeta(mode);
      syncBookingFilterUi();

      if (mode === 'charters') {
        document.getElementById('charters_tbody').style.display = 'grid';
        if (document.getElementById('charters_tbody').children.length <= 1) {
          ajaxListLoad('charters', { page: 1, per_page: 999, search: '' });
        }
      } else if (mode === 'luggage') {
        document.getElementById('luggage_tbody').style.display = 'grid';
        if (document.getElementById('luggage_tbody').children.length <= 1) {
          ajaxListLoad('luggage', { page: 1, per_page: 999, search: '' });
        }
      } else {
        document.getElementById('bookings_tbody').style.display = 'grid';
      }

      if (typeof window.syncAdminNavState === 'function') {
        window.syncAdminNavState('bookings');
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const bookingRefreshBtn = document.getElementById('bookingToolbarRefresh');
      const bookingDateInput = document.getElementById('booking_date_filter');
      const bookingDateReset = document.getElementById('bookingDateReset');

      document.querySelectorAll('.charter-filter-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
          document.querySelectorAll('.charter-filter-chip').forEach((item) => item.classList.remove('active'));
          chip.classList.add('active');
        });
      });

      document.querySelectorAll('[data-booking-scope]').forEach((chip) => {
        chip.addEventListener('click', () => {
          const nextScope = chip.getAttribute('data-booking-scope') || 'active';
          const filters = getBookingFilters();
          filters.scope = nextScope;
          syncBookingFilterUi();
          ajaxListLoad('bookings', getBookingListQueryParams({
            page: 1,
            per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10),
            search: ''
          }));
        });
      });

      if (bookingDateInput) {
        bookingDateInput.addEventListener('change', () => {
          const filters = getBookingFilters();
          filters.tanggal = bookingDateInput.value || '';
          ajaxListLoad('bookings', getBookingListQueryParams({
            page: 1,
            per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10),
            search: ''
          }));
        });
      }

      if (bookingDateReset) {
        bookingDateReset.addEventListener('click', () => {
          const filters = getBookingFilters();
          filters.tanggal = '';
          if (bookingDateInput) bookingDateInput.value = '';
          ajaxListLoad('bookings', getBookingListQueryParams({
            page: 1,
            per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10),
            search: ''
          }));
        });
      }

      if (mobileRefreshBtn) {
        mobileRefreshBtn.addEventListener('click', refreshActiveBookingMode);
      }
      if (bookingRefreshBtn) {
        bookingRefreshBtn.addEventListener('click', refreshActiveBookingMode);
      }
      const primaryActionBtn = document.getElementById('bookingPrimaryAction');
      if (primaryActionBtn) {
        primaryActionBtn.addEventListener('click', (event) => {
          const target = primaryActionBtn.getAttribute('data-target');
          if (!target) return;
          event.preventDefault();
          if (typeof window.showSectionById === 'function') {
            window.showSectionById(target);
          }
          window.location.hash = '#' + target;
        });
      }
      const urlParams = new URLSearchParams(window.location.search);
      const initialMode = urlParams.get('booking_mode');
      if (initialMode === 'charters' || initialMode === 'luggage' || initialMode === 'bookings') {
        switchAdminView(initialMode);
      } else {
        switchAdminView('bookings');
      }
    });
  </script>
</section>
