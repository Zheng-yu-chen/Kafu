<?php
session_start();
include('db.php');

if (!isset($_SESSION['u_id']) || !isset($_POST['item_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入或參數錯誤']);
    exit;
}

$u_id = $_SESSION['u_id'];
$item_id = intval($_POST['item_id']);

// 檢查是否已經收藏
$check = $conn->query("SELECT * FROM favorites WHERE u_id = $u_id AND item_id = $item_id");

if ($check->num_rows > 0) {
    // 已收藏 -> 取消收藏
    $conn->query("DELETE FROM favorites WHERE u_id = $u_id AND item_id = $item_id");
    echo json_encode(['success' => true, 'status' => 'removed']);
} else {
    // 未收藏 -> 新增收藏
    $conn->query("INSERT INTO favorites (u_id, item_id) VALUES ($u_id, $item_id)");
    echo json_encode(['success' => true, 'status' => 'added']);
}
?>