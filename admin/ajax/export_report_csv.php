<?php
/**
 * admin/ajax/export_report_csv.php - Export report data to CSV
 */

global $conn;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$type = isset($_GET['type']) ? $_GET['type'] : 'reguler';

$filename = "report_" . $type . "_" . $start_date . "_to_" . $end_date . ".csv";

// Output headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Row
if ($type === 'reguler') {
    fputcsv($output, ['Tanggal', 'Nama Penumpang', 'Nomor HP', 'Rute', 'Potongan (Rp)', 'Total (Rp)']);

    $sqlDetails = "SELECT b.tanggal, b.name, b.phone, b.rute, COALESCE(b.discount, 0) as discount, (b.price - COALESCE(b.discount, 0)) as final_price 
                   FROM bookings b 
                   WHERE b.status != 'canceled' AND b.pembayaran IN ('Lunas', 'Redbus', 'Traveloka') AND b.tanggal BETWEEN ? AND ? 
                   ORDER BY b.tanggal DESC";
} elseif ($type === 'bagasi') {
    fputcsv($output, ['Tanggal', 'Pengirim', 'Penerima', 'Layanan', 'Total (Rp)']);

    $sqlDetails = "SELECT DATE(l.created_at) as tanggal, l.sender_name as name, l.receiver_name as phone, s.name as rute, l.price as final_price 
                   FROM luggages l 
                   LEFT JOIN luggage_services s ON l.service_id = s.id 
                   WHERE l.status != 'canceled' AND DATE(l.created_at) BETWEEN ? AND ? 
                   ORDER BY l.created_at DESC";
} else {
    fputcsv($output, ['Tanggal', 'Nama Penyewa', 'Nomor HP', 'Jemput - Tujuan', 'Total (Rp)']);

    $sqlDetails = "SELECT start_date as tanggal, name, phone, CONCAT(pickup_point, ' - ', drop_point) as rute, price as final_price 
                   FROM charters 
                   WHERE bop_status = 'done' AND start_date BETWEEN ? AND ? 
                   ORDER BY start_date DESC";
}

$stmtDetails = $conn->prepare($sqlDetails);
$stmtDetails->execute([$start_date, $end_date]);

while ($row = $stmtDetails->fetch()) {
    fputcsv($output, [
        $row['tanggal'],
        $row['name'],
        $row['phone'],
        $row['rute'],
        number_format($row['final_price'], 0, ',', '.')
    ]);
}

fclose($output);
exit;
