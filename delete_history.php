<?php
session_start();
include('db.php');

if (isset($_SESSION['u_id']) && isset($_GET['log_id'])) {
    $u_id = $_SESSION['u_id'];
    $log_id = intval($_GET['log_id']);
    
    // 🔒 雙重驗證：必須是自己的紀錄才能刪除
    $stmt = $conn->prepare("DELETE FROM consumptionlogs WHERE log_id = ? AND u_id = ?");
    $stmt->bind_param("ii", $log_id, $u_id);
    $stmt->execute();
}

// 刪除完成後，瞬間跳回歷史頁面
header("Location: history.php");
exit();
?>