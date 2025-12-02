<?php
include 'db.php';

$category = $_GET['category'] ?? '';
$units = [];

if ($category) {
    $stmt = $conn->prepare("SELECT DISTINCT unit FROM products WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $units[] = $row['unit'];
    }
}
header('Content-Type: application/json');
echo json_encode($units);
?>
