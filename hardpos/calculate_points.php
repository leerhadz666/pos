<?php
include 'db.php';

// This would be called during the checkout process
function calculateOrderPoints($orderItems) {
    $totalPoints = 0;
    
    foreach ($orderItems as $item) {
        $stmt = $conn->prepare("SELECT points_per_item FROM categories WHERE id = ?");
        $stmt->bind_param("i", $item['category_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($category = $result->fetch_assoc()) {
            $pointsPerItem = $category['points_per_item'];
            $totalPoints += $pointsPerItem * $item['quantity'];
        }
    }
    
    return $totalPoints;
}

// Example usage:
// $orderItems = [
//     ['category_id' => 1, 'quantity' => 2],
//     ['category_id' => 3, 'quantity' => 1]
// ];
// $points = calculateOrderPoints($orderItems);
// echo "Total points earned: " . $points;
?>