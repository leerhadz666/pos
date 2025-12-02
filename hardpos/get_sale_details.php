<?php
header('Content-Type: application/json');

// Include database connection
try {
    include 'db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if sale ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

$saleId = intval($_GET['id']);

// Check if database connection is established
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection not established']);
    exit;
}

// Get sale details - using only the columns that exist in your sales table
$saleQuery = "
    SELECT s.id, 
           s.customer_id,
           s.user_id,
           s.total_amount,
           s.paid_amount,
           s.payment_method,
           s.created_at,
           COALESCE(c.name, 'Walk-in') AS customer_name,
           u.username AS operator_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
";

try {
    $stmt = $conn->prepare($saleQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit;
    }

    $sale = $result->fetch_assoc();

    // Get sale items - using the correct table name 'sales_items'
    $itemsQuery = "
        SELECT si.product_id,
               p.product_name,
               si.quantity,
               si.price,
               si.subtotal
        FROM sales_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ";

    $stmt = $conn->prepare($itemsQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $itemsResult = $stmt->get_result();

    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }

    // Return response
    echo json_encode([
        'success' => true,
        'sale' => $sale,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>