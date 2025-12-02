<?php
include 'db.php';

// Get the JSON data from the request
 $data = json_decode(file_get_contents('php://input'), true);
 $cart = $data['cart'] ?? [];

// Calculate total amount
 $totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['qty'];
}

// Simple calculation: 1 point per peso
 $totalPoints = floor($totalAmount);

echo json_encode([
    'success' => true,
    'points' => $totalPoints
]);
?>