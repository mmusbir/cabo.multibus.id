<?php
// tools/diagnose_queries.php
// Run EXPLAIN ANALYZE on a few known-heavy queries and write JSON results to logs/

chdir(__DIR__ . '/..');
require_once 'config/db.php';
require_once 'helpers/perf.php';

$queries = [
    // Use explicit column lists to make EXPLAIN more realistic and avoid fetching large unnecessary columns
    'SELECT id, nopol, merek, kapasitas, layout FROM units',
    'SELECT id, schedule_date, unit_id, route_id, seats_sold FROM schedules',
    'SELECT id, booking_ref, customer_id, schedule_id, status FROM bookings LIMIT 1000'
];

$results = [];
foreach ($queries as $q) {
    echo "Running EXPLAIN for: {$q}\n";
    $start = microtime(true);
    $explain = explain_query_json($conn, $q);
    $dur = (microtime(true) - $start) * 1000.0;
    $results[] = [
        'query' => $q,
        'duration_ms' => $dur,
        'explain' => $explain,
        'time' => date('c')
    ];

    // If execution time reported in explain JSON, optionally log slow
    $reported = null;
    if (is_array($explain)) {
        $reported = isset($explain[0]['Execution Time']) ? floatval($explain[0]['Execution Time']) : null;
        if ($reported === null && isset($explain[0][0]['Execution Time'])) $reported = floatval($explain[0][0]['Execution Time']);
    }
    $actual_ms = $reported !== null ? $reported : $dur;
    if ($actual_ms > 100) {
        log_slow_query_entry($q, [], $actual_ms);
    }
}

$dir = __DIR__ . '/../logs';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
file_put_contents($dir . '/query_diagnostics.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Diagnostics written to logs/query_diagnostics.json\n";
