<?php
include('db.php');
session_start();

// 1. 檢查登入狀態
$u_id = $_SESSION['u_id'] ?? null;
if (!$u_id) {
    die("未登入");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    // 2. 檢查是否有檔案上傳錯誤
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('上傳失敗，錯誤代碼：" . $file['error'] . "'); window.location.href='profile.php';</script>";
        exit;
    }

    // 3. 檢查檔案類型（限制只能 JPG, PNG, GIF, WEBP 圖片）
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo "<script>alert('只允許上傳 JPG, PNG, GIF 或 WEBP 格式的圖片！'); window.location.href='profile.php';</script>";
        exit;
    }

    // 4. 檢查檔案大小（限制最大 3MB，避免大圖卡死主機空間）
    if ($file['size'] > 3 * 1024 * 1024) {
        echo "<script>alert('檔案過大，請勿超過 3MB！'); window.location.href='profile.php';</script>";
        exit;
    }

    // 設定目標資料夾（既然你已經建好了，這裡直接對準它）
    $target_dir = "uploads/";

    // 5. 生成唯一的新檔名（以 user_ID_時間戳記 命名，防止同學上傳同名檔案互相覆蓋）
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = "user_" . $u_id . "_" . time() . "." . $ext;
    $target_file = $target_dir . $new_filename;

    // 6. 將暫存檔搬移到你建立的 uploads 資料夾
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        // 【優化】撈出舊大頭貼檔名，從主機裡刪除舊圖，避免佔用無用空間
        $old_query = "SELECT user_photo FROM accounts WHERE u_id = $u_id";
        $old_res = $conn->query($old_query);
        if ($old_res && $row = $old_res->fetch_assoc()) {
            $old_photo = $row['user_photo'];
            if (!empty($old_photo) && file_exists($target_dir . $old_photo)) {
                @unlink($target_dir . $old_photo); // 刪除舊圖片檔案
            }
        }

        // 7. 更新資料庫的 user_photo 欄位為新檔名
        $sql_update = "UPDATE accounts SET user_photo = '$new_filename' WHERE u_id = $u_id";
        if ($conn->query($sql_update)) {
            // 成功後直接秒速重新整理回到個人檔案頁面
            header("Location: profile.php");
            exit;
        } else {
            echo "資料庫更新失敗：" . $conn->error;
        }
    } else {
        echo "<script>alert('搬移檔案失敗，請確認 uploads 資料夾是否有讀寫權限。'); window.location.href='profile.php';</script>";
    }
} else {
    header("Location: profile.php");
    exit;
}
?>