<!-- BOOKINGS -->
<section id="bookings" class="card kinetic-command-bookings">
  <div class="kinetic-command-header">
    <div>
      <div class="kinetic-command-kicker">Kinetic Command</div>
      <h3 class="kinetic-command-title">Data Keberangkatan</h3>
      <p class="kinetic-command-subtitle">Real-time schedule monitoring and dispatch control untuk booking reguler, carter, dan bagasi.</p>
    </div>
    <div class="kinetic-command-metrics">
      <div class="kinetic-metric-card kinetic-metric-primary">
        <span class="kinetic-metric-label">Mode Aktif</span>
        <div class="kinetic-metric-value-row">
          <strong id="bookingMetricMode">Reguler</strong>
          <span id="bookingMetricContext">Live</span>
        </div>
      </div>
      <div class="kinetic-metric-card kinetic-metric-secondary">
        <span class="kinetic-metric-label">Total Data</span>
        <div class="kinetic-metric-value-row">
          <strong id="bookingMetricTotal">0</strong>
          <span id="bookingMetricLabel">Bookings</span>
        </div>
      </div>
    </div>
  </div>

  <div class="kinetic-command-toolbar">
    <div class="kinetic-command-tabs toggle-container grid-cols-3 w-full max-w-400" id="admin-mode-toggle-container">
      <div class="toggle-slider"></div>
      <button type="button" class="toggle-btn active" id="btn-view-reguler" onclick="switchAdminView('bookings')">Reguler</button>
      <button type="button" class="toggle-btn" id="btn-view-carter" onclick="switchAdminView('charters')">Carter</button>
      <button type="button" class="toggle-btn" id="btn-view-bagasi" onclick="switchAdminView('luggage')">Bagasi</button>
    </div>

    <div class="kinetic-command-toolbar-actions">
      <div class="search-bar-modern admin-bs-search kinetic-command-search">
        <input type="text" id="search_name_input" class="search-input-modern" placeholder="Cari rute, driver, penumpang, atau jam...">
        <button type="button" id="searchBtn" class="search-btn-icon" aria-label="Cari booking">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </button>
      </div>

      <div class="kinetic-command-toolbar-meta">
        <label class="small" for="bookings_per_page">Per page</label>
        <select id="bookings_per_page" class="form-select form-select-sm kinetic-command-select">
          <option>10</option>
          <option selected>25</option>
          <option>50</option>
          <option>100</option>
        </select>
      </div>

      <button type="button" id="bookingRefreshBtn" class="kinetic-command-refresh">
        <span class="material-symbols-outlined">refresh</span>
        Refresh
      </button>
    </div>
  </div>

  <div class="kinetic-command-summary-bar">
    <div class="kinetic-command-summary-copy">
      <span id="bookingSummaryStatus" class="kinetic-status-chip" data-state="ready">
        <span class="kinetic-status-dot"></span>
        READY
      </span>
      <div>
        <div id="bookingSummaryHeadline" class="kinetic-summary-headline">Booking Reguler</div>
        <div id="bookings_info" class="small kinetic-summary-text">Memuat data booking reguler hari ini...</div>
      </div>
    </div>
    <div class="kinetic-command-summary-side">
      <span class="kinetic-summary-tag" id="bookingSummaryTag">Dispatch View</span>
      <span class="kinetic-summary-meta" id="bookingSummaryMeta">Desktop list</span>
    </div>
  </div>

  <div class="kinetic-mobile-list-head">
    <h4 class="kinetic-mobile-list-title">
      <span class="material-symbols-outlined">event_note</span>
      Jadwal Mendatang
    </h4>
    <div class="kinetic-mobile-list-actions">
      <button type="button" class="kinetic-mobile-icon-btn" id="bookingMobileFocusSearch" aria-label="Fokus ke pencarian">
        <span class="material-symbols-outlined">search</span>
      </button>
      <button type="button" class="kinetic-mobile-icon-btn" id="bookingMobileRefresh" aria-label="Refresh daftar booking">
        <span class="material-symbols-outlined">refresh</span>
      </button>
    </div>
  </div>

  <div id="bookings_spinner_wrap" class="spinner-wrap" style="display:none">
    <div class="ajax-spinner"></div>
  </div>

  <div id="bookings_tbody" class="booking-cards-grid admin-bs-card-grid kinetic-command-list">
    <div class="small admin-grid-message">Loading...</div>
  </div>
  <div id="bookings_pagination" class="admin-pagination-host"></div>

  <div id="charters_tbody" class="booking-cards-grid admin-bs-card-grid kinetic-command-list" style="display:none">
    <div class="small admin-grid-message">Loading Charters...</div>
  </div>
  <div id="charters_pagination" class="admin-pagination-host" style="display:none"></div>

  <div id="luggage_tbody" class="booking-cards-grid admin-bs-card-grid kinetic-command-list" style="display:none">
    <div class="small admin-grid-message">Loading Luggage...</div>
  </div>
  <div id="luggage_pagination" class="admin-pagination-host" style="display:none"></div>

  <script>
    window.bookingDashboardState = window.bookingDashboardState || {
      active: 'bookings',
      totals: {
        bookings: 0,
        charters: 0,
        luggage: 0,
      },
    };

    function getBookingModeMeta(mode) {
      if (mode === 'charters') {
        return {
          label: 'Carter',
          totalLabel: 'Charters',
          state: 'loading',
          badge: 'LOADING',
          headline: 'Manifest Carter',
          info: 'Pantau charter aktif, unit, driver, dan BOP dari satu list desktop.',
          tag: 'Fleet Charter',
          context: 'Ready',
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
        };
      }
      return {
        label: 'Reguler',
        totalLabel: 'Trip Schedules',
        state: 'ready',
        badge: 'READY',
        headline: 'Trip Booking Reguler',
        info: 'Pantau keberangkatan, driver, dan total booking customer per jadwal sebelum membuka detail manifest.',
        tag: 'Manifest Queue',
        context: 'Live',
      };
    }

    function updateBookingModeMeta(mode) {
      const meta = getBookingModeMeta(mode);
      const total = Number(window.bookingDashboardState.totals[mode] || 0);
      const metricMode = document.getElementById('bookingMetricMode');
      const metricContext = document.getElementById('bookingMetricContext');
      const metricTotal = document.getElementById('bookingMetricTotal');
      const metricLabel = document.getElementById('bookingMetricLabel');
      const summaryStatus = document.getElementById('bookingSummaryStatus');
      const summaryHeadline = document.getElementById('bookingSummaryHeadline');
      const summaryTag = document.getElementById('bookingSummaryTag');
      const summaryMeta = document.getElementById('bookingSummaryMeta');
      const info = document.getElementById('bookings_info');

      if (metricMode) metricMode.textContent = meta.label;
      if (metricContext) metricContext.textContent = meta.context;
      if (metricTotal) metricTotal.textContent = total.toLocaleString('id-ID');
      if (metricLabel) metricLabel.textContent = meta.totalLabel;
      if (summaryHeadline) summaryHeadline.textContent = meta.headline;
      if (summaryTag) summaryTag.textContent = meta.tag;
      if (summaryMeta) summaryMeta.textContent = 'Total ' + total.toLocaleString('id-ID') + ' data';
      if (info) info.textContent = meta.info;
      if (summaryStatus) {
        summaryStatus.setAttribute('data-state', meta.state);
        summaryStatus.innerHTML = '<span class="kinetic-status-dot"></span>' + meta.badge;
      }
    }

    window.updateBookingCommandSummary = function (mode, total) {
      if (!window.bookingDashboardState.totals) {
        window.bookingDashboardState.totals = { bookings: 0, charters: 0, luggage: 0 };
      }
      window.bookingDashboardState.totals[mode] = Number(total || 0);
      if (window.bookingDashboardState.active === mode) {
        updateBookingModeMeta(mode);
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
        const url = new URL('admin/ajax.php', window.location.origin);
        url.searchParams.set('action', 'getPassengers');
        url.searchParams.set('rute', meta.rute);
        url.searchParams.set('tanggal', meta.tanggal);
        url.searchParams.set('jam', meta.jam);
        url.searchParams.set('unit', meta.unit);

        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        const js = await res.json();
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
        customAlert('Gagal memuat data copy manifest.');
      }
    };

    window.openBookingTripDetail = async function (trigger) {
      const rute = trigger.getAttribute('data-rute') || '';
      const tanggal = trigger.getAttribute('data-tanggal') || '';
      const jam = trigger.getAttribute('data-jam') || '';
      const unit = trigger.getAttribute('data-unit') || '1';

      const viewRoute = document.getElementById('view_rute');
      const viewTanggal = document.getElementById('view_tanggal');
      const viewJam = document.getElementById('view_jam');
      const viewUnit = document.getElementById('view_unit');

      if (!viewRoute || !viewTanggal || !viewJam || !viewUnit) {
        customAlert('Panel view belum tersedia.');
        return;
      }

      viewRoute.value = rute;
      viewTanggal.value = tanggal;

      if (typeof window.refreshViewJamOptions === 'function') {
        await window.refreshViewJamOptions();
      }

      if (![...viewJam.options].some(opt => opt.value === jam)) {
        viewJam.innerHTML += `<option value="${jam}">${jam}</option>`;
      }
      viewJam.value = jam;

      if (![...viewUnit.options].some(opt => opt.value === unit)) {
        viewUnit.innerHTML += `<option value="${unit}">Unit ${unit}</option>`;
      }
      viewUnit.value = unit;

      if (typeof window.showSectionById === 'function') {
        window.showSectionById('view');
      }
      window.location.hash = '#view';
      document.getElementById('view')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      document.getElementById('btnLoadPassengers')?.click();
    };

    function refreshActiveBookingMode() {
      const target = window.bookingDashboardState.active || 'bookings';
      ajaxListLoad(target, {
        page: 1,
        per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10),
        search: document.getElementById('search_name_input')?.value || ''
      });
    }

    function switchAdminView(mode) {
      window.bookingDashboardState.active = mode;

      document.getElementById('btn-view-reguler').classList.toggle('active', mode === 'bookings');
      document.getElementById('btn-view-carter').classList.toggle('active', mode === 'charters');
      document.getElementById('btn-view-bagasi').classList.toggle('active', mode === 'luggage');

      const container = document.getElementById('admin-mode-toggle-container');
      if (container) {
        container.classList.remove('mode-charters', 'mode-luggage');
        if (mode === 'charters') container.classList.add('mode-charters');
        else if (mode === 'luggage') container.classList.add('mode-luggage');
      }

      document.getElementById('bookings_tbody').style.display = 'none';
      document.getElementById('bookings_pagination').style.display = 'none';
      document.getElementById('charters_tbody').style.display = 'none';
      document.getElementById('charters_pagination').style.display = 'none';
      document.getElementById('luggage_tbody').style.display = 'none';
      document.getElementById('luggage_pagination').style.display = 'none';

      updateBookingModeMeta(mode);

      if (mode === 'charters') {
        document.getElementById('charters_tbody').style.display = 'flex';
        document.getElementById('charters_pagination').style.display = 'flex';
        if (document.getElementById('charters_tbody').children.length <= 1) {
          ajaxListLoad('charters', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' });
        }
      } else if (mode === 'luggage') {
        document.getElementById('luggage_tbody').style.display = 'flex';
        document.getElementById('luggage_pagination').style.display = 'flex';
        if (document.getElementById('luggage_tbody').children.length <= 1) {
          ajaxListLoad('luggage', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' });
        }
      } else {
        document.getElementById('bookings_tbody').style.display = 'flex';
        document.getElementById('bookings_pagination').style.display = 'flex';
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const refreshBtn = document.getElementById('bookingRefreshBtn');
      const mobileRefreshBtn = document.getElementById('bookingMobileRefresh');
      const mobileSearchBtn = document.getElementById('bookingMobileFocusSearch');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshActiveBookingMode);
      }
      if (mobileRefreshBtn) {
        mobileRefreshBtn.addEventListener('click', refreshActiveBookingMode);
      }
      if (mobileSearchBtn) {
        mobileSearchBtn.addEventListener('click', () => {
          document.getElementById('search_name_input')?.focus();
        });
      }
      switchAdminView('bookings');
    });
  </script>
</section>
