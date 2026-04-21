<?php
include('db.php');
header('Content-Type: application/json');

$mode = $_GET['mode'] ?? 'random';
$prefs = isset($_GET['prefs']) ? explode(',', $_GET['prefs']) : [];

// 基礎 SQL 串聯
$sql = "SELECT i.*, r.name as res_name, r.r_id 
        FROM items i 
        JOIN categories c ON i.c_id = c.c_id 
        JOIN restaurants r ON c.r_id = r.r_id 
        WHERE 1=1";

if ($mode === 'filter') {
    if (in_array('low_cal', $prefs)) $sql .= " AND i.calories < 500";
    if (in_array('high_pro', $prefs)) $sql .= " AND i.protein > 20";
    
    // 💡 關鍵修正：將 is_veg 改為 i.is_vegetarian
    if (in_array('is_veg', $prefs)) $sql .= " AND i.is_vegetarian = 1"; 
}

$sql .= " ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'item_id' => $row['item_id'],
        'r_id' => $row['r_id'], // 💡 務必新增這一行，前端才拿得到餐廳 ID
        'name' => $row['name'],
        'restaurant' => $row['res_name'],
        'calories' => $row['calories']
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>