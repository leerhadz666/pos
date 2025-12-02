<?php  
include "db.php";

 $cat = trim($_GET['category'] ?? 'all');
 $search = trim($_GET['search'] ?? '');

// If search is provided → search mode
if ($search !== '') {
    // Check if search matches a category
    // FIXED: Products table uses 'category' column, not 'category_name'
    $catCheck = $conn->prepare("SELECT DISTINCT category FROM products WHERE LOWER(category) = LOWER(?) LIMIT 1");
    $catCheck->bind_param("s", $search);
    $catCheck->execute();
    $catResult = $catCheck->get_result();

    if ($catResult->num_rows > 0) {
        // Match found → all products in that category
        // FIXED: Use 'category' column from products table
        $stmt = $conn->prepare("SELECT id, product_name, price, category, stock, unit, image 
                                FROM products 
                                WHERE LOWER(category) = LOWER(?)");
        $stmt->bind_param("s", $search);
    } else {
        // No category match → search product name (partial match)
        $like = "%$search%";
        $stmt = $conn->prepare("SELECT id, product_name, price, category, stock, unit, image 
                                FROM products 
                                WHERE product_name LIKE ?");
        $stmt->bind_param("s", $like);
    }
} 
// If no search but category is chosen → category filter
elseif ($cat !== 'all') {
    // FIXED: Use 'category' column from products table
    $stmt = $conn->prepare("SELECT id, product_name, price, category, stock, unit, image 
                            FROM products 
                            WHERE LOWER(category) = LOWER(?)");
    $stmt->bind_param("s", $cat);
} 
// No search, no category filter → return all
else {
    // FIXED: Use 'category' column from products table
    $stmt = $conn->prepare("SELECT id, product_name, price, category, stock, unit, image FROM products");
}

 $stmt->execute();
 $result = $stmt->get_result();

 $products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>