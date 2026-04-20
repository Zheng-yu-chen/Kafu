<?php
// 💡 必須啟動 Session，才能讀取會員 ID 或清除訪客紀錄
session_start();
include('db.php');

// 判斷 1：如果是已登入的會員
if (isset($_SESSION['u_id'])) {
    $u_id = intval($_SESSION['u_id']); // 轉為整數確保安全
    
    if ($u_id > 0) {
        $sql = "DELETE FROM tray WHERE u_id = $u_id";
        $conn->query($sql);
    }
} 
// 判斷 2：如果是訪客
else if (isset($_SESSION['guest_tray'])) {
    // 直接把整個暫存陣列銷毀
    unset($_SESSION['guest_tray']);
}

// 💡 清空完畢後，自動導回托盤頁面 (瞬間重整的感覺)
header("Location: tray.php");
exit();
?>