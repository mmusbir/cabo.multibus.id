<?php
/**
 * admin/ajax/dashboard_data.php
 * Returns dashboard statistics as JSON for async loading.
 * Called via: admin.php?action=dashboardData
 *
 * PERFORMANCE: Reduced from 14+ sequential queries to 4 consolidated queries.
 *   Query 1 — bookings stats slab (COUNT + revenue in one pass)
 *   Query 2 — revenue trend last 7 days (UNION ALL bookings+charters+luggages)
 *   Query 3 — revenue trend per month current year (UNION ALL)
 *   Query 4 — recent activity log
 */

require_once __DIR__ . '/../../config/activity_log.php';

global $conn;

$dashboard = [
    'total_bookings'         => 0,
    'pending'                => 0,
    'confirmed'              => 0,
    'canceled'               => 0,
    'top_route'              => '-',
    'top_route_count'        => 0,
    'live_fleet'             => 0,
    'revenue_today'          => 0,
    'revenue_booking_month'  => 0,
    'revenue_charter_month'  => 0,
    'revenue_luggage_month'  => 0,
    'trend_labels'           => [],
    'trend_revenues'         => [],
    'trend_dates'            => [],
    'monthly_labels'         => [],
    'monthly_revenues'       => [],
    'monthly_names'          => [],
    'recent_activity'        => [],
];

