<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Get recent customers (based on last purchase date)
 $stmt = $conn->prepare("SELECT c.id, c.name, c.is_loyal, MAX(s.created_at) as last_purchase
                       FROM customers c
                       LEFT JOIN sales s ON c.id = s.customer_id
                       GROUP BY c.id
                       ORDER BY last_purchase DESC
                       LIMIT 5");

 $stmt->execute();
 $result = $stmt->get_result();

 $customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode($customers);
?>