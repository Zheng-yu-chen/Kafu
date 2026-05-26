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
    $new_price = floatval($_POST['price']);
    $new_date = $_POST['eat_date']; 
    $new_meal = intval($_POST['daily_meal']);
    
    // 💡 小巧思：只改變日期並保留原本的時間更新到資料庫中
    $sql = "UPDATE consumptionlogs 
            SET manual_item_name = ?,
                total_calories = ?,
                total_protein = ?,
                daily_meal = ?, 
                price = ?,
                recorded_at = STR_TO_DATE(CONCAT(?, ' ', TIME(recorded_at)), '%Y-%m-%d %H:%i:%s')
            WHERE log_id = ? AND u_id = ?";
            
    $stmt = $conn->prepare($sql);
    
    // 🌟 修正後的完美參數綁定：
    // s = 字串 (String), i = 整數 (Integer), d = 浮點數 (Double)
    // 對應順序：
    // 1. manual_item_name -> $new_name (s)
    // 2. total_calories   -> $new_cal (i)
    // 3. total_protein    -> $new_pro (d)
    // 4. daily_meal       -> $new_meal (i)
    // 5. price            -> $new_price (d)
    // 6. CONCAT(?, ...)   -> $new_date (s)
    // 7. WHERE log_id = ? -> $log_id (i)
    // 8. AND u_id = ?     -> $u_id (i)
    $stmt->bind_param("sididsii", $new_name, $new_cal, $new_pro, $new_meal, $new_price, $new_date, $log_id, $u_id);
    
    if($stmt->execute()) {
        // 更新成功，跳轉回歷史紀錄頁面
        header("Location: history.php?status=updated");
    } else {
        // 更新失敗，印出錯誤訊息
        echo "更新失敗: " . $conn->error;
    }
} else {
    // 驗證失敗直接踢回歷史頁面
    header("Location: history.php");
}
exit();
?>