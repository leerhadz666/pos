<?php
include 'db.php';

// Example admin account
$username = "arvin";
$password = "nirvana"; // ⚠️ Stored as plain text
$role = "admin";

$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $password, $role);

if ($stmt->execute()) {
    echo "Admin user created!";
} else {
    echo "Error: " . $conn->error;
}
?>
