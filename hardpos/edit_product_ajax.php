<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $id=intval($_POST['id']);
  $name=$conn->real_escape_string($_POST['product_name']);
  $cat=$conn->real_escape_string($_POST['category']);
  $unit=$conn->real_escape_string($_POST['unit']);
  $price=floatval($_POST['price']);
  $stock=intval($_POST['stock']);
  $ok=$conn->query("UPDATE products SET product_name='$name',category='$cat',unit='$unit',price=$price,stock=$stock WHERE id=$id");
  echo json_encode(['success'=>$ok,'updated'=>compact('id','name','cat','unit','price','stock')]);
}
