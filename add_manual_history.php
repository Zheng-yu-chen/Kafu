<?php
session_start(); 
include('db.php'); 

// 1. 安全檢查
if (!isset($_SESSION['u_id'])) {
    die("請先登入");
}

$u_id = $_SESSION['u_id'];

// 2. 接收並處理表單資料 (此處變數命名微調與您的 INSERT 完美對應)
$food_name = $_POST['food_name'] ?? '未命名食物';
$calories  = intval($_POST['calories'] ?? 0);
$protein   = floatval($_POST['protein'] ?? 0);
$fat       = floatval($_POST['fat'] ?? 0); 
$carbs     = floatval($_POST['carbs'] ?? 0);
$price     = floatval($_POST['price'] ?? 0);
$meal_type = intval($_POST['daily_meal'] ?? 4); // 預設 4 代表其他
$eat_date  = $_POST['eat_date'] ?? date('Y-m-d'); // 抓取前端點選的日期

// 3. 準備 SQL (補齊 total_fat 與 total_carbs 欄位，並動態結合使用者點選的日期與當前時間)
$sql = "INSERT INTO consumptionlogs (u_id, item_id, manual_item_name, daily_meal, total_calories, total_protein, total_fat, total_carbs, price, recorded_at) 
        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, STR_TO_DATE(CONCAT(?, ' ', TIME(NOW())), '%Y-%m-%d %H:%i:%s'))";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("資料庫準備失敗: " . $conn->error);
}

// 4. 參數綁定校對 (共 9 個參數)：
// s = 字串, i = 整數, d = 浮點數
// 欄位對應順序：
// u_id (i), manual_item_name (s), daily_meal (i), total_calories (i), total_protein (d), total_fat (d), total_carbs (d), price (d), CONCAT日期 (s)
$stmt->bind_param("isiidddds", $u_id, $food_name, $meal_type, $calories, $protein, $fat, $carbs, $price, $eat_date);

if ($stmt->execute()) {
    // 新增成功，自動跳轉回歷史紀錄頁面
    header("Location: history.php?status=success");
} else {
    echo "新增失敗: " . $stmt->error;
}

$stmt->close();
$conn->close();
exit();
?>