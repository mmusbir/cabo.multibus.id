<?php
require 'config/db.php';
$stmt = $conn->query('SELECT layout FROM units WHERE layout IS NOT NULL LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
