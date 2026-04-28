<?php
include 'config/db.php';
global $conn;
try {
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'charters'");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
