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
    'trend_labels' => [],
    'trend_counts' => [],
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

    $dashboard['live_fleet'] = (int) ($conn->query("SELECT COUNT(DISTINCT CONCAT(tanggal, '|', jam, '|', unit)) FROM bookings WHERE status != 'canceled' AND tanggal = CURRENT_DATE")->fetchColumn() ?? 0);
    $dashboard['revenue_today'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0)), 0) FROM bookings WHERE status != 'canceled' AND tanggal = CURRENT_DATE")->fetchColumn() ?? 0);

    $trendStmt = $conn->query("SELECT tanggal, COUNT(*) AS total FROM bookings WHERE status != 'canceled' AND tanggal BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) AND CURRENT_DATE GROUP BY tanggal ORDER BY tanggal ASC");
    $trendMap = [];
    if ($trendStmt) {
        while ($row = $trendStmt->fetch(PDO::FETCH_ASSOC)) {
            $trendMap[$row['tanggal']] = (int) ($row['total'] ?? 0);
        }
    }

    for ($i = 6; $i >= 0; $i--) {
        $dateKey = date('Y-m-d', strtotime('-' . $i . ' days'));
        $dashboard['trend_labels'][] = strtoupper(date('D', strtotime($dateKey)));
        $dashboard['trend_counts'][] = $trendMap[$dateKey] ?? 0;
    }

    $recentStmt = $conn->query("SELECT name, rute, pembayaran, created_at FROM bookings ORDER BY created_at DESC LIMIT 3");
    if ($recentStmt) {
        while ($row = $recentStmt->fetch(PDO::FETCH_ASSOC)) {
            $payment = $row['pembayaran'] ?? 'Belum Lunas';
            $tone = 'primary';
            $icon = 'confirmation_number';
            if ($payment === 'Lunas') {
                $tone = 'success';
                $icon = 'check_circle';
            } elseif ($payment === 'Belum Lunas') {
                $tone = 'warning';
                $icon = 'schedule';
            }

            $dashboard['recent_activity'][] = [
                'title' => 'Booking baru dari ' . ($row['name'] ?: 'Customer'),
                'meta' => $row['rute'] ?: '-',
                'time' => !empty($row['created_at']) ? date('H:i', strtotime($row['created_at'])) . ' WIB' : '-',
                'tone' => $tone,
                'icon' => $icon,
                'tag' => strtoupper($payment),
            ];
        }
    }
} catch (Throwable $e) {
    // Keep dashboard resilient even if a query fails.
}

$todayLabel = strtoupper(date('l, d F Y'));
$maxTrend = max($dashboard['trend_counts'] ?: [1]);
$trendHeights = array_map(static function ($count) use ($maxTrend) {
    if ($maxTrend <= 0) {
        return 18;
    }
    return max(18, (int) round(($count / $maxTrend) * 100));
}, $dashboard['trend_counts']);
?>
<section id="dashboard" class="card kinetic-admin-dashboard">
  <div class="kinetic-dash-shell">
    <section class="kinetic-dash-head">
      <div>
        <p class="kinetic-dash-kicker">Operational Overview</p>
        <h1 class="kinetic-dash-title">Dashboard</h1>
        <p class="kinetic-dash-date"><?php echo htmlspecialchars($todayLabel); ?></p>
      </div>
      <button type="button" class="kinetic-dash-export" onclick="if(typeof window.showSectionById==='function'){window.showSectionById('reports');window.location.hash='#reports';}">
        <span class="material-symbols-outlined">download</span>
        Export Report
      </button>
    </section>

    <section class="kinetic-dash-stats">
      <div class="kinetic-stat-card is-primary">
        <p>Total Booking</p>
        <div><strong><?php echo number_format($dashboard['total_bookings'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">trending_up</span></div>
      </div>
      <div class="kinetic-stat-card is-warning">
        <p>Pending</p>
        <div><strong><?php echo number_format($dashboard['pending'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">schedule</span></div>
      </div>
      <div class="kinetic-stat-card is-success">
        <p>Confirmed</p>
        <div><strong><?php echo number_format($dashboard['confirmed'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">check_circle</span></div>
      </div>
      <div class="kinetic-stat-card is-danger">
        <p>Dibatalkan</p>
        <div><strong><?php echo number_format($dashboard['canceled'], 0, ',', '.'); ?></strong><span class="material-symbols-outlined">cancel</span></div>
      </div>
    </section>

    <div class="kinetic-dash-grid">
      <section class="kinetic-dash-panel kinetic-dash-chart">
        <div class="kinetic-dash-panel-head">
          <h2>Booking Trends</h2>
          <span class="kinetic-dash-chip">Last 7 Days</span>
        </div>
        <div class="kinetic-dash-chart-bars">
          <?php foreach ($dashboard['trend_labels'] as $idx => $label): ?>
            <div class="kinetic-dash-bar-group <?php echo $idx === array_key_last($dashboard['trend_labels']) ? 'is-active' : ''; ?>">
              <div class="kinetic-dash-bar" style="height: <?php echo (int) $trendHeights[$idx]; ?>%;"></div>
              <span><?php echo htmlspecialchars($label); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="kinetic-dash-panel kinetic-dash-activity">
        <div class="kinetic-dash-panel-head">
          <h2>Recent Activity</h2>
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
        <h3>Top Route</h3>
        <div class="kinetic-insight-body">
          <div>
            <p><?php echo htmlspecialchars($dashboard['top_route']); ?></p>
            <span><?php echo number_format($dashboard['top_route_count'], 0, ',', '.'); ?> BOOKINGS</span>
          </div>
          <span class="material-symbols-outlined">route</span>
        </div>
      </div>
      <div class="kinetic-insight-card">
        <h3>Live Fleet</h3>
        <div class="kinetic-insight-body">
          <div>
            <p><?php echo number_format($dashboard['live_fleet'], 0, ',', '.'); ?> Units On-Road</p>
            <span>0 INCIDENTS REPORTED</span>
          </div>
          <span class="material-symbols-outlined">minor_crash</span>
        </div>
      </div>
      <div class="kinetic-insight-card is-revenue">
        <h3>Revenue Today</h3>
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
