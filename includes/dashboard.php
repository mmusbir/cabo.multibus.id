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
      <button type="button" class="kinetic-dash-export" onclick="if(typeof window.showSectionById==='function'){window.showSectionById('reports');window.location.hash='#reports';}">
        <i class="fa-solid fa-file-arrow-down fa-icon"></i>
        Export Laporan
      </button>
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
  var dashLoaded = false;

  function formatNumber(n) {
    return new Intl.NumberFormat('id-ID').format(n);
  }

  function populateDashboard(data) {
    if (dashLoaded) return;
    dashLoaded = true;

    // Stats
    var el;
    el = document.getElementById('dashTotalBookings'); if (el) el.textContent = formatNumber(data.total_bookings || 0);
    el = document.getElementById('dashPending'); if (el) el.textContent = formatNumber(data.pending || 0);
    el = document.getElementById('dashConfirmed'); if (el) el.textContent = formatNumber(data.confirmed || 0);
    el = document.getElementById('dashCanceled'); if (el) el.textContent = formatNumber(data.canceled || 0);

    // Date label
    if (data.today_label) {
      el = document.getElementById('dashDateLabel'); if (el) el.textContent = data.today_label;
    }

    // Revenue splits
    el = document.getElementById('dashRevenueBooking'); if (el) el.textContent = 'Rp ' + formatNumber(data.revenue_booking_month || 0);
    el = document.getElementById('dashRevenueCharter'); if (el) el.textContent = 'Rp ' + formatNumber(data.revenue_charter_month || 0);
    el = document.getElementById('dashRevenueLuggage'); if (el) el.textContent = 'Rp ' + formatNumber(data.revenue_luggage_month || 0);

    // Insights
    el = document.getElementById('dashTopRoute'); if (el) el.textContent = data.top_route || '-';
    el = document.getElementById('dashTopRouteCount'); if (el) el.textContent = formatNumber(data.top_route_count || 0) + ' BOOKING';
    el = document.getElementById('dashLiveFleet'); if (el) el.textContent = formatNumber(data.live_fleet || 0) + ' Unit Beroperasi';
    el = document.getElementById('dashRevenueToday'); if (el) el.textContent = 'Rp ' + formatNumber(data.revenue_today || 0);

    // Chart bars
    var chartContainer = document.getElementById('dashChartBars');
    if (chartContainer && data.trend_labels && data.trend_labels.length) {
      var maxRevenue = Math.max.apply(null, data.trend_revenues.length ? data.trend_revenues : [0]);
      var html = '';
      for (var i = 0; i < data.trend_labels.length; i++) {
        var rev = data.trend_revenues[i] || 0;
        var height = (maxRevenue <= 0 || rev <= 0) ? '0.35rem' : (Math.round((rev / maxRevenue) * 10000) / 100) + '%';
        var isActive = (i === data.trend_labels.length - 1) ? ' is-active' : '';
        var isZero = rev <= 0 ? ' is-zero' : '';
        html += '<div class="kinetic-dash-bar-group' + isActive + '" data-revenue="' + Math.round(rev) + '" data-date="' + (data.trend_dates[i] || '') + '">';
        html += '<div class="kinetic-dash-bar-tooltip">';
        html += '<span class="tooltip-date">' + (data.trend_dates[i] || '') + '</span>';
        html += '<span class="tooltip-amount">Rp ' + formatNumber(rev) + '</span>';
        html += '</div>';
        html += '<div class="kinetic-dash-bar-shell">';
        html += '<div class="kinetic-dash-bar' + isZero + '" style="height: ' + height + ';"></div>';
        html += '</div>';
        html += '<span>' + (data.trend_labels[i] || '') + '</span>';
        html += '</div>';
      }
      chartContainer.innerHTML = html;
    }

    // Activity list
    var activityList = document.getElementById('dashActivityList');
    if (activityList) {
      if (!data.recent_activity || data.recent_activity.length === 0) {
        activityList.innerHTML = '<div class="admin-empty-state view-empty-state">Belum ada aktivitas terbaru.</div>';
      } else {
        var actHtml = '';
        for (var j = 0; j < data.recent_activity.length; j++) {
          var a = data.recent_activity[j];
          actHtml += '<div class="kinetic-activity-item tone-' + (a.tone || 'info') + '">';
          actHtml += '<div class="kinetic-activity-dot"></div>';
          actHtml += '<div>';
          actHtml += '<p>' + escapeHtml(a.title || '-') + '</p>';
          actHtml += '<div class="kinetic-activity-meta">';
          actHtml += '<span>' + escapeHtml(a.time || '') + '</span>';
          actHtml += '<span class="kinetic-meta-divider"></span>';
          actHtml += '<span>' + escapeHtml(a.meta || '') + '</span>';
          actHtml += '<span class="kinetic-meta-divider"></span>';
          actHtml += '<span>' + escapeHtml(a.tag || '') + '</span>';
          actHtml += '</div></div></div>';
        }
        activityList.innerHTML = actHtml;
      }
    }
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  window.loadDashboardData = function() {
    if (dashLoaded) return;
    fetch('admin.php?action=dashboardData', { credentials: 'same-origin' })
      .then(function(res) { return res.json(); })
      .then(function(json) {
        if (json.success && json.data) {
          populateDashboard(json.data);
        }
      })
      .catch(function(err) {
        console.error('Dashboard load error:', err);
      });
  };

  // Auto-load when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.loadDashboardData);
  } else {
    window.loadDashboardData();
  }
})();
</script>
