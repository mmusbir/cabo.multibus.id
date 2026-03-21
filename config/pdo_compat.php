<?php
// config/pdo_compat.php - PDO compatibility helper functions for PostgreSQL migration

/**
 * Check if a column exists in a table via information_schema
 * Replaces MySQL: SHOW COLUMNS FROM $table LIKE '$column'
 */
function db_column_exists(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ? LIMIT 1");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetch();
}

/**
 * Check if a table exists via information_schema
 * Replaces MySQL: SHOW TABLES LIKE '$table'
 */
function db_table_exists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_name = ? AND table_schema = 'public' LIMIT 1");
    $stmt->execute([$table]);
    return (bool) $stmt->fetch();
}

/**
 * Get column type info from information_schema
 * Replaces MySQL: SHOW COLUMNS FROM $table LIKE '$column' -> fetch Type
 */
function db_column_type(PDO $conn, string $table, string $column): ?string
{
    $stmt = $conn->prepare("SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ? LIMIT 1");
    $stmt->execute([$table, $column]);
    $row = $stmt->fetch();
    return $row ? $row['data_type'] : null;
}
