<?php
include 'db.php';
$start = $_GET['start_date'] ?? date('Y-m-01');
$end   = $_GET['end_date'] ?? date('Y-m-d');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sales_report.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Customer','Total Amount','Payment','Date']);

$res = $conn->query("SELECT * FROM sales WHERE DATE(created_at) BETWEEN '$start' AND '$end'");
while($row = $res->fetch_assoc()){
    fputcsv($out, [$row['id'],$row['customer_id'],$row['total_amount'],$row['payment_method'],$row['created_at']]);
}
fclose($out);
exit;
