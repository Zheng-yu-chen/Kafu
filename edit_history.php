<?php
session_start();
include('db.php');

if (isset($_SESSION['u_id']) && isset($_POST['log_id'])) {
    $u_id = $_SESSION['u_id'];
    $log_id = intval($_POST['log_id']);
    $new_date = $_POST['eat_date']; // YYYY-MM-DD
    $new_meal = intval($_POST['daily_meal']);
    
    // 💡 小巧思：只改變日期 (保留原本紀錄的小時與分鐘) 並更新時段
    $sql = "UPDATE consumptionlogs 
            SET daily_meal = ?, 
                recorded_at = CONCAT(?, ' ', TIME(recorded_at)) 
            WHERE log_id = ? AND u_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $new_meal, $new_date, $log_id, $u_id);
    $stmt->execute();
}

header("Location: history.php");
exit();
?>