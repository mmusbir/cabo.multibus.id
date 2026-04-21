<?php
// Dashboard — async loaded via AJAX for faster initial page render.
// Data is fetched from admin.php?action=dashboardData after the HTML shell is visible.
$todayLabel = strtoupper(date('l, d F Y'));
?>
<section id="dashboard" class="card kinetic-admin-dashboard">
  <div class="kinetic-dash-shell">
    <section class="kinetic-dash-head">
      <div>
        <p class="kinetic-dash-kicker">Admin Panel</p>
        <h1 class="kinetic-dash-title">Dashboard</h1>
        <p class="kinetic-dash-date" id="dashDateLabel"><?php echo htmlspecialchars($todayLabel); ?></p>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <div id="dashLiveIndicator" title="Status koneksi realtime" style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--neu-muted,#94a3b8);opacity:0.6;transition:opacity .3s;">
          <span id="dashLiveDot" style="width:8px;height:8px;border-radius:50%;background:#94a3b8;display:inline-block;transition:background .4s;"></span>
          <span id="dashLiveText">Memuat...</span>
        </div>
        <button type="button" class="kinetic-dash-export" onclick="if(typeof window.showSectionById==='function'){window.showSectionById('reports');window.location.hash='#reports';}">
          <i class="fa-solid fa-file-arrow-down fa-icon"></i>
          Export Laporan
        </button>
      </div>
    </section>

    <section class="kinetic-dash-stats">
      <div class="kinetic-stat-card is-primary">
        <p>Total Booking</p>
        <div><strong id="dashTotalBookings">—</strong><i class="fa-solid fa-arrow-trend-up fa-icon"></i></div>
      </div>
      <div class="kinetic-stat-card is-warning">
        <p>Belum Lunas</p>
        <div><strong id="dashPending">—</strong><i class="fa-solid fa-clock fa-icon"></i></div>
      </div>
      <div class="kinetic-stat-card is-success">
        <p>Lunas Semua</p>
        <div><strong id="dashConfirmed">—</strong><i class="fa-solid fa-circle-check fa-icon"></i></div>
      </div>
      <div class="kinetic-stat-card is-danger">
        <p>Dibatalkan</p>
        <div><strong id="dashCanceled">—</strong><i class="fa-solid fa-circle-xmark fa-icon"></i></div>
      </div>
    </section>

    <div class="kinetic-dash-grid">
      <div class="kinetic-dash-chart-stack">
        <section class="kinetic-dash-panel kinetic-dash-chart">
          <div class="kinetic-dash-panel-head">
            <h2>Tren Revenue Harian</h2>
            <span class="kinetic-dash-chip">7 Hari Terakhir</span>
          </div>
          <div class="kinetic-dash-chart-bars" id="dashChartBars">
            <!-- Skeleton bars while loading -->
            <?php for ($i = 0; $i < 7; $i++): ?>
              <div class="kinetic-dash-bar-group">
                <div class="kinetic-dash-bar-shell">
                  <div class="kinetic-dash-bar is-zero" style="height: 0.35rem;"></div>
                </div>
                <span>—</span>
              </div>
            <?php endfor; ?>
          </div>
        </section>

        <section class="kinetic-dash-panel kinetic-dash-chart is-monthly">
          <div class="kinetic-dash-panel-head">
            <h2>Tren Revenue Bulanan</h2>
            <span class="kinetic-dash-chip">Tahun <?php echo date('Y'); ?></span>
          </div>
          <div class="kinetic-dash-chart-bars" id="dashChartBarsMonthly">
            <!-- Skeleton bars while loading -->
            <?php for ($i = 0; $i < 12; $i++): ?>
              <div class="kinetic-dash-bar-group">
                <div class="kinetic-dash-bar-shell">
                  <div class="kinetic-dash-bar is-zero" style="height: 0.35rem;"></div>
                </div>
                <span>—</span>
              </div>
            <?php endfor; ?>
          </div>
        </section>

        <section class="kinetic-dash-revenue-splits" id="dashRevenueSplits">
          <article class="kinetic-revenue-split-card is-booking">
            <div class="kinetic-revenue-split-head">
              <span class="kinetic-revenue-split-icon"><i class="fa-solid fa-receipt fa-icon"></i></span>
              <h3>Booking</h3>
            </div>
            <strong id="dashRevenueBooking">Rp —</strong>
            <span>Bulan ini · booking lunas</span>
          </article>
          <article class="kinetic-revenue-split-card is-charter">
            <div class="kinetic-revenue-split-head">
              <span class="kinetic-revenue-split-icon"><i class="fa-solid fa-bus fa-icon"></i></span>
              <h3>Carter</h3>
            </div>
            <strong id="dashRevenueCharter">Rp —</strong>
            <span>Bulan ini · seluruh carter</span>
          </article>
          <article class="kinetic-revenue-split-card is-luggage">
            <div class="kinetic-revenue-split-head">
              <span class="kinetic-revenue-split-icon"><i class="fa-solid fa-suitcase-rolling fa-icon"></i></span>
              <h3>Bagasi</h3>
            </div>
            <strong id="dashRevenueLuggage">Rp —</strong>
            <span>Bulan ini · bagasi lunas</span>
          </article>
        </section>
      </div>

      <section class="kinetic-dash-panel kinetic-dash-activity">
        <div class="kinetic-dash-panel-head">
          <h2>Aktivitas Terbaru</h2>
          <button type="button" class="kinetic-dash-panel-link" onclick="if(typeof window.showSectionById==='function'){window.showSectionById('cancellations');window.location.hash='#cancellations';}">
            Lihat Semua Activity
          </button>
        </div>
        <div class="kinetic-dash-activity-list" id="dashActivityList">
          <div class="admin-empty-state view-empty-state">Memuat aktivitas...</div>
        </div>
      </section>
    </div>

    <section class="kinetic-dash-insights">
      <div class="kinetic-insight-card">
        <h3>Rute Teratas</h3>
        <div class="kinetic-insight-body">
          <div>
            <p id="dashTopRoute">—</p>
            <span id="dashTopRouteCount">— BOOKING</span>
          </div>
          <i class="fa-solid fa-route fa-icon"></i>
        </div>
      </div>
      <div class="kinetic-insight-card">
        <h3>Armada Aktif</h3>
        <div class="kinetic-insight-body">
          <div>
            <p id="dashLiveFleet">— Unit Beroperasi</p>
            <span>0 INSIDEN DILAPORKAN</span>
          </div>
          <i class="fa-solid fa-bus-simple fa-icon"></i>
        </div>
      </div>
      <div class="kinetic-insight-card is-revenue">
        <h3>Pendapatan Hari Ini</h3>
        <div class="kinetic-insight-body">
          <div>
            <p id="dashRevenueToday">Rp —</p>
            <span>AKUMULASI HARI INI</span>
          </div>
          <i class="fa-solid fa-wallet fa-icon"></i>
        </div>
      </div>
    </section>
  </div>
