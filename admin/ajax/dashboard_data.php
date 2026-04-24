<?php
/**
 * admin/ajax/dashboard_data.php
 * Returns dashboard statistics as JSON for async loading.
 * Called via: admin.php?action=dashboardData
 */

require_once __DIR__ . '/../../config/activity_log.php';

global $conn;

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
    'monthly_labels' => [],
    'monthly_revenues' => [],
    'monthly_names' => [],
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
    $dashboard['revenue_charter_month'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0)), 0) FROM charters WHERE DATE_TRUNC('month', start_date) = DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn() ?? 0);
    $dashboard['revenue_luggage_month'] = (float) ($conn->query("SELECT COALESCE(SUM(COALESCE(price, 0)), 0) FROM luggages WHERE payment_status = 'Lunas' AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn() ?? 0);

    // Revenue trend per day (last 7 days), combining all 3 modules
    $revTrendMap = [];
    
    // 1. Bookings
    $stmt1 = $conn->query("SELECT tanggal AS dt, COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0)), 0) AS revenue FROM bookings WHERE status != 'canceled' AND pembayaran IN ('Lunas', 'Redbus', 'Traveloka') AND tanggal BETWEEN (CURRENT_DATE - INTERVAL '6 days') AND CURRENT_DATE GROUP BY dt");
    if ($stmt1) {
        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
            $dateKey = !empty($row['dt']) ? date('Y-m-d', strtotime((string) $row['dt'])) : '';
            if ($dateKey !== '') $revTrendMap[$dateKey] = ($revTrendMap[$dateKey] ?? 0) + (float) ($row['revenue'] ?? 0);
        }
    }

    // 2. Charters
    $stmt2 = $conn->query("SELECT start_date AS dt, COALESCE(SUM(COALESCE(price, 0)), 0) AS revenue FROM charters WHERE start_date BETWEEN (CURRENT_DATE - INTERVAL '6 days') AND CURRENT_DATE GROUP BY dt");
    if ($stmt2) {
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $dateKey = !empty($row['dt']) ? date('Y-m-d', strtotime((string) $row['dt'])) : '';
            if ($dateKey !== '') $revTrendMap[$dateKey] = ($revTrendMap[$dateKey] ?? 0) + (float) ($row['revenue'] ?? 0);
        }
    }

    // 3. Luggages
    $stmt3 = $conn->query("SELECT DATE(created_at) AS dt, COALESCE(SUM(COALESCE(price, 0)), 0) AS revenue FROM luggages WHERE payment_status = 'Lunas' AND created_at >= (CURRENT_DATE - INTERVAL '6 days') GROUP BY dt");
    if ($stmt3) {
        while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            $dateKey = !empty($row['dt']) ? date('Y-m-d', strtotime((string) $row['dt'])) : '';
            if ($dateKey !== '') $revTrendMap[$dateKey] = ($revTrendMap[$dateKey] ?? 0) + (float) ($row['revenue'] ?? 0);
        }
    }

    for ($i = 6; $i >= 0; $i--) {
        $dateKey = date('Y-m-d', strtotime('-' . $i . ' days'));
        $dashboard['trend_labels'][] = strtoupper(date('D', strtotime($dateKey)));
        $dashboard['trend_revenues'][] = $revTrendMap[$dateKey] ?? 0;
        $dashboard['trend_dates'][] = date('d M', strtotime($dateKey));
    }

    // Revenue trend per month (current year), combining all 3 modules
    $monthlyTrendMap = [];
    
    // 1. Bookings
    $monthlyBookingsStmt = $conn->query("SELECT DATE_TRUNC('month', tanggal) AS bulan, COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0)), 0) AS revenue FROM bookings WHERE status != 'canceled' AND pembayaran IN ('Lunas', 'Redbus', 'Traveloka') AND DATE_PART('year', tanggal) = DATE_PART('year', CURRENT_DATE) GROUP BY bulan");
    if ($monthlyBookingsStmt) {
        while ($row = $monthlyBookingsStmt->fetch(PDO::FETCH_ASSOC)) {
            $monthKey = !empty($row['bulan']) ? date('Y-m', strtotime((string) $row['bulan'])) : '';
            if ($monthKey !== '') $monthlyTrendMap[$monthKey] = ($monthlyTrendMap[$monthKey] ?? 0) + (float) ($row['revenue'] ?? 0);
        }
    }

    // 2. Charters
    $monthlyChartersStmt = $conn->query("SELECT DATE_TRUNC('month', start_date) AS bulan, COALESCE(SUM(COALESCE(price, 0)), 0) AS revenue FROM charters WHERE DATE_PART('year', start_date) = DATE_PART('year', CURRENT_DATE) GROUP BY bulan");
    if ($monthlyChartersStmt) {
        while ($row = $monthlyChartersStmt->fetch(PDO::FETCH_ASSOC)) {
            $monthKey = !empty($row['bulan']) ? date('Y-m', strtotime((string) $row['bulan'])) : '';
            if ($monthKey !== '') $monthlyTrendMap[$monthKey] = ($monthlyTrendMap[$monthKey] ?? 0) + (float) ($row['revenue'] ?? 0);
        }
    }

    // 3. Luggages
    $monthlyLuggagesStmt = $conn->query("SELECT DATE_TRUNC('month', created_at) AS bulan, COALESCE(SUM(COALESCE(price, 0)), 0) AS revenue FROM luggages WHERE payment_status = 'Lunas' AND DATE_PART('year', created_at) = DATE_PART('year', CURRENT_DATE) GROUP BY bulan");
    if ($monthlyLuggagesStmt) {
        while ($row = $monthlyLuggagesStmt->fetch(PDO::FETCH_ASSOC)) {
            $monthKey = !empty($row['bulan']) ? date('Y-m', strtotime((string) $row['bulan'])) : '';
            if ($monthKey !== '') $monthlyTrendMap[$monthKey] = ($monthlyTrendMap[$monthKey] ?? 0) + (float) ($row['revenue'] ?? 0);
        }
    }

    $dashboard['monthly_labels'] = [];
    $dashboard['monthly_revenues'] = [];
    $dashboard['monthly_names'] = [];
    $currentYear = date('Y');
    $currentMonth = (int) date('n');
    for ($i = 1; $i <= $currentMonth; $i++) {
        $monthKey = $currentYear . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $dashboard['monthly_labels'][] = strtoupper(date('M', strtotime($monthKey . '-01')));
        $dashboard['monthly_revenues'][] = $monthlyTrendMap[$monthKey] ?? 0;
        $dashboard['monthly_names'][] = date('F Y', strtotime($monthKey . '-01'));
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

$dashboard['today_label'] = strtoupper(date('l, d F Y'));

echo json_encode(['success' => true, 'data' => $dashboard]);
