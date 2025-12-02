<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Get search query
 $query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// Prepare search statement - search by name and phone
 $stmt = $conn->prepare("SELECT id, name, phone, is_loyal FROM customers 
                       WHERE name LIKE ? OR phone LIKE ? 
                       ORDER BY is_loyal DESC, name ASC 
                       LIMIT 10");

 $searchTerm = "%$query%";
 $stmt->bind_param("ss", $searchTerm, $searchTerm);
 $stmt->execute();
 $result = $stmt->get_result();

 $customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode($customers);
?>