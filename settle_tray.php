<?php
session_start();
include('db.php');

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

if (!empty($_SESSION['tray'])) {
    $u_id = $_SESSION['u_id'];
    
    // 對應你的 consumptionlogs 表格欄位：u_id, item_id, daily_meal, total_calories, total_protein, total_fat, total_carbs, price, recorded_at
    $stmt = $conn->prepare("INSERT INTO consumptionlogs (u_id, item_id, daily_meal, total_calories, total_protein, total_fat, total_carbs, price, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $meal_map = ['早餐'=>1, '午餐'=>2, '晚餐'=>3, '點心'=>4, '全天'=>0];

    foreach ($_SESSION['tray'] as $item) {
        $item_id = intval($item['item_id']);
        
        // 抓取餐點營養資訊與價格
        $cal = 0;
        $protein = 0;
        $fat = 0;
        $carbs = 0;
        $price = 0;
        $res = $conn->query("SELECT calories, protein, fat, carbs, price FROM items WHERE item_id = $item_id");
        if ($row = $res->fetch_assoc()) {
            $cal = $row['calories'];
            $protein = $row['protein'];
            $fat = $row['fat'];
            $carbs = $row['carbs'];
            $price = $row['price'];
        }
        
        $daily_meal = isset($meal_map[$item['meal_time']]) ? $meal_map[$item['meal_time']] : 0;
        
        // 將使用者選擇的日期加上現在的時間，存入 recorded_at
        $recorded_at = $item['eat_date'] . ' ' . date('H:i:s');
        
        // 修正後：將 "iiidddds" 改為 "iiiddddds" (多加一個 d)
        $stmt->bind_param("iiiddddds", $u_id, $item_id, $daily_meal, $cal, $protein, $fat, $carbs, $price, $recorded_at);
        $stmt->execute();
    }
    
    // 結算完成後，徹底清空暫存托盤
    $_SESSION['tray'] = [];
    
    // 結算完自動跳轉到個人檔案看進度條
    echo "<script>alert('結算成功！已記錄到您的個人檔案。'); window.location.href='profile.php';</script>";
} else {
    header("Location: tray.php");
}
?>