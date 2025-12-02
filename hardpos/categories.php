<?php
include 'db.php';

// Handle add category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_name'])) {
    $category_name = trim($_POST['category_name']);
    if ($category_name != "") {
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle delete category
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM categories WHERE id=$id");
}

// Fetch categories
$result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Categories</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    form { margin-bottom: 20px; }
    table { width: 400px; border-collapse: collapse; }
    table, th, td { border: 1px solid #ccc; padding: 8px; }
    th { background: #333; color: #fff; }
  </style>
</head>
<body>
  <h2>Manage Categories</h2>

  <form method="POST">
    <input type="text" name="category_name" placeholder="Enter new category" required><label>Category Image</label>
  <input type="file" name="category_image" accept="image/*">
  <div style="margin-top:15px; display:flex; justify-content:flex-end; gap:10px;">
    <button type="submit" class="btn">Save</button>
    <button type="button" id="closeModal" class="btn btn-secondary">Cancel</button>
  </div>
    <button type="submit">Add</button>
  </form>

  <table>
    <tr><th>ID</th><th>Category</th><th>Action</th></tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['category_name']) ?></td>
      <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this category?')">Delete</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
