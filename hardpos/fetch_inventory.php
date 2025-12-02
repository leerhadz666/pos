<?php
include 'db.php';

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$unit     = isset($_GET['unit']) ? $_GET['unit'] : '';
$search   = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$sql = "SELECT p.id, p.product_name, p.category, p.unit, p.price, p.stock, p.created_at 
        FROM products p WHERE 1=1";

// Build conditions
if (!empty($category)) {
    $sql .= " AND p.category = '" . $conn->real_escape_string($category) . "'";
}
if (!empty($unit)) {
    $sql .= " AND p.unit = '" . $conn->real_escape_string($unit) . "'";
}
if (!empty($search)) {
    $sql .= " AND p.product_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$sql .= " ORDER BY p.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table class='table table-striped'>";
    echo "<thead><tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Unit</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Created At</th>
            <th>Action</th>
          </tr></thead><tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['product_name']}</td>
                <td>{$row['category']}</td>
                <td>{$row['unit']}</td>
                <td>{$row['price']}</td>
                <td>{$row['stock']}</td>
                <td>{$row['created_at']}</td>
                <td>
                    <button class='btn btn-sm btn-primary editBtn'
                        data-id='{$row['id']}'
                        data-name='{$row['product_name']}'
                        data-category='{$row['category']}'
                        data-unit='{$row['unit']}'
                        data-price='{$row['price']}'
                        data-stock='{$row['stock']}'>
                        Edit
                    </button>
                </td>
              </tr>";
    }

    echo "</tbody></table>";
} else {
    echo "<p class='text-center text-muted'>No products found.</p>";
}

$conn->close();
?>
