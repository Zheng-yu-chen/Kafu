<?php
session_start(); 
include('db.php'); 

// 1. 安全檢查
if (!isset($_SESSION['u_id'])) {
    die("請先登入");
}

$u_id = $_SESSION['u_id'];

// 2. 接收並處理表單資料
$food_name = $_POST['food_name'] ?? '未命名食物';
$calories  = intval($_POST['calories'] ?? 0);
$protein   = floatval($_POST['protein'] ?? 0);
$fat       = floatval($_POST['fat'] ?? 0); 
$carbs     = floatval($_POST['carbs'] ?? 0);
$price     = floatval($_POST['price'] ?? 0);
$meal_type = intval($_POST['daily_meal'] ?? 2);

// 3. 準備 SQL 
// 💡 總共 8 個問號，對應除了 item_id(NULL) 與 recorded_at(NOW()) 以外的 8 個欄位
$sql = "INSERT INTO consumptionlogs (u_id, item_id, manual_item_name, daily_meal, total_calories, total_protein, total_fat, total_carbs, price, recorded_at) 
        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("資料庫準備失敗: " . $conn->error);
}

// 4. 綁定參數 
// 💡 型別字串共 8 位："isiddddd"
// 對應變數：u_id(i), name(s), meal(i), cal(i), pro(d), fat(d), carbs(d), price(d)
$stmt->bind_param("isiddddd", 
    $u_id, 
    $food_name, 
    $meal_type, 
    $calories, 
    $protein, 
    $fat, 
    $carbs, 
    $price
);

// 5. 執行並跳轉
if ($stmt->execute()) {
    header("Location: history.php"); 
    exit();
} else {
    echo "寫入失敗：" . $stmt->error;
}
?>