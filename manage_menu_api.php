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
    $fat = isset($_POST['fat']) ? $_POST['fat'] : 0;
    $carbs = isset($_POST['carbs']) ? $_POST['carbs'] : 0;

    // 更新欄位：name, price, calories, protein, fat, carbs
    $stmt = $conn->prepare("UPDATE items SET name=?, price=?, calories=?, protein=?, fat=?, carbs=? WHERE item_id=?");
    $stmt->bind_param("sdddddi", $name, $price, $cal, $pro, $fat, $carbs, $id);
    
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