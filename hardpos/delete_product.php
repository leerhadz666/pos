<?php
include 'db.php';
session_start();

// Validate ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete query
    $sql = "DELETE FROM products WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "✅ Product deleted successfully!";
    } else {
        $_SESSION['message'] = "❌ Error deleting product: " . $conn->error;
    }
} else {
    $_SESSION['message'] = "⚠️ Invalid product ID.";
}

// Redirect back
header("Location: inventory.php");
exit;
?>
