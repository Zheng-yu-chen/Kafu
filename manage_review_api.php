<?php
include('db.php');
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$com_id = $_POST['com_id'] ?? 0;

if ($action === 'approve') {
    // 將 status 改為 1 (通過)，讓前台 comment.php 可以看到
    $stmt = $conn->prepare("UPDATE comments SET status = 1 WHERE com_id = ?");
    $stmt->bind_param("i", $com_id);
    
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => $conn->error]);

} elseif ($action === 'reject') {
    // 拒絕則直接刪除該筆評論
    $stmt = $conn->prepare("DELETE FROM comments WHERE com_id = ?");
    $stmt->bind_param("i", $com_id);
    
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false]);
}
?>