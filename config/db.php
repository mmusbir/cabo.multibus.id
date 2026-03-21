<?php
// config/db.php - Database connection configuration (PostgreSQL via PDO)

// Database URL - Use environment variables in production
$env_url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');
$DATABASE_URL = $env_url ?: 'postgresql://postgres:password@localhost:5432/cabomultibus_db';

// Parse DATABASE_URL
$db_parts = parse_url($DATABASE_URL);
$db_host = $db_parts['host'] ?? 'localhost';
$db_port = $db_parts['port'] ?? 5432;
$db_user = urldecode($db_parts['user'] ?? 'postgres');
$db_pass = urldecode($db_parts['pass'] ?? '');
$db_name = ltrim($db_parts['path'] ?? '/postgres', '/');

// Build DSN
$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require";

try {
    $conn = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Set timezone to UTC+8 (WITA)
    $conn->exec("SET timezone = 'Asia/Makassar'");
} catch (PDOException $e) {
    if (empty($env_url)) {
        $keys_env = array_keys($_ENV);
        $keys_server = array_keys($_SERVER);
        die("Database connection failed: " . $e->getMessage() . "<br><br><b>Debug URL KOSONG.</b><br>Kunci \$_ENV yang tersedia: " . implode(', ', $keys_env) . "<br>Kunci \$_SERVER yang tersedia: " . implode(', ', $keys_server));
    }
    die('Database connection failed: ' . $e->getMessage() . '<br><br><b>Debug URL:</b> URL DITEMUKAN (TIDAK DITAMPILKAN ALASAN KEAMANAN)');
}

// Set timezone for PHP
date_default_timezone_set('Asia/Kuala_Lumpur');

// Include PDO compatibility helpers
require_once __DIR__ . '/pdo_compat.php';
