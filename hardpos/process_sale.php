<?php
include "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Always send JSON
header('Content-Type: application/json');

// Decode request body
 $data = json_decode(file_get_contents("php://input"), true);

 $cart        = $data['cart']        ?? [];
 $payment     = $data['payment']     ?? 'Cash';
 $customer_id = $data['customer_id'] ?? null;

// ✅ Get logged-in user ID (operator)
session_start();
 $user_id = $_SESSION['user']['id'] ?? $_SESSION['id'] ?? null;

// Validate cart
if (empty($cart)) {
    echo json_encode(["success" => false, "message" => "Cart empty"]);
    exit;
}

// If payment is Utang but no customer selected → block
if ($payment === "Utang" && !$customer_id) {
    echo json_encode(["success" => false, "message" => "Customer is required for Utang"]);
    exit;
}

 $total = 0;
foreach ($cart as $item) {
    if (!isset($item['qty'], $item['price'], $item['id'])) {
        echo json_encode(["success" => false, "message" => "Invalid cart item format"]);
        exit;
    }
    $total += $item['qty'] * $item['price'];
}

// Initialize points variables
 $pointsEarned = 0;
 $isLoyalCustomer = false;

// Check if customer is loyal (only if customer_id is provided)
if ($customer_id) {
    $stmt = $conn->prepare("SELECT is_loyal FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if ($customer && $customer['is_loyal']) {
        $isLoyalCustomer = true;
        
        // Calculate points for each item in cart
        foreach ($cart as $item) {
            // Get product category and points_per_item
            $stmt = $conn->prepare("
                SELECT c.points_per_item 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $item['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($product && $product['points_per_item'] > 0) {
                $pointsEarned += $product['points_per_item'] * $item['qty'];
            }
        }
    }
}

try {
    // Start transaction
    $conn->begin_transaction();

    // ✅ Insert into sales with user_id
    $stmt = $conn->prepare("INSERT INTO sales (customer_id, total_amount, payment_method, user_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) throw new Exception($conn->error);

    // If no customer (cash/gcash), set null
    if ($customer_id === null || $customer_id === "") {
        $null = null;
        $stmt->bind_param("idsi", $null, $total, $payment, $user_id);
    } else {
        $stmt->bind_param("idsi", $customer_id, $total, $payment, $user_id);
    }

    $stmt->execute();
    $sale_id = $stmt->insert_id;

    // Insert sale items + update stock
    foreach ($cart as $item) {
        $pid   = (int)$item['id'];
        $qty = (float)$item['qty'];
        $price = (float)$item['price'];
        $sub   = $qty * $price;

        $stmt = $conn->prepare("INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal) 
                        VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddd", $sale_id, $pid, $qty, $price, $sub);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt2->bind_param("di", $qty, $pid);
        $stmt2->execute();
    }

    // Optional: Insert into utang tracking
    if ($payment === "Utang") {
        $stmt3 = $conn->prepare("INSERT INTO utang (customer_id, sale_id, amount) VALUES (?, ?, ?)");
        $stmt3->bind_param("iid", $customer_id, $sale_id, $total);
        $stmt3->execute();
    }

    // Handle loyalty points if customer is loyal and points were earned
    if ($isLoyalCustomer && $pointsEarned > 0) {
        // Update customer points balance
        $stmt4 = $conn->prepare("UPDATE customers SET points_balance = points_balance + ? WHERE id = ?");
        $stmt4->bind_param("ii", $pointsEarned, $customer_id);
        $stmt4->execute();
        
        // Record points transaction
        $stmt5 = $conn->prepare("INSERT INTO points_transactions (customer_id, points_earned, transaction_date, sale_id) VALUES (?, ?, NOW(), ?)");
        $stmt5->bind_param("iii", $customer_id, $pointsEarned, $sale_id);
        $stmt5->execute();
    }

    // Log activity
    $logText = "New sale: ID $sale_id, Amount: ₱$total, Payment: $payment";
    if ($customer_id) {
        $logText .= ", Customer: $customer_id";
    }
    if ($pointsEarned > 0) {
        $logText .= ", Points earned: $pointsEarned";
    }
    
    $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");

    // Commit transaction
    $conn->commit();

    // Prepare response
    $response = ["success" => true, "sale_id" => $sale_id];
    if ($pointsEarned > 0) {
        $response["points_earned"] = $pointsEarned;
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "DB error: " . $e->getMessage()
    ]);
}
?>