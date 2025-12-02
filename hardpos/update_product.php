<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $brand = $_POST['brand'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, brand=?, unit=?, price=?, stock=? WHERE id=?");
    $stmt->bind_param("sissdii", $product_name, $category, $brand, $unit, $price, $stock, $id);

    if ($stmt->execute()) {
        header("Location: inventory.php?msg=updated");
        exit;
    } else {
        echo "Error updating product: " . $stmt->error;
    }
}
