<?php
session_start();
if (isset($_POST['theme'])) {
    $_SESSION['theme'] = $_POST['theme'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>