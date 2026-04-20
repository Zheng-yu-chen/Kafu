<?php
session_start();
include('db.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 接收餐點 ID
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : (isset($_GET['item_id']) ? intval($_GET['item_id']) : 0);

if ($item_id > 0) {
    // 💡 系統自動產生「今天」的日期與預設時段，用來安撫資料庫
    $eat_date = date('Y-m-d');
    $meal_time = '全天';

    // 判斷 1：登入使用者
    if (isset($_SESSION['u_id'])) {
        $u_id = $_SESSION['u_id'];
        
        try {
            // 第一次嘗試：把 eat_date 和 meal_time 都乖乖交給資料庫
            $stmt = $conn->prepare("INSERT INTO tray (u_id, item_id, eat_date, meal_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $u_id, $item_id, $eat_date, $meal_time);
            $stmt->execute();
            
            echo "<script>window.location.href='tray.php';</script>";
            
        } catch (mysqli_sql_exception $e) {
            // 💡 防呆機制：如果資料庫說「沒有 meal_time 這個欄位」，我們就只塞 eat_date
            if (strpos($e->getMessage(), "Unknown column 'meal_time'") !== false) {
                $stmt = $conn->prepare("INSERT INTO tray (u_id, item_id, eat_date) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $u_id, $item_id, $eat_date);
                $stmt->execute();
                echo "<script>window.location.href='tray.php';</script>";
            } else {
                // 如果還是出錯，再把真實錯誤印出來
                $real_error = addslashes($e->getMessage());
                echo "<script>alert('資料庫真實報錯：\\n" . $real_error . "'); window.history.back();</script>";
            }
        }
    } 
    // 判斷 2：訪客
    else {
        if (!isset($_SESSION['guest_tray'])) {
            $_SESSION['guest_tray'] = [];
        }
        // 訪客只存 ID 就好，因為版面上已經不需要顯示日期了
        $_SESSION['guest_tray'][] = $item_id;
        
        echo "<script>window.location.href='tray.php';</script>";
    }
} else {
    echo "<script>alert('無效的餐點！'); window.history.back();</script>";
}
?>