<?php
session_start();
$_SESSION['admin_auth'] = true;
$_SESSION['admin_user'] = 'test';
require 'config/db.php';
$_GET['action'] = 'getScheduleSeats';
$_GET['rute'] = 'Makassar - Parepare';
$_GET['tanggal'] = '2026-03-25';
$_GET['jam'] = '09:00';
$_GET['unit'] = 1;

try {
    include 'admin/ajax/get_schedule_seats.php';
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
