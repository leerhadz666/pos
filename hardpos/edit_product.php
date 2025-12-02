<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $unit = trim($_POST['unit']);
    $price = floatval($_POST['price']);
    $stock = floatval($_POST['stock']);
    
    // Get category ID from category name
    $stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $category_data = $result->fetch_assoc();
    $category_id = $category_data['id'];
    
    // Handle image upload if provided
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Validate image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
            $imagePath = $targetFile;
        }
    }
    
    // Update product
    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, category = ?, category_id = ?, unit = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        $stmt->bind_param("sssidisi", $product_name, $category, $category_id, $unit, $price, $stock, $imagePath, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, category = ?, category_id = ?, unit = ?, price = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("sssidii", $product_name, $category, $category_id, $unit, $price, $stock, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
}
?>