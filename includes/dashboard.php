<?php
if (!function_exists('format_admin_relative_time_dashboard')) {
    function format_admin_relative_time_dashboard($datetime)
    {
        if (empty($datetime)) return '-';
        $timestamp = strtotime((string) $datetime);
        if (!$timestamp) return '-';

        $diff = time() - $timestamp;
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 172800) return 'Kemarin';
        if ($diff < 2592000) return floor($diff / 86400) . ' hari lalu';

        return date('d M Y - H:i', $timestamp) . ' WITA';
    }
}

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

    $activities = [];

    // 1. Recent Regular Bookings
    $stmt1 = $conn->query("SELECT name, rute, pembayaran, created_at, 'booking' as type FROM bookings ORDER BY created_at DESC LIMIT 10");
    while ($r = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $payment = $r['pembayaran'] ?? 'Belum Lunas';
        $tone = ($payment === 'Lunas') ? 'success' : 'warning';
        $activities[] = [
            'title' => 'Booking: ' . ($r['name'] ?: 'Customer'),
            'meta' => $r['rute'] ?: '-',
            'time' => $r['created_at'],
            'tone' => $tone,
            'tag' => strtoupper($payment),
            'sort' => strtotime($r['created_at'])
        ];
    }

    // 2. Recent Charters
    $stmt2 = $conn->query("SELECT name, pickup_point, drop_point, created_at, 'charter' as type FROM charters ORDER BY created_at DESC LIMIT 10");
    while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $activities[] = [
            'title' => 'Charter: ' . ($r['name'] ?: 'Customer'),
            'meta' => ($r['pickup_point'] ?: '?') . ' -> ' . ($r['drop_point'] ?: '?'),
            'time' => $r['created_at'],
            'tone' => 'primary',
            'tag' => 'CHARTER',
            'sort' => strtotime($r['created_at'])
        ];
    }

    // 3. Recent Luggages
    $stmt3 = $conn->query("SELECT sender_name, created_at, 'luggage' as type FROM luggages ORDER BY created_at DESC LIMIT 10");
    while ($r = $stmt3->fetch(PDO::FETCH_ASSOC)) {
        $activities[] = [
            'title' => 'Paket: ' . ($r['sender_name'] ?: 'Sender'),
            'meta' => 'Pengiriman Barang/Dokumen',
            'time' => $r['created_at'],
            'tone' => 'info',
            'tag' => 'BAGASI',
            'sort' => strtotime($r['created_at'])
        ];
    }

    // Sort all and limit on dashboard so the card stays concise
    usort($activities, function($a, $b) {
        return $b['sort'] - $a['sort'];
    });
    $activities = array_slice($activities, 0, 5);

    foreach ($activities as $act) {
        $dashboard['recent_activity'][] = [
            'title' => $act['title'],
            'meta' => $act['meta'],
            'time' => format_admin_relative_time_dashboard($act['time'] ?? ''),
            'tone' => $act['tone'],
            'tag' => $act['tag']
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
                <div class="kinetic-dash-bar <?php echo ($dashboard['trend_revenues'][$idx] ?? 0) <= 0 ? 'is-zero' : ''; ?>" style="height: max(<?php echo htmlspecialchars((string) $trendHeights[$idx]); ?>%, 0.35rem);"></div>
              </div>
              <span><?php echo htmlspecialchars($label); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="kinetic-dash-panel kinetic-dash-activity">
        <div class="kinetic-dash-panel-head">
          <h2>Recent Activity</h2>
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
