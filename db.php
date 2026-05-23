<?php
$servername = "localhost:3308";
$dbname = "sa";
$dbUsername = "root";
$dbPassword = "";
$conn = mysqli_connect($servername, $dbUsername, $dbPassword, $dbname);
if (!$conn) {
    // 如果原連線失敗，這裡可以做個預防紀錄（可選）
    error_log("Mysqli connection failed: " . mysqli_connect_error());
}


// 2. 智慧擴充：新增 PDO 連線，專門供給 chatapi.php 使用
try {
    // 建立 PDO 連線，指定為 MySQL、資料庫名稱、連接埠與編碼
    $dsn = "mysql:host=localhost;port=3308;dbname={$dbname};charset=utf8mb4";
    
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
        // 設定錯誤模式為「拋出異常」，這樣 chatapi.php 的 try-catch 才能成功捕捉到錯誤
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // 設定預設取回資料的格式為「關聯陣列」，符合 chatapi 裡面的 $meals 處理邏輯
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // 關閉模擬預處理，改用 MySQL 原生預處理，安全性更高
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // 這裡不需要直接 echo 錯誤，讓引入它的 chatapi.php 內部的 try-catch 去捕捉並呈現即可
    error_log("PDO connection failed: " . $e->getMessage());
}
?>
