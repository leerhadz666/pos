<?php
include 'db.php';
$data = json_decode(file_get_contents('php://input'), true);
if(isset($data['id'])){
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i",$data['id']);
    if($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
?>
