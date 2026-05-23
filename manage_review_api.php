<?php
session_start();
include('db.php');
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$com_id = intval($_POST['com_id'] ?? 0);
$current_user_id = $_SESSION['u_id'] ?? null; 
$role_id = $_SESSION['role_id'] ?? null;        

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $com_id === 0) {
    echo json_encode(['success' => false, 'message' => '無效的請求']);
    exit;
}

// =========================================================================
// 🎯 區塊一：管理員審核與刪除邏輯
// =========================================================================

if ($action === 'approve') {
    if ($role_id != 1) { echo json_encode(['success' => false, 'message' => '權限不足！']); exit; }
    $stmt = $conn->prepare("UPDATE comments SET status = 1 WHERE com_id = ?");
    $stmt->bind_param("i", $com_id);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;

} elseif ($action === 'reject') {
    if ($role_id != 1) { echo json_encode(['success' => false, 'message' => '權限不足！']); exit; }
    
    // 🎯 管理員刪除評論時，同步將硬碟中的實體圖片刪除釋放空間
    $stmt_img = $conn->prepare("SELECT com_img FROM comments WHERE com_id = ?");
    $stmt_img->bind_param("i", $com_id);
    $stmt_img->execute();
    $old_img = $stmt_img->get_result()->fetch_assoc();
    if ($old_img && !empty($old_img['com_img']) && file_exists('food/' . $old_img['com_img'])) {
        unlink('food/' . $old_img['com_img']);
    }

    $stmt = $conn->prepare("DELETE FROM comments WHERE com_id = ?");
    $stmt->bind_param("i", $com_id);
    
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

// =========================================================================
// 🎯 區塊二：學生 (一般註冊用戶) 編輯與刪除自身評論 (支援圖片上傳與移除)
// =========================================================================

function checkCommentOwnership($conn, $com_id, $current_user_id) {
    if ($current_user_id === null) return false;
    $stmt_check = $conn->prepare("SELECT u_id FROM comments WHERE com_id = ?");
    $stmt_check->bind_param("i", $com_id);
    $stmt_check->execute();
    $res = $stmt_check->get_result()->fetch_assoc();
    return ($res && $res['u_id'] == $current_user_id);
}

if ($action === 'user_update') {
    $content = trim($_POST['content'] ?? '');
    $delete_photo_flag = intval($_POST['delete_photo_flag'] ?? 0); // 1 代表使用者點選了移除原有照片

    if ($content === '') {
        echo json_encode(['success' => false, 'message' => '評論內容不能留空喔！']);
        exit;
    }

    // 交叉安全驗證：防止篡改他人資料
    if (!checkCommentOwnership($conn, $com_id, $current_user_id)) {
        echo json_encode(['success' => false, 'message' => '安全錯誤：您無權修改此筆評論！']);
        exit;
    }

    // 1. 先抓出這則評論原有的相片狀態
    $stmt_current = $conn->prepare("SELECT com_img FROM comments WHERE com_id = ?");
    $stmt_current->bind_param("i", $com_id);
    $stmt_current->execute();
    $current_comment = $stmt_current->get_result()->fetch_assoc();
    $final_image_name = $current_comment['com_img'] ?? null;

    // 2. 處理移除現有照片的動作
    if ($delete_photo_flag === 1 && $final_image_name !== null) {
        if (file_exists('food/' . $final_image_name)) {
            unlink('food/' . $final_image_name); // 實體移除硬碟舊照片
        }
        $final_image_name = null; // 清空資料庫欄位暫存值
    }

    // 3. 處理新上傳照片的接收
    if (isset($_FILES['comment_photo']) && $_FILES['comment_photo']['error'] === UPLOAD_ERR_OK) {
        // 如果原本已經有一張照片且刚才沒移除，現在要更換新照片，就把先前的砍掉
        if ($final_image_name !== null && file_exists('food/' . $final_image_name)) {
            unlink('food/' . $final_image_name);
        }

        $file_extension = pathinfo($_FILES['comment_photo']['name'], PATHINFO_EXTENSION);
        // 使用毫秒權重為新圖片重新命名
        $final_image_name = 'food_' . $com_id . '_' . time() . '.' . $file_extension;
        
        // 確保儲存目錄存在
        if (!is_dir('food')) { mkdir('food', 0777, true); }
        
        move_uploaded_file($_FILES['comment_photo']['tmp_name'], 'food/' . $final_image_name);
    }

    // 4. 更新至資料庫
    $stmt = $conn->prepare("UPDATE comments SET content = ?, com_img = ?, created_at = NOW(), is_edited = 1 WHERE com_id = ?");
    $stmt->bind_param("ssi", $content, $final_image_name, $com_id);
    
    if ($stmt->execute()) {
        $stmt_time = $conn->prepare("SELECT created_at FROM comments WHERE com_id = ?");
        $stmt_time->bind_param("i", $com_id);
        $stmt_time->execute();
        $time_res = $stmt_time->get_result()->fetch_assoc();
        
        // 將修改後的最新狀態回傳前端，完成無跳轉畫面無縫連動
        echo json_encode([
            'success' => true,
            'updated_time' => $time_res['created_at'],
            'has_image' => ($final_image_name !== null && $final_image_name !== ""),
            'new_image_url' => $final_image_name
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;

} elseif ($action === 'user_delete') {
    if (!checkCommentOwnership($conn, $com_id, $current_user_id)) {
        echo json_encode(['success' => false, 'message' => '安全錯誤：您無權刪除此筆評論！']);
        exit;
    }

    // 學生自刪評論時，同步將對應實體照片清除
    $stmt_img = $conn->prepare("SELECT com_img FROM comments WHERE com_id = ?");
    $stmt_img->bind_param("i", $com_id);
    $stmt_img->execute();
    $old_img = $stmt_img->get_result()->fetch_assoc();
    if ($old_img && !empty($old_img['com_img']) && file_exists('food/' . $old_img['com_img'])) {
        unlink('food/' . $old_img['com_img']);
    }

    $stmt = $conn->prepare("DELETE FROM comments WHERE com_id = ?");
    $stmt->bind_param("i", $com_id);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

echo json_encode(['success' => false, 'message' => '未知的操作指令']);
exit;
?>