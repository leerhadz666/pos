<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    // Validate and sanitize inputs
    $product_name = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $imagePath = null;

    // Debug: Log individual values
    error_log("Product Name: $product_name");
    error_log("Category: $category");
    error_log("Unit: $unit");
    error_log("Price: $price");
    error_log("Stock: $stock");

    // Validate required fields
    if (empty($product_name)) {
        die("Product name is required");
    }
    if (empty($category)) {
        die("Category is required");
    }
    if (empty($unit)) {
        die("Unit type is required");
    }
    if (!is_numeric($price) || $price <= 0) {
        die("Price must be a positive number");
    }
    if (!is_numeric($stock) || $stock < 0) {
        die("Stock must be a non-negative number");
    }

    // Get category ID from category name
    $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        die("Invalid category selected");
    }
    $category_data = $result->fetch_assoc();
    $category_id = $category_data['id'];
    $stmt->close();

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Generate unique filename
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check === false) {
            die("File is not an image");
        }

        // Check file size (5MB limit)
        if ($_FILES['image']['size'] > 5000000) {
            die("Image file is too large (max 5MB)");
        }

        // Allow certain file formats
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedTypes)) {
            die("Only JPG, JPEG, PNG & GIF files are allowed");
        }

        // Upload file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        } else {
            die("Error uploading image file");
        }
    }

    // Debug: Log before inserting
    error_log("Inserting: product_name=$product_name, category=$category, category_id=$category_id, unit=$unit, price=$price, stock=$stock");

    // Insert product into database - FIXED: Corrected parameter binding
    $stmt = $conn->prepare("INSERT INTO products (product_name, category, category_id, unit, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Database prepare error: " . $conn->error);
    }

    // FIXED: Correct parameter types - sssiids (stock is decimal, not integer)
   $stmt->bind_param("ssisdds", $product_name, $category, $category_id, $unit, $price, $stock, $imagePath);


    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        
        // Debug: Log successful insertion
        error_log("Product inserted with ID: $insert_id");
        
        // Verify the inserted data
        $verify_stmt = $conn->prepare("SELECT product_name, category, unit, price, stock FROM products WHERE id = ?");
        $verify_stmt->bind_param("i", $insert_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $inserted_data = $verify_result->fetch_assoc();
        error_log("Inserted data: " . print_r($inserted_data, true));
        
        // Log activity using prepared statement to prevent SQL injection
        $logText = "Added product: " . $product_name . " in category: " . $category . " with stock: " . $stock;
        $logStmt = $conn->prepare("INSERT INTO activity_logs (action) VALUES (?)");
        $logStmt->bind_param("s", $logText);
        $logStmt->execute();
        $logStmt->close();

        // Redirect with success message
        header("Location: inventory.php?success=1&msg=Product added successfully with stock: " . $stock);
        exit();
    } else {
        // Debug: Log insertion error
        error_log("Insertion error: " . $stmt->error);
        die("Database error: " . $stmt->error);
    }
} else {
    // Not a POST request
    header("Location: add_product.php?error=Invalid request method");
    exit();
}
?>