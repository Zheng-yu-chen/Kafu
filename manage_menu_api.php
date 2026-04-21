<?php
include('db.php');
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'update') {
    $id = $_POST['item_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $cal = $_POST['calories'];
    $pro = $_POST['protein'];

    // 注意：欄位名稱必須與你資料庫 items 表一致 (你的資料庫欄位是 name, price, calories, protein)
    $stmt = $conn->prepare("UPDATE items SET name=?, price=?, calories=?, protein=? WHERE item_id=?");
    $stmt->bind_param("sdddi", $name, $price, $cal, $pro, $id);
    
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => $conn->error]);

} elseif ($action === 'delete') {
    $id = $_POST['item_id'];
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false]);
}
?>