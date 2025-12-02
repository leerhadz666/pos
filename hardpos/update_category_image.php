<?php
include 'db.php';

if(isset($_POST['id']) && isset($_FILES['image'])){
    $id = intval($_POST['id']);
    $file = $_FILES['image'];
    
    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if(!in_array($ext,$allowed)){
        echo json_encode(['success'=>false,'error'=>'Invalid image type.']);
        exit;
    }
    
    $uploadDir = 'uploads/';
    if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    
    $newName = 'cat_'.uniqid().'.'.$ext;
    $dest = $uploadDir.$newName;
    
    if(move_uploaded_file($file['tmp_name'],$dest)){
        $stmt = $conn->prepare("UPDATE categories SET image=? WHERE id=?");
        $stmt->bind_param("si",$dest,$id);
        if($stmt->execute()){
            echo json_encode(['success'=>true,'path'=>$dest]);
        } else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    } else {
        echo json_encode(['success'=>false,'error'=>'Failed to move uploaded file']);
    }
}
?>
