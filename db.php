<?php
$servername = "127.0.0.1:3307";
$dbname = "sa";
$dbUsername = "root";
$dbPassword = ""; // ← 這裡不要有空白
$conn = mysqli_connect($servername, $dbUsername, $dbPassword, $dbname);

// 檢查連線是否成功
if (!$conn) {
    die("資料庫連線失敗：" . mysqli_connect_error());
}
?>