</section>

<script>
(function() {
  // =============================================
  // DASHBOARD REALTIME POLLING ENGINE
  // Polls admin.php?action=dashboardData every 30s
  // Pauses when tab is hidden (Page Visibility API)
  // Animates number changes with smooth transition
  // =============================================

  var POLL_INTERVAL_MS  = 30000; // 30 detik
  var RETRY_DELAY_MS    = 10000; // Retry 10 detik jika gagal
  var _pollTimer        = null;
  var _lastData         = null;
  var _initialLoaded    = false;
  var _fetching         = false;

  // ---- Helpers ----

  function formatRupiah(n) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
  }

  function formatNumber(n) {
    return new Intl.NumberFormat('id-ID').format(n || 0);
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Animate number from current displayed value → new target value
  function animateValue(el, newVal, isRupiah) {
    if (!el) return;
    var currentText = el.dataset.rawValue !== undefined ? parseFloat(el.dataset.rawValue) : 0;
    var target = parseFloat(newVal) || 0;
    if (currentText === target) return; // no change, skip

    el.dataset.rawValue = target;

    // Highlight flash on change
    el.style.transition = 'color 0.3s';
    el.style.color = target > currentText ? 'var(--bs-success, #22c55e)' : 'var(--bs-danger, #ef4444)';
    setTimeout(function() { el.style.color = ''; }, 900);

    // Smooth count animation (60fps, ~600ms)
    var duration = 600;
    var start    = performance.now();
    var from     = currentText;
    function step(now) {
      var progress = Math.min((now - start) / duration, 1);
      var ease     = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      var current  = from + (target - from) * ease;
      el.textContent = isRupiah ? formatRupiah(current) : formatNumber(Math.round(current));
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  // Set text only if changed
  function setText(id, text) {
    var el = document.getElementById(id);
    if (el && el.textContent !== text) el.textContent = text;
  }

  // ---- Live Indicator ----

  function setIndicator(state) {
    // state: 'loading' | 'live' | 'error' | 'paused'
    var dot   = document.getElementById('dashLiveDot');
    var label = document.getElementById('dashLiveText');
    var wrap  = document.getElementById('dashLiveIndicator');
    if (!dot || !label || !wrap) return;

    var configs = {
      loading: { color: '#f59e0b', text: 'Memuat...', opacity: '0.7', pulse: true  },
      live:    { color: '#22c55e', text: 'Live',      opacity: '1',   pulse: true  },
      error:   { color: '#ef4444', text: 'Gagal',     opacity: '0.8', pulse: false },
      paused:  { color: '#94a3b8', text: 'Jeda',      opacity: '0.5', pulse: false }
    };
    var cfg = configs[state] || configs['paused'];
    dot.style.background = cfg.color;
    label.textContent    = cfg.text;
    wrap.style.opacity   = cfg.opacity;

    // Pulse animation via keyframe trick
    dot.style.animation = cfg.pulse ? 'dashDotPulse 2s ease-in-out infinite' : 'none';
  }

  // Inject pulse keyframe once
  (function injectPulse() {
    if (document.getElementById('dashPulseStyle')) return;
    var s = document.createElement('style');
    s.id  = 'dashPulseStyle';
    s.textContent = '@keyframes dashDotPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.4)}}';
    document.head.appendChild(s);
  })();

  // ---- Chart renderers ----

  function renderDailyChart(data) {
    var container = document.getElementById('dashChartBars');
    if (!container || !data.trend_labels || !data.trend_labels.length) return;
    var maxR = Math.max.apply(null, data.trend_revenues.length ? data.trend_revenues : [0]);
    var html = '';
    for (var i = 0; i < data.trend_labels.length; i++) {
      var rev      = data.trend_revenues[i] || 0;
      var h        = (maxR <= 0 || rev <= 0) ? '0.35rem' : (Math.round((rev / maxR) * 10000) / 100) + '%';
      var isActive = (i === data.trend_labels.length - 1) ? ' is-active' : '';
      var isZero   = rev <= 0 ? ' is-zero' : '';
      html += '<div class="kinetic-dash-bar-group' + isActive + '" data-revenue="' + Math.round(rev) + '" data-date="' + escapeHtml(data.trend_dates[i] || '') + '">';
      html += '<div class="kinetic-dash-bar-tooltip">';
      html += '<span class="tooltip-date">' + escapeHtml(data.trend_dates[i] || '') + '</span>';
      html += '<span class="tooltip-amount">' + formatRupiah(rev) + '</span>';
      html += '</div>';
      html += '<div class="kinetic-dash-bar-shell"><div class="kinetic-dash-bar' + isZero + '" style="height:' + h + ';"></div></div>';
      html += '<span>' + escapeHtml(data.trend_labels[i] || '') + '</span>';
      html += '</div>';
    }
    container.innerHTML = html;
  }

  function renderMonthlyChart(data) {
    var container = document.getElementById('dashChartBarsMonthly');
    if (!container || !data.monthly_labels || !data.monthly_labels.length) return;
    var maxR = Math.max.apply(null, data.monthly_revenues.length ? data.monthly_revenues : [0]);
    var html = '';
    for (var k = 0; k < data.monthly_labels.length; k++) {
      var rev      = data.monthly_revenues[k] || 0;
      var h        = (maxR <= 0 || rev <= 0) ? '0.35rem' : (Math.round((rev / maxR) * 10000) / 100) + '%';
      var isActive = (k === data.monthly_labels.length - 1) ? ' is-active' : '';
      var isZero   = rev <= 0 ? ' is-zero' : '';
      html += '<div class="kinetic-dash-bar-group' + isActive + '" data-revenue="' + Math.round(rev) + '" data-date="' + escapeHtml(data.monthly_names[k] || '') + '">';
      html += '<div class="kinetic-dash-bar-tooltip">';
      html += '<span class="tooltip-date">' + escapeHtml(data.monthly_names[k] || '') + '</span>';
      html += '<span class="tooltip-amount">' + formatRupiah(rev) + '</span>';
      html += '</div>';
      html += '<div class="kinetic-dash-bar-shell"><div class="kinetic-dash-bar' + isZero + '" style="height:' + h + ';"></div></div>';
      html += '<span>' + escapeHtml(data.monthly_labels[k] || '') + '</span>';
      html += '</div>';
    }
    container.innerHTML = html;
  }

  function renderActivity(data) {
    var list = document.getElementById('dashActivityList');
    if (!list) return;
    if (!data.recent_activity || data.recent_activity.length === 0) {
      list.innerHTML = '<div class="admin-empty-state view-empty-state">Belum ada aktivitas terbaru.</div>';
      return;
    }
    // Only re-render if activity changed (compare first item title+time)
    var firstNew = data.recent_activity[0];
    var firstCur = list.querySelector('.kinetic-activity-item p');
    if (firstCur && firstCur.textContent === (firstNew.title || '')) return;

    var html = '';
    for (var j = 0; j < data.recent_activity.length; j++) {
      var a = data.recent_activity[j];
      html += '<div class="kinetic-activity-item tone-' + escapeHtml(a.tone || 'info') + '">';
      html += '<div class="kinetic-activity-dot"></div>';
      html += '<div><p>' + escapeHtml(a.title || '-') + '</p>';
      html += '<div class="kinetic-activity-meta">';
      html += '<span>' + escapeHtml(a.time || '') + '</span>';
      html += '<span class="kinetic-meta-divider"></span>';
      html += '<span>' + escapeHtml(a.meta || '') + '</span>';
      html += '<span class="kinetic-meta-divider"></span>';
      html += '<span>' + escapeHtml(a.tag || '') + '</span>';
      html += '</div></div></div>';
    }
    list.innerHTML = html;
  }

  // ---- Core Populate (smart diff-update) ----

  function populateDashboard(data, isRefresh) {
    var prev = _lastData;
    _lastData = data;

    // Date label (static, set once)
    if (!isRefresh && data.today_label) setText('dashDateLabel', data.today_label);

    // Stat cards — animate only if values changed on refresh
    var numFields = [
      { id: 'dashTotalBookings', key: 'total_bookings', rupiah: false },
      { id: 'dashPending',       key: 'pending',        rupiah: false },
      { id: 'dashConfirmed',     key: 'confirmed',      rupiah: false },
      { id: 'dashCanceled',      key: 'canceled',       rupiah: false },
    ];
    numFields.forEach(function(f) {
      var el = document.getElementById(f.id);
      if (!el) return;
      var newVal = data[f.key] || 0;
      if (!isRefresh || !prev || prev[f.key] !== newVal) {
        animateValue(el, newVal, f.rupiah);
      }
    });

    // Revenue splits
    var revFields = [
      { id: 'dashRevenueBooking', key: 'revenue_booking_month' },
      { id: 'dashRevenueCharter', key: 'revenue_charter_month' },
      { id: 'dashRevenueLuggage', key: 'revenue_luggage_month' },
      { id: 'dashRevenueToday',   key: 'revenue_today'         },
    ];
    revFields.forEach(function(f) {
      var el = document.getElementById(f.id);
      if (!el) return;
      var newVal = data[f.key] || 0;
      if (!isRefresh || !prev || prev[f.key] !== newVal) {
        animateValue(el, newVal, true);
      }
    });

    // Insights
    setText('dashTopRoute', data.top_route || '-');
    setText('dashTopRouteCount', formatNumber(data.top_route_count || 0) + ' BOOKING');
    setText('dashLiveFleet', formatNumber(data.live_fleet || 0) + ' Unit Beroperasi');

    // Charts — only re-render on first load or if revenue changed
    var revenueChanged = !prev ||
      JSON.stringify(prev.trend_revenues)   !== JSON.stringify(data.trend_revenues) ||
      JSON.stringify(prev.monthly_revenues) !== JSON.stringify(data.monthly_revenues);

    if (!isRefresh || revenueChanged) {
      renderDailyChart(data);
      renderMonthlyChart(data);
    }

    // Activity list
    renderActivity(data);
  }

  // ---- Fetch ----

  function fetchDashboard(isRefresh) {
    if (_fetching) return;
    _fetching = true;
    setIndicator('loading');

    fetch('admin.php?action=dashboardData', { credentials: 'same-origin' })
      .then(function(res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function(json) {
        _fetching = false;
        if (json.success && json.data) {
          populateDashboard(json.data, isRefresh);
          _initialLoaded = true;
          setIndicator('live');
          scheduleNext(POLL_INTERVAL_MS);
        } else {
          throw new Error('Invalid response');
        }
      })
      .catch(function(err) {
        _fetching = false;
        console.warn('[Dashboard] Fetch error:', err);
        setIndicator('error');
        scheduleNext(RETRY_DELAY_MS); // retry sooner on error
      });
  }

  // ---- Scheduler ----

  function scheduleNext(delay) {
    clearTimeout(_pollTimer);
    if (document.hidden) {
      setIndicator('paused');
      return; // Don't schedule while tab is hidden
    }
    _pollTimer = setTimeout(function() {
      fetchDashboard(true);
    }, delay);
  }

  // ---- Page Visibility API — pause/resume ----

  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      clearTimeout(_pollTimer);
      setIndicator('paused');
    } else {
      // Tab became visible again — refresh immediately then resume schedule
      fetchDashboard(true);
    }
  });

  // ---- Public API ----

  // Called by admin.php when dashboard section becomes active
  window.loadDashboardData = function() {
    if (!_initialLoaded) {
      fetchDashboard(false);
    }
  };

  // Manual refresh (e.g. button)
  window.refreshDashboard = function() {
    clearTimeout(_pollTimer);
    fetchDashboard(true);
  };

  // ---- Bootstrap ----

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { fetchDashboard(false); });
  } else {
    fetchDashboard(false);
  }

})();
</script>

