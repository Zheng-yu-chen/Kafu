<?php
session_start();
include('db.php');

// 安全防護：沒登入的人不能加托盤
if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('請先登入，才能加入托盤喔！'); window.location.href='login.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u_id = $_SESSION['u_id']; // 從 Session 抓目前登入的人
    $item_id = intval($_POST['item_id']);
    $eat_date = $_POST['eat_date'];
    $meal_time = $_POST['meal_time'];

    // 將資料寫入 tray 資料表
    $sql = "INSERT INTO tray (u_id, item_id, eat_date, meal_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $u_id, $item_id, $eat_date, $meal_time);

    if ($stmt->execute()) {
        // 成功加入後，跳出提示並停留在原畫面
        echo "<script>alert('✅ 已成功加入托盤！'); history.back();</script>";
    } else {
        echo "<script>alert('發生錯誤：" . $conn->error . "'); history.back();</script>";
    }
}
?>