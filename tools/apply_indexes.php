<?php
// tools/apply_indexes.php
// Execute SQL statements from docs/apply_indexes.sql using config/db.php

chdir(__DIR__ . '/..');
require_once 'config/db.php';

$sqlFile = __DIR__ . '/../docs/apply_indexes.sql';
if (!is_file($sqlFile)) {
    echo "SQL file not found: {$sqlFile}\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Failed to read SQL file\n";
    exit(1);
}

// Split statements by semicolon; naive but sufficient for simple index file
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    try {
        echo "Executing: " . substr($stmt, 0, 120) . "...\n";
        $conn->exec($stmt);
        echo "OK\n";
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
