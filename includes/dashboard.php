<?php
$dashboard = [
    'total_bookings' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'canceled' => 0,
    'top_route' => '-',
    'top_route_count' => 0,
    'live_fleet' => 0,
    'revenue_today' => 0,
    'revenue_booking_month' => 0,
    'revenue_charter_month' => 0,
    'revenue_luggage_month' => 0,
    'trend_labels' => [],
    'trend_revenues' => [],
    'trend_dates' => [],
    'recent_activity' => [],
];

try {
    $dashboard['total_bookings'] = (int) ($conn->query("SELECT COUNT(*) FROM bookings WHERE status != 'canceled' AND tanggal >= CURRENT_DATE")->fetchColumn() ?? 0);
    $dashboard['pending'] = (int) ($conn->query("SELECT COUNT(*) FROM bookings WHERE status != 'canceled' AND tanggal >= CURRENT_DATE AND (pembayaran IS NULL OR pembayaran = 'Belum Lunas')")->fetchColumn() ?? 0);
    $dashboard['confirmed'] = (int) ($conn->query("SELECT COUNT(*) FROM bookings WHERE status != 'canceled' AND tanggal >= CURRENT_DATE AND pembayaran IN ('Lunas', 'Redbus', 'Traveloka')")->fetchColumn() ?? 0);
    $dashboard['canceled'] = (int) ($conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'canceled' AND tanggal >= CURRENT_DATE")->fetchColumn() ?? 0);

    $topRouteStmt = $conn->query("SELECT rute, COUNT(*) AS total FROM bookings WHERE status != 'canceled' AND tanggal >= CURRENT_DATE GROUP BY rute ORDER BY total DESC LIMIT 1");
    if ($topRouteStmt && ($topRoute = $topRouteStmt->fetch(PDO::FETCH_ASSOC))) {
        $dashboard['top_route'] = $topRoute['rute'] ?: '-';
        $dashboard['top_route_count'] = (int) ($topRoute['total'] ?? 0);
    }

    $dashboard['live_fleet'] = (int) ($conn->query("SELECT COUNT(DISTINCT (tanggal::text || '|' || jam::text || '|' || unit::text)) FROM bookings WHERE status != 'canceled' AND tanggal = CURRENT_DATE")->fetchColumn() ?? 0);
    $dashboard['revenue_today'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0)), 0) FROM bookings WHERE status != 'canceled' AND pembayaran IN ('Lunas', 'Redbus', 'Traveloka') AND tanggal = CURRENT_DATE")->fetchColumn() ?? 0);
    $dashboard['revenue_booking_month'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0)), 0) FROM bookings WHERE status != 'canceled' AND pembayaran IN ('Lunas', 'Redbus', 'Traveloka') AND DATE_TRUNC('month', tanggal) = DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn() ?? 0);
    $dashboard['revenue_charter_month'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0)), 0) FROM charters WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn() ?? 0);
    $dashboard['revenue_luggage_month'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0)), 0) FROM luggages WHERE payment_status = 'Lunas' AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn() ?? 0);

    // Revenue trend per day (last 7 days), only counting paid bookings
    $revTrendStmt = $conn->query("SELECT tanggal, COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0)), 0) AS revenue FROM bookings WHERE status != 'canceled' AND pembayaran IN ('Lunas', 'Redbus', 'Traveloka') AND tanggal BETWEEN (CURRENT_DATE - INTERVAL '6 days') AND CURRENT_DATE GROUP BY tanggal ORDER BY tanggal ASC");
    $revTrendMap = [];
    if ($revTrendStmt) {
        while ($row = $revTrendStmt->fetch(PDO::FETCH_ASSOC)) {
            $dateKey = !empty($row['tanggal']) ? date('Y-m-d', strtotime((string) $row['tanggal'])) : '';
            if ($dateKey !== '') {
                $revTrendMap[$dateKey] = (float) ($row['revenue'] ?? 0);
            }
        }
    }

    for ($i = 6; $i >= 0; $i--) {
        $dateKey = date('Y-m-d', strtotime('-' . $i . ' days'));
        $dashboard['trend_labels'][] = strtoupper(date('D', strtotime($dateKey)));
        $dashboard['trend_revenues'][] = $revTrendMap[$dateKey] ?? 0;
        $dashboard['trend_dates'][] = date('d M', strtotime($dateKey));
    }

    activity_log_ensure_table($conn);
    $activityStmt = $conn->query("SELECT category, action, summary, details, actor, created_at FROM activity_logs ORDER BY created_at DESC, id DESC LIMIT 5");
    while ($act = $activityStmt->fetch(PDO::FETCH_ASSOC)) {
        $category = strtolower(trim((string) ($act['category'] ?? 'settings')));
        $action = strtolower(trim((string) ($act['action'] ?? 'update')));
        $dashboard['recent_activity'][] = [
            'title' => (string) ($act['summary'] ?? '-'),
            'meta' => trim((string) ($act['details'] ?? '')) ?: ('Admin: ' . trim((string) ($act['actor'] ?? 'system'))),
            'time' => activity_log_relative_time($act['created_at'] ?? ''),
            'tone' => activity_log_tone($category, $action),
            'tag' => strtoupper($category)
        ];
    }

} catch (Throwable $e) {
    // Keep dashboard resilient even if a query fails.
}

