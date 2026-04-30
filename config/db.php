<?php
// config/db.php - Database connection configuration (PostgreSQL via PDO)

// Load .env file manually if exists (Simple Native Loader)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
    }
}

// Database configuration prioritized from DATABASE_URL then .env
$env_url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');

// Hardcode application timezone here to avoid repeated DB timezone queries
// Use Asia/Makassar (WITA)
if (!defined('TIMEZONE')) define('TIMEZONE', 'Asia/Makassar');

if ($env_url) {
    $db_parts = parse_url($env_url);
    $db_host = $db_parts['host'] ?? 'localhost';
    $db_port = $db_parts['port'] ?? 5432;
    $db_user = urldecode($db_parts['user'] ?? 'postgres');
    $db_pass = urldecode($db_parts['pass'] ?? '');
    $db_name = ltrim($db_parts['path'] ?? '/postgres', '/');
    
    // Parse query params for sslmode
    parse_str($db_parts['query'] ?? '', $query_params);
    $ssl_mode = $query_params['sslmode'] ?? 'prefer';
} else {
    $db_host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_port = $_ENV['DB_PORT'] ?? 5432;
    $db_user = $_ENV['DB_USERNAME'] ?? 'postgres';
    $db_pass = $_ENV['DB_PASSWORD'] ?? '';
    $db_name = $_ENV['DB_DATABASE'] ?? 'cabomultibus_db';
    // Local tends to disable SSL, while production (Supabase/Neon) tends to require it
    $ssl_mode = ($_ENV['DB_SSL'] ?? ($db_host === 'localhost' || $db_host === '127.0.0.1' ? 'disable' : 'require'));
}

// Build DSN with dynamic sslmode and timezone baked in (avoids per-session SET timezone query)
$tzEncoded = urlencode('Asia/Makassar');
$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode={$ssl_mode};options='--timezone=Asia/Makassar'";

try {
    $conn = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);

    // Set timezone to the application TIMEZONE constant (avoid querying pg_timezone_names)
    $conn->exec("SET timezone = '" . TIMEZONE . "'");
    // Enable simple query profiling: use custom PDOStatement class
    require_once __DIR__ . '/../helpers/perf.php';
    require_once __DIR__ . '/../helpers/db_profiler.php';
    // Attach our ProfilingStatement so execute() is timed and slow queries logged
    $conn->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('ProfilingStatement', array()));
} catch (PDOException $e) {
    if (empty($env_url)) {
        $keys_env = array_keys($_ENV);
        $keys_server = array_keys($_SERVER);
        die("Database connection failed: " . $e->getMessage() . "<br><br><b>Debug URL KOSONG.</b><br>Kunci \$_ENV yang tersedia: " . implode(', ', $keys_env) . "<br>Kunci \$_SERVER yang tersedia: " . implode(', ', $keys_server));
    }
    die('Database connection failed: ' . $e->getMessage() . '<br><br><b>Debug URL:</b> URL DITEMUKAN (TIDAK DITAMPILKAN ALASAN KEAMANAN)');
}

// Set timezone for PHP to the same application timezone
date_default_timezone_set(TIMEZONE);

// Include PDO compatibility helpers
require_once __DIR__ . '/pdo_compat.php';
