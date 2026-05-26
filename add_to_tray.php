<?php
session_start();

// 接收來自表單的參數
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$eat_date = isset($_POST['eat_date']) ? $_POST['eat_date'] : date('Y-m-d');
$meal_time = isset($_POST['meal_time']) ? $_POST['meal_time'] : '全天';

// 💡 接收數量參數，預設為 1
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// 確保數量至少為 1
if ($quantity < 1) {
    $quantity = 1;
}

if ($item_id > 0) {
    // 如果 Session 托盤還不存在，先初始化
    if (!isset($_SESSION['tray'])) {
        $_SESSION['tray'] = [];
    }
    
    // 將 quantity 與餐點資訊存進 Session 陣列中
    $_SESSION['tray'][] = [
        'item_id'   => $item_id,
        'eat_date'  => $eat_date,
        'meal_time' => $meal_time,
        'quantity'  => $quantity // 存入數量
    ];
    
    // 🎯 關鍵修改：拔掉原本的 header() 轉址，改成純輸出 success 文字
    // 這樣前端的 fetch() 收到這段文字，就會知道資料已經成功存入 Session，然後跳出新模態視窗！
    echo "success";
    exit();
    
} else {
    // 如果出錯，回傳 error 訊號
    echo "error";
    exit();
}
?>