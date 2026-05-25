<?php
// 1. 開啟 Session 以便取得目前登入的學生 ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php'; 

try {

    if (!isset($_SESSION['u_id'])) {
        echo json_encode(['success' => false, 'message' => '請先登入系統才能檢舉評論喔！']);
        exit;
    }

    $u_id = $_SESSION['u_id'];
    $com_id = isset($_POST['com_id']) ? intval($_POST['com_id']) : 0;
    $reason_id = isset($_POST['reason_id']) ? intval($_POST['reason_id']) : 0;

    $other_text = isset($_POST['other_text']) ? trim($_POST['other_text']) : null; 

    if ($com_id <= 0 || $reason_id <= 0) {
        echo json_encode(['success' => false, 'message' => '無效的參數或檢舉原因！']);
        exit;
    }
    $stmt_check = $conn->prepare("SELECT usreport_id FROM userreports WHERE com_id = ? AND u_id = ?");
    $stmt_check->bind_param("ii", $com_id, $u_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => '您已經檢舉過這則評論囉，不需重複送出！']);
        exit;
    }
    $stmt_check->close();

    $sql = "INSERT INTO userreports (com_id, u_id, reason, other_reason_text, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql);

    if ($stmt_insert) {
        $stmt_insert->bind_param("iiis", $com_id, $u_id, $reason_id, $other_text);
        
        if ($stmt_insert->execute()) {
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