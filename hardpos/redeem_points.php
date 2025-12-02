<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)$_POST['customer_id'];
    $pointsToRedeem = (int)$_POST['points'];
    
    // Check if customer is loyal
    $stmt = $conn->prepare("SELECT is_loyal, points_balance FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($customer = $result->fetch_assoc()) {
        if (!$customer['is_loyal']) {
            echo json_encode(['success' => false, 'error' => 'Customer is not a loyal member']);
            exit;
        }
        
        if ($customer['points_balance'] < $pointsToRedeem) {
            echo json_encode(['success' => false, 'error' => 'Insufficient points balance']);
            exit;
        }
        
        // Update customer points balance
        $newBalance = $customer['points_balance'] - $pointsToRedeem;
        $updateStmt = $conn->prepare("UPDATE customers SET points_balance = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newBalance, $customerId);
        
        if ($updateStmt->execute()) {
            // Record the transaction
            $recordStmt = $conn->prepare("INSERT INTO points_transactions (customer_id, points_used, transaction_date) VALUES (?, ?, NOW())");
            $recordStmt->bind_param("ii", $customerId, $pointsToRedeem);
            $recordStmt->execute();
            
            // Process commission payout (1 point = 1 peso)
            // This would integrate with your payment system
            $commissionAmount = $pointsToRedeem; // 1 point = 1 peso
            
            echo json_encode([
                'success' => true, 
                'message' => "Successfully redeemed $pointsToRedeem points for ₱$commissionAmount commission",
                'new_balance' => $newBalance
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update points balance']);
        }
        
        $updateStmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
    }
    
    $stmt->close();
    $conn->close();
}
?>