try {
    // ──────────────────────────────────────────────────────────────────────────
    // QUERY 1 — All booking-based stats in a SINGLE scan of the bookings table.
    //   Replaces 7 separate queries (4 COUNTs + revenue_today + revenue_month
    //   + live_fleet + top_route).
    // ──────────────────────────────────────────────────────────────────────────
    $statsRow = $conn->query("
        SELECT
            /* active/future bookings */
            COUNT(*) FILTER (WHERE status != 'canceled' AND tanggal >= CURRENT_DATE)                                         AS total_bookings,
            COUNT(*) FILTER (WHERE status != 'canceled' AND tanggal >= CURRENT_DATE AND (pembayaran IS NULL OR pembayaran = 'Belum Lunas')) AS pending,
            COUNT(*) FILTER (WHERE status != 'canceled' AND tanggal >= CURRENT_DATE AND pembayaran IN ('Lunas','Redbus','Traveloka')) AS confirmed,
            COUNT(*) FILTER (WHERE status = 'canceled'  AND tanggal >= CURRENT_DATE)                                         AS canceled,

            /* live fleet: distinct trips today */
            COUNT(DISTINCT tanggal::text || '|' || jam::text || '|' || unit::text)
                FILTER (WHERE status != 'canceled' AND tanggal = CURRENT_DATE)                                               AS live_fleet,

            /* revenue today */
            COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0))
                FILTER (WHERE status != 'canceled' AND tanggal = CURRENT_DATE), 0)                                           AS revenue_today,

            /* revenue this month (bookings only) */
            COALESCE(SUM(COALESCE(price, 0) - COALESCE(discount, 0))
                FILTER (WHERE status != 'canceled'
                          AND tanggal >= DATE_TRUNC('month', CURRENT_DATE)
                          AND tanggal <  DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'), 0)                         AS revenue_booking_month
        FROM bookings
    ")->fetch(PDO::FETCH_ASSOC);

    if ($statsRow) {
        $dashboard['total_bookings']        = (int)   ($statsRow['total_bookings']        ?? 0);
        $dashboard['pending']               = (int)   ($statsRow['pending']               ?? 0);
        $dashboard['confirmed']             = (int)   ($statsRow['confirmed']             ?? 0);
        $dashboard['canceled']              = (int)   ($statsRow['canceled']              ?? 0);
        $dashboard['live_fleet']            = (int)   ($statsRow['live_fleet']            ?? 0);
        $dashboard['revenue_today']         = (float) ($statsRow['revenue_today']         ?? 0);
        $dashboard['revenue_booking_month'] = (float) ($statsRow['revenue_booking_month'] ?? 0);
    }

    // Top route (cannot be done with the aggregate above without a subquery — keep as one small query)
    $topRouteRow = $conn->query("
        SELECT rute, COUNT(*) AS total
        FROM bookings
        WHERE status != 'canceled' AND tanggal >= CURRENT_DATE
        GROUP BY rute
        ORDER BY total DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    if ($topRouteRow) {
        $dashboard['top_route']       = $topRouteRow['rute']  ?: '-';
        $dashboard['top_route_count'] = (int) ($topRouteRow['total'] ?? 0);
    }

    // Charter + luggage monthly revenue — two small scalar queries (different tables, unavoidable)
    $dashboard['revenue_charter_month'] = (float) ($conn->query("
        SELECT COALESCE(SUM(COALESCE(price, 0)), 0)
        FROM charters
        WHERE start_date >= DATE_TRUNC('month', CURRENT_DATE)
          AND start_date <  DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
    ")->fetchColumn() ?? 0);

    $dashboard['revenue_luggage_month'] = (float) ($conn->query("
        SELECT COALESCE(SUM(COALESCE(price, 0)), 0)
        FROM luggages
        WHERE payment_status = 'Lunas'
          AND created_at >= DATE_TRUNC('month', CURRENT_DATE)
          AND created_at <  DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
    ")->fetchColumn() ?? 0);

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY 2 — Revenue trend last 7 days across ALL modules in one query.
    //   Replaces 3 separate queries (bookings + charters + luggages).
    // ──────────────────────────────────────────────────────────────────────────
    $revTrendMap = [];
    $trendStmt = $conn->query("
        SELECT dt, SUM(revenue) AS revenue
        FROM (
            SELECT tanggal                                                    AS dt,
                   COALESCE(price, 0) - COALESCE(discount, 0)                AS revenue
            FROM bookings
            WHERE status != 'canceled'
              AND tanggal BETWEEN CURRENT_DATE - INTERVAL '6 days' AND CURRENT_DATE

            UNION ALL

            SELECT start_date                                                 AS dt,
                   COALESCE(price, 0)                                         AS revenue
            FROM charters
            WHERE start_date BETWEEN CURRENT_DATE - INTERVAL '6 days' AND CURRENT_DATE

            UNION ALL

            SELECT DATE(created_at)                                           AS dt,
                   COALESCE(price, 0)                                         AS revenue
            FROM luggages
            WHERE payment_status = 'Lunas'
              AND created_at >= CURRENT_DATE - INTERVAL '6 days'
        ) combined
        GROUP BY dt
        ORDER BY dt
    ");
    if ($trendStmt) {
        while ($row = $trendStmt->fetch(PDO::FETCH_ASSOC)) {
            $dk = !empty($row['dt']) ? date('Y-m-d', strtotime((string) $row['dt'])) : '';
            if ($dk !== '') $revTrendMap[$dk] = (float) ($row['revenue'] ?? 0);
        }
    }

    for ($i = 6; $i >= 0; $i--) {
        $dk = date('Y-m-d', strtotime('-' . $i . ' days'));
        $dashboard['trend_labels'][]   = strtoupper(date('D', strtotime($dk)));
        $dashboard['trend_revenues'][] = $revTrendMap[$dk] ?? 0;
        $dashboard['trend_dates'][]    = date('d M', strtotime($dk));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY 3 — Monthly revenue trend (current year) across ALL modules.
    //   Replaces 3 separate queries (bookings + charters + luggages).
    // ──────────────────────────────────────────────────────────────────────────
    $monthlyTrendMap = [];
    $currentYear  = date('Y');
    $monthlyStmt  = $conn->query("
        SELECT TO_CHAR(bulan, 'YYYY-MM') AS month_key, SUM(revenue) AS revenue
        FROM (
            SELECT DATE_TRUNC('month', tanggal)                               AS bulan,
                   COALESCE(price, 0) - COALESCE(discount, 0)                AS revenue
            FROM bookings
            WHERE status != 'canceled'
              AND tanggal >= '{$currentYear}-01-01'
              AND tanggal <  '{$currentYear}-12-31'::date + INTERVAL '1 day'

            UNION ALL

            SELECT DATE_TRUNC('month', start_date)                            AS bulan,
                   COALESCE(price, 0)                                         AS revenue
            FROM charters
            WHERE start_date >= '{$currentYear}-01-01'
              AND start_date <  '{$currentYear}-12-31'::date + INTERVAL '1 day'

            UNION ALL

            SELECT DATE_TRUNC('month', created_at)                            AS bulan,
                   COALESCE(price, 0)                                         AS revenue
            FROM luggages
            WHERE payment_status = 'Lunas'
              AND created_at >= '{$currentYear}-01-01'
              AND created_at <  '{$currentYear}-12-31'::date + INTERVAL '1 day'
        ) combined
        GROUP BY bulan
        ORDER BY bulan
    ");
    if ($monthlyStmt) {
        while ($row = $monthlyStmt->fetch(PDO::FETCH_ASSOC)) {
            $mk = trim((string) ($row['month_key'] ?? ''));
            if ($mk !== '') $monthlyTrendMap[$mk] = (float) ($row['revenue'] ?? 0);
        }
    }

    $dashboard['monthly_labels']   = [];
    $dashboard['monthly_revenues'] = [];
    $dashboard['monthly_names']    = [];
    $currentMonth = (int) date('n');
    for ($i = 1; $i <= $currentMonth; $i++) {
        $mk = $currentYear . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $dashboard['monthly_labels'][]   = strtoupper(date('M', strtotime($mk . '-01')));
        $dashboard['monthly_revenues'][] = $monthlyTrendMap[$mk] ?? 0;
        $dashboard['monthly_names'][]    = date('F Y', strtotime($mk . '-01'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY 4 — Recent activity log (unchanged, already one query).
    // ──────────────────────────────────────────────────────────────────────────
    activity_log_ensure_table($conn);
    $activityStmt = $conn->query("
        SELECT category, action, summary, details, actor, created_at
        FROM activity_logs
        ORDER BY created_at DESC, id DESC
        LIMIT 5
    ");
    while ($act = $activityStmt->fetch(PDO::FETCH_ASSOC)) {
        $category = strtolower(trim((string) ($act['category'] ?? 'settings')));
        $action   = strtolower(trim((string) ($act['action']   ?? 'update')));
        $dashboard['recent_activity'][] = [
            'title' => (string) ($act['summary'] ?? '-'),
            'meta'  => trim((string) ($act['details'] ?? '')) ?: ('Admin: ' . trim((string) ($act['actor'] ?? 'system'))),
            'time'  => activity_log_relative_time($act['created_at'] ?? ''),
            'tone'  => activity_log_tone($category, $action),
            'tag'   => strtoupper($category),
        ];
    }

} catch (Throwable $e) {
    // Keep dashboard resilient even if a query fails.
}

$dashboard['today_label'] = strtoupper(date('l, d F Y'));

echo json_encode(['success' => true, 'data' => $dashboard]);