$todayLabel = strtoupper(date('l, d F Y'));
$maxRevenue = max($dashboard['trend_revenues'] ?: [0]);
$trendHeights = array_map(static function ($rev) use ($maxRevenue) {
    if ($maxRevenue <= 0 || $rev <= 0) {
        return 0;
    }
    return round(($rev / $maxRevenue) * 100, 2);
}, $dashboard['trend_revenues']);
?>
<section id="dashboard" class="card kinetic-admin-dashboard">
  <div class="kinetic-dash-shell">
    <section class="kinetic-dash-head">
      <div>
        <p class="kinetic-dash-kicker">Admin Panel</p>
        <h1 class="kinetic-dash-title">Dashboard</h1>
        <p class="kinetic-dash-date"><?php echo htmlspecialchars($todayLabel); ?></p>
      </div>
      <button type="button" class="kinetic-dash-export" onclick="if(typeof window.showSectionById==='function'){window.showSectionById('reports');window.location.hash='#reports';}">
        <span class="material-symbols-outlined">download</span>
        Export Laporan
      </button>
    </section>

    <section class="kinetic-dash-stats">
      <div class="kinetic-stat-card is-primary">
        <p>Total Booking</p>
        <div><strong><?php echo number_format($dashboard['total_bookings'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">trending_up</span></div>
      </div>
      <div class="kinetic-stat-card is-warning">
        <p>Belum Lunas</p>
        <div><strong><?php echo number_format($dashboard['pending'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">schedule</span></div>
      </div>
      <div class="kinetic-stat-card is-success">
        <p>Lunas Semua</p>
        <div><strong><?php echo number_format($dashboard['confirmed'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">check_circle</span></div>
      </div>
      <div class="kinetic-stat-card is-danger">
        <p>Dibatalkan</p>
        <div><strong><?php echo number_format($dashboard['canceled'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">cancel</span></div>
      </div>
    </section>

    <div class="kinetic-dash-grid">
      <div class="kinetic-dash-chart-stack">
        <section class="kinetic-dash-panel kinetic-dash-chart">
          <div class="kinetic-dash-panel-head">
            <h2>Tren Revenue Harian</h2>
            <span class="kinetic-dash-chip">7 Hari Terakhir</span>
          </div>
          <div class="kinetic-dash-chart-bars">
            <?php foreach ($dashboard['trend_labels'] as $idx => $label): ?>
              <div class="kinetic-dash-bar-group <?php echo $idx === array_key_last($dashboard['trend_labels']) ? 'is-active' : ''; ?>"
                   data-revenue="<?php echo (int) $dashboard['trend_revenues'][$idx]; ?>"
                   data-date="<?php echo htmlspecialchars($dashboard['trend_dates'][$idx]); ?>">
                <div class="kinetic-dash-bar-tooltip">
                  <span class="tooltip-date"><?php echo htmlspecialchars($dashboard['trend_dates'][$idx]); ?></span>
                  <span class="tooltip-amount">Rp <?php echo number_format($dashboard['trend_revenues'][$idx], 0, ',', '.'); ?></span>
                </div>
                <div class="kinetic-dash-bar-shell">
                  <div class="kinetic-dash-bar <?php echo ($dashboard['trend_revenues'][$idx] ?? 0) <= 0 ? 'is-zero' : ''; ?>" style="height: <?php echo ($dashboard['trend_revenues'][$idx] ?? 0) <= 0 ? '0.35rem' : htmlspecialchars((string) $trendHeights[$idx]) . '%'; ?>;"></div>
                </div>
                <span><?php echo htmlspecialchars($label); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="kinetic-dash-revenue-splits">
          <article class="kinetic-revenue-split-card is-booking">
            <h3>Revenue Booking Penumpang</h3>
            <strong>Rp <?php echo number_format($dashboard['revenue_booking_month'], 0, ',', '.'); ?></strong>
            <span>Bulan ini · booking lunas</span>
          </article>
          <article class="kinetic-revenue-split-card is-charter">
            <h3>Revenue Carter</h3>
            <strong>Rp <?php echo number_format($dashboard['revenue_charter_month'], 0, ',', '.'); ?></strong>
            <span>Bulan ini · seluruh carter</span>
          </article>
          <article class="kinetic-revenue-split-card is-luggage">
            <h3>Revenue Bagasi</h3>
            <strong>Rp <?php echo number_format($dashboard['revenue_luggage_month'], 0, ',', '.'); ?></strong>
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
        <div class="kinetic-dash-activity-list">
          <?php if (empty($dashboard['recent_activity'])): ?>
            <div class="admin-empty-state view-empty-state">Belum ada aktivitas terbaru.</div>
          <?php else: ?>
            <?php foreach ($dashboard['recent_activity'] as $activity): ?>
              <div class="kinetic-activity-item tone-<?php echo htmlspecialchars($activity['tone']); ?>">
                <div class="kinetic-activity-dot"></div>
                <div>
                  <p><?php echo htmlspecialchars($activity['title']); ?></p>
                  <div class="kinetic-activity-meta">
                    <span><?php echo htmlspecialchars($activity['time']); ?></span>
                    <span class="kinetic-meta-divider"></span>
                    <span><?php echo htmlspecialchars($activity['meta']); ?></span>
                    <span class="kinetic-meta-divider"></span>
                    <span><?php echo htmlspecialchars($activity['tag']); ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <section class="kinetic-dash-insights">
      <div class="kinetic-insight-card">
        <h3>Rute Teratas</h3>
        <div class="kinetic-insight-body">
          <div>
            <p><?php echo htmlspecialchars($dashboard['top_route']); ?></p>
            <span><?php echo number_format($dashboard['top_route_count'], 0, ',', '.'); ?> BOOKING</span>
          </div>
          <span class="material-symbols-outlined">route</span>
        </div>
      </div>
      <div class="kinetic-insight-card">
        <h3>Armada Aktif</h3>
        <div class="kinetic-insight-body">
          <div>
            <p><?php echo number_format($dashboard['live_fleet'], 0, ',', '.'); ?> Unit Beroperasi</p>
            <span>0 INSIDEN DILAPORKAN</span>
          </div>
          <span class="material-symbols-outlined">minor_crash</span>
        </div>
      </div>
      <div class="kinetic-insight-card is-revenue">
        <h3>Pendapatan Hari Ini</h3>
        <div class="kinetic-insight-body">
          <div>
            <p>Rp <?php echo number_format($dashboard['revenue_today'], 0, ',', '.'); ?></p>
            <span>AKUMULASI HARI INI</span>
          </div>
          <span class="material-symbols-outlined">payments</span>
        </div>
      </div>
    </section>
  </div>
</section>
