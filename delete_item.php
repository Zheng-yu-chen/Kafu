<?php
session_start();
include('db.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 判斷 1：如果是訪客的餐點 (ID 包含 'guest_')
    if (strpos($id, 'guest_') === 0) {
        
        // 提取出後面的數字索引 (例如 'guest_0' 會變成 0)
        $index = intval(str_replace('guest_', '', $id));
        
        // 從 Session 陣列中移除該筆資料
        if (isset($_SESSION['guest_tray'][$index])) {
            unset($_SESSION['guest_tray'][$index]);
        }
        
    } 
    // 判斷 2：如果是登入會員的餐點 (ID 是純數字)
    else {
        $id = intval($id); // 轉成整數，防止 SQL 隱碼攻擊
        
        // 確保 ID 大於 0 才執行資料庫刪除
        if ($id > 0) {
            $sql = "DELETE FROM tray WHERE id = $id";
            $conn->query($sql);
        }
    }
}

// 💡 刪除完成後，自動把畫面導回托盤頁面 (取代原本的 JSON 回傳)
header("Location: tray.php");
exit();
?>