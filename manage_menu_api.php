<?php
session_start();
include('db.php');

// 權限檢查：只有管理員 (role_id 1) 可以訪問
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => '無權限訪問！']);
    exit();
}

// 開啟錯誤回報，方便除錯
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. 更新餐點資料 (含熱量、蛋白質、脂肪、碳水)
    // ==========================================
    if ($action === 'update') {
        $item_id  = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $name     = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price    = isset($_POST['price']) ? intval($_POST['price']) : 0;
        $calories = isset($_POST['calories']) ? intval($_POST['calories']) : 0;
        $protein  = isset($_POST['protein']) ? floatval($_POST['protein']) : 0.0;
        // 💡 接收前端傳來的 fat 與 carbs
        $fat      = isset($_POST['fat']) ? floatval($_POST['fat']) : 0.0;
        $carbs    = isset($_POST['carbs']) ? floatval($_POST['carbs']) : 0.0;

        if ($item_id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => '無效的參數或餐點名稱不能為空']);
            exit();
        }

        try {
            // 💡 這裡對應你資料庫的實際欄位名稱：protein, fat, carbohydrates
            $sql = "UPDATE items SET name = ?, price = ?, calories = ?, protein = ?, fat = ?, carbohydrates = ? WHERE item_id = ?";
            $stmt = $conn->prepare($sql);
            
            // 參數型態：s=字串, i=整數, d=浮點數
            // 欄位順序：name(s), price(i), calories(i), protein(d), fat(d), carbohydrates(d), item_id(i) -> "siidddi"
            $stmt->bind_param("siidddi", $name, $price, $calories, $protein, $fat, $carbs, $item_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => '餐點更新成功！']);
            } else {
                echo json_encode(['success' => false, 'message' => '資料更新失敗']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
        }
        exit();
    }

    // ==========================================
    // 2. 刪除餐點資料
    // ==========================================
    if ($action === 'delete') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if ($item_id <= 0) {
            echo json_encode(['success' => false, 'message' => '無效的餐點 ID']);
            exit();
        }

        try {
            $sql = "DELETE FROM items WHERE item_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => '餐點已成功刪除！']);
            } else {
                echo json_encode(['success' => false, 'message' => '刪除失敗']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
        }
        exit();
    }

    echo json_encode(['success' => false, 'message' => '未知的操作行為']);
    exit();
}

echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
exit();