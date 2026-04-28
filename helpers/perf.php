<?php
// helpers/perf.php - Simple slow query logger and EXPLAIN helper

function log_slow_query_entry($sql, $params, $duration_ms) {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/slow_queries.log';
    $entry = [
        'time' => date('c'),
        'duration_ms' => $duration_ms,
        'sql' => $sql,
        'params' => $params
    ];
    @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function explain_query_json($conn, $sql) {
    try {
        $stmt = $conn->query("EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) " . $sql);
        // PostgreSQL returns a single row with JSON text in the first column
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ($row && isset($row[0])) {
            $decoded = json_decode($row[0], true);
            return $decoded;
        }
        return null;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function extract_execution_time_from_explain($explainJson) {
    if (!is_array($explainJson) || empty($explainJson[0])) return null;
    $root = $explainJson[0];
    // Modern PG JSON contains 'Execution Time' at top-level
    if (isset($root['Execution Time'])) return floatval($root['Execution Time']);
    // Fallback: traverse plan and sum
    return null;
}
