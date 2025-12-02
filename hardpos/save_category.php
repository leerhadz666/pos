<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']); // Category name
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 0; // Points per item
    $image_path = null;

    // Validate points is a non-negative integer
    if ($points < 0) {
        echo json_encode(['success' => false, 'error' => 'Points must be a non-negative number']);
        exit;
    }

    // Handle image upload
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['category_image']['tmp_name'];
        $fileName = $_FILES['category_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = uniqid('cat_', true) . '.' . $fileExtension;
            $uploadDir = 'uploads/'; // folder inside hardpos
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = $destPath; // relative path saved in DB
            } else {
                echo json_encode(['success' => false, 'error' => 'Error moving uploaded file']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid image type. Only JPG, PNG, GIF allowed.']);
            exit;
        }
    }

    // Insert category into database with points
    $stmt = $conn->prepare("INSERT INTO categories (category_name, image, points_per_item) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $image_path, $points);

    if ($stmt->execute()) {
        // ✅ Log activity
        $logText = "Added category: " . $name . " with " . $points . " points per item";
        $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");

        echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'name' => $name, 'points' => $points]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>