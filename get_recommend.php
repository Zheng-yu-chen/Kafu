<?php
include('db.php');

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'random';
$prefs = isset($_GET['prefs']) ? explode(',', $_GET['prefs']) : [];
$max_cal = isset($_GET['max_cal']) ? intval($_GET['max_cal']) : 0;

// 基礎 SQL 語法：確保餐點名稱、熱量、蛋白質等資料都被撈出
$sql = "SELECT i.item_id, i.name, i.price, i.calories, i.protein, i.is_vegetarian, r.name AS restaurant, r.r_id 
        FROM items i
        JOIN categories c ON i.c_id = c.c_id
        JOIN restaurants r ON c.r_id = r.r_id
        WHERE 1=1";

if ($mode === 'filter' || $mode === 'remaining') {
    if (in_array('low_cal', $prefs)) {
        $sql .= " AND i.calories < 500 AND i.calories IS NOT NULL";
    }
    if (in_array('high_pro', $prefs)) {
        $sql .= " AND i.protein > 20 AND i.protein IS NOT NULL";
    }
    if (in_array('is_veg', $prefs)) {
        $sql .= " AND i.is_vegetarian = 1";
    }
}

if ($mode === 'remaining') {
    if ($max_cal > 0) {
        $sql .= " AND i.calories <= $max_cal AND i.calories IS NOT NULL";
    } else {
        $sql .= " AND i.calories IS NOT NULL";
    }
    $sql .= " ORDER BY i.calories DESC LIMIT 8";
} else {
    $sql .= " ORDER BY RAND() LIMIT 1";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    if ($mode === 'remaining') {
        $items = [];
        while ($item = $result->fetch_assoc()) {
            $items[] = [
                'item_id' => $item['item_id'],
                'name' => htmlspecialchars($item['name']),
                'price' => floatval($item['price']),
                'calories' => intval($item['calories']),
                'protein' => floatval($item['protein']),
                'restaurant' => htmlspecialchars($item['restaurant']),
                'r_id' => $item['r_id']
            ];
        }
        echo json_encode(['success' => true, 'items' => $items, 'max_cal' => $max_cal]);
        exit();
    }

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
    echo json_encode(['success' => false]);
}
?>