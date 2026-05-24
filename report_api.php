<?php
// 1. 開啟 Session 以便取得目前登入的學生 ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. 強制宣告這一支 API 回傳的是標準 JSON 格式
header('Content-Type: application/json; charset=utf-8');

// 3. 引入你原本專案的資料庫連線檔案 (請確保檔名為 db.php)
require_once 'db.php'; 

try {
    // 4. 安全檢查：檢查使用者有沒有登入
    if (!isset($_SESSION['u_id'])) {
        echo json_encode(['success' => false, 'message' => '請先登入系統才能檢舉評論喔！']);
        exit;
    }

    $u_id = $_SESSION['u_id']; // 拿到目前登入者的 u_id
    $com_id = isset($_POST['com_id']) ? intval($_POST['com_id']) : 0;
    $reason_id = isset($_POST['reason_id']) ? intval($_POST['reason_id']) : 0;

    // 5. 防範傳送空值或無效參數
    if ($com_id <= 0 || $reason_id <= 0) {
        echo json_encode(['success' => false, 'message' => '無效的參數或檢舉原因！']);
        exit;
    }

    // 6. 防範重複檢舉（去符合你目前現狀的 userreports 資料表查）
    $stmt_check = $conn->prepare("SELECT report_id FROM userreports WHERE com_id = ? AND u_id = ?");
    $stmt_check->bind_param("ii", $com_id, $u_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => '您已經檢舉過這則評論囉，不需重複送出！']);
        exit;
    }
    $stmt_check->close();

    // 7. 🎯 執行寫入符合你現狀的 userreports 資料表 (欄位已精準校正為 reason)
    $sql = "INSERT INTO userreports (com_id, u_id, reason, created_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql);

    if ($stmt_insert) {
        $stmt_insert->bind_param("iii", $com_id, $u_id, $reason_id);
        
        if ($stmt_insert->execute()) {
            // 寫入成功，回傳 true 給前端
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '資料庫寫入失敗：' . $conn->error]);
        }
        $stmt_insert->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL 語法準備失敗：' . $conn->error]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '伺服器發生錯誤：' . $e->getMessage()]);
}

// 關閉資料庫連線
$conn->close();
?>