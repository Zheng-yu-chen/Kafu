<?php
session_start(); // 修復 Warning: Undefined variable $_SESSION[cite: 2]
include('db.php'); // 確認這是你的連線檔案名稱

// 安全檢查：確保有登入
if (!isset($_SESSION['u_id'])) {
    die("請先登入");
}

$u_id = $_SESSION['u_id'];

// 獲取表單資料，並使用 ?? 運算子防止 Undefined index Warning[cite: 2]
$food_name = $_POST['food_name'] ?? '未命名食物';
$calories  = $_POST['calories'] ?? 0;
$protein   = $_POST['protein'] ?? 0;
$meal_type = $_POST['daily_meal'] ?? 2; // 對應 history.php 彈窗中的 name="daily_meal"[cite: 2]

// 寫入資料庫：item_id 此時為 NULL，寫入 manual_item_name 欄位[cite: 2]
$sql = "INSERT INTO consumptionlogs (u_id, item_id, manual_item_name, daily_meal, total_calories, total_protein, recorded_at) 
        VALUES (?, NULL, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isidd", $u_id, $food_name, $meal_type, $calories, $protein);

if ($stmt->execute()) {
    // 執行完後導回 history.php[cite: 2]
    header("Location: history.php");
    exit();
} else {
    echo "紀錄失敗：" . $conn->error;
}
?>