<?php
include('db.php');

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'random';
$prefs = isset($_GET['prefs']) ? explode(',', $_GET['prefs']) : [];

// 基礎 SQL 語法：確保餐點名稱、熱量、蛋白質等資料都被撈出
$sql = "SELECT i.item_id, i.name, i.calories, i.protein, i.is_vegetarian, r.name AS restaurant, r.r_id 
        FROM items i
        JOIN categories c ON i.c_id = c.c_id
        JOIN restaurants r ON c.r_id = r.r_id
        WHERE 1=1";

if ($mode === 'filter') {
    // 💡 低卡優先：熱量小於 500
    if (in_array('low_cal', $prefs)) {
        $sql .= " AND i.calories < 500 AND i.calories IS NOT NULL";
    }
    // 💡 高蛋白：蛋白質大於 20g
    if (in_array('high_pro', $prefs)) {
        $sql .= " AND i.protein > 20 AND i.protein IS NOT NULL";
    }
    // 💡 吃素：is_vegetarian = 1
    if (in_array('is_veg', $prefs)) {
        $sql .= " AND i.is_vegetarian = 1";
    }
}

// 打亂順序並只取 1 筆推薦
$sql .= " ORDER BY RAND() LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $item = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'r_id' => $item['r_id'],
        'name' => htmlspecialchars($item['name']),
        'restaurant' => htmlspecialchars($item['restaurant']),
        'calories' => $item['calories'],
        'protein' => $item['protein']
    ]);
} else {
    // 找不到符合這三個嚴苛條件的餐點
    echo json_encode(['success' => false]);
}
?>