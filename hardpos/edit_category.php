<?php
include 'db.php';
 $data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'], $data['name'], $data['points'])) {
    // Validate points is a non-negative integer
    if (!is_numeric($data['points']) || $data['points'] < 0 || (int)$data['points'] != $data['points']) {
        echo json_encode(['success' => false, 'error' => 'Points must be a non-negative integer']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE categories SET category_name=?, points_per_item=? WHERE id=?");
    $stmt->bind_param("sii", $data['name'], $data['points'], $data['id']);
    
    if ($stmt->execute()) {
        // Log the category update
        $logText = "Updated category: " . $data['name'] . " with " . $data['points'] . " points per item";
        $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
}
?>