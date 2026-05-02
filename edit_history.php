<?php
session_start();
include('db.php');

if (isset($_SESSION['u_id']) && isset($_POST['log_id'])) {
    $u_id = $_SESSION['u_id'];
    $log_id = intval($_POST['log_id']);
    
    // 獲取新資料
    $new_name = $_POST['food_name'];
    $new_cal = intval($_POST['calories']);
    $new_pro = floatval($_POST['protein']);
    $new_date = $_POST['eat_date']; 
    $new_meal = intval($_POST['daily_meal']);
    
    // 💡 小巧思：只改變日期並更新所有數值[cite: 5]
    $sql = "UPDATE consumptionlogs 
            SET manual_item_name = ?,
                total_calories = ?,
                total_protein = ?,
                daily_meal = ?, 
                recorded_at = CONCAT(?, ' ', TIME(recorded_at)) 
            WHERE log_id = ? AND u_id = ?";
            
    $stmt = $conn->prepare($sql);
    // 綁定參數：s (name), i (cal), d (pro), i (meal), s (date), i (log_id), i (u_id)
    $stmt->bind_param("sidissi", $new_name, $new_cal, $new_pro, $new_meal, $new_date, $log_id, $u_id);
    
    if($stmt->execute()) {
        header("Location: history.php?status=updated");
    } else {
        echo "更新失敗: " . $conn->error;
    }
} else {
    header("Location: history.php");
}
exit();
?>