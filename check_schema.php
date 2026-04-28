<?php
include 'config/db.php';
global $conn;
$stmt = $conn->query("SELECT table_schema, table_name, column_name FROM information_schema.columns WHERE table_name = 'charters'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
