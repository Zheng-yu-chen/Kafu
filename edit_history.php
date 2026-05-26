<?php
session_start();
include('db.php');

if (isset($_SESSION['u_id']) && isset($_POST['log_id'])) {
    $u_id = $_SESSION['u_id'];
    $log_id = intval($_POST['log_id']);
    
    // 獲取新資料
    $new_name  = $_POST['food_name'];
    $new_cal   = max(0, intval($_POST['calories']));
    $new_pro   = max(0.0, floatval($_POST['protein']));
    $new_fat   = max(0.0, floatval($_POST['fat']));      
    $new_carbs = max(0.0, floatval($_POST['carbs']));    
    $new_price = max(0.0, floatval($_POST['price']));
    $new_date  = $_POST['eat_date']; 
    $new_meal  = intval($_POST['daily_meal']);
    
    // 💡 小巧思：只改變日期並保留原本的時間更新到資料庫中
    // 🌟 補上：在 SET 後面加上 total_fat = ? 和 total_carbs = ?
    $sql = "UPDATE consumptionlogs 
            SET manual_item_name = ?,
                total_calories = ?,
                total_protein = ?,
                total_fat = ?,       -- 🌟 補上脂肪欄位
                total_carbs = ?,     -- 🌟 補上碳水欄位
                daily_meal = ?, 
                price = ?,
                recorded_at = STR_TO_DATE(CONCAT(?, ' ', TIME(recorded_at)), '%Y-%m-%d %H:%i:%s')
            WHERE log_id = ? AND u_id = ?";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("資料庫準備失敗: " . $conn->error);
    }
    
    // 🌟 修正後的完美參數綁定：
    // s = 字串 (String), i = 整數 (Integer), d = 浮點數 (Double)
    // 總共 10 個問號對應 10 個參數，格式字串為 "sidddisdii"
   $stmt->bind_param("sidddidsii", 
        $new_name,   // s -> manual_item_name
        $new_cal,    // i -> total_calories
        $new_pro,    // d -> total_protein
        $new_fat,    // d -> total_fat      
        $new_carbs,  // d -> total_carbs    
        $new_meal,   // i -> daily_meal
        $new_price,  // d -> price (修正為 d)
        $new_date,   // s -> CONCAT(?, ...) (修正為 s)
        $log_id,     // i -> WHERE log_id = ?
        $u_id        // i -> AND u_id = ?
    );
    
    if ($stmt->execute()) {
        header("Location: history.php");
        exit();
    } else {
        echo "修改失敗: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    echo "請求無效或未登入";
}
?>