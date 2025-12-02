<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hardware_pos";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
