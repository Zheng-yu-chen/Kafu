<?php
session_start();
include('db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['u_id']) || !isset($_POST['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'login_required']);
    exit;
}

$u_id = intval($_SESSION['u_id']);
$item_id = intval($_POST['item_id']);

// 1. 檢查是否已收藏
$check = $conn->query("SELECT 1 FROM favorites WHERE u_id = $u_id AND item_id = $item_id");

if ($check && $check->num_rows > 0) {
    // 2. 已存在 -> 刪除 (這裡不用管 f_id，直接用 u_id 和 item_id 刪除即可)
    $sql = "DELETE FROM favorites WHERE u_id = $u_id AND item_id = $item_id";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'delete_failed']);
    }
} else {
    // 3. 不存在 -> 新增
    // 注意：如果你的 f_id 是 Auto Increment (自動遞增)，這裡就不需要填寫 f_id
    $sql = "INSERT INTO favorites (u_id, item_id) VALUES ($u_id, $item_id)";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'status' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'insert_failed']);
    }
}
?>