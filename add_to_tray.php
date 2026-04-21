<?php
session_start();

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$eat_date = isset($_POST['eat_date']) ? $_POST['eat_date'] : date('Y-m-d');
$meal_time = isset($_POST['meal_time']) ? $_POST['meal_time'] : '全天';

if ($item_id > 0) {
    // 無論會員或訪客，全部存進 PHP 暫存記憶體 (Session) 中
    if (!isset($_SESSION['tray'])) {
        $_SESSION['tray'] = [];
    }
    
    $_SESSION['tray'][] = [
        'item_id' => $item_id,
        'eat_date' => $eat_date,
        'meal_time' => $meal_time
    ];
    
    // 瞬間導回托盤，超級流暢
    header("Location: tray.php");
    exit();
} else {
    echo "<script>alert('無效的餐點！'); window.history.back();</script>";
}
?>