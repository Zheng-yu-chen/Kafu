<?php
// 1. 設定回傳格式為 JSON
header('Content-Type: application/json');

// 2. 引入「保險箱」env.php 與「資料庫設定」db.php
if (file_exists('env.php')) {
    include('env.php');
} else {
    echo json_encode(['reply' => '錯誤：找不到 env.php 檔案。']);
    exit;
}

// 關鍵串接：引入資料庫連線設定檔
if (file_exists('db.php')) {
    include('db.php');
} else {
    echo json_encode(['reply' => '錯誤：找不到 db.php 檔案。']);
    exit;
}

// 3. 取得 API Key (從 env.php 來的變數)
$apiKey = $GLOBALS['GEMINI_API_KEY'] ?? ''; 

if (empty($apiKey)) {
    echo json_encode(['reply' => '錯誤：env.php 裡面沒有金鑰喔！']);
    exit;
}

// 4. 接收前端訊息
$input = json_decode(file_get_contents('php://input'), true);
$userMsg = $input['message'] ?? '';

if (empty($userMsg)) {
    echo json_encode(['reply' => '同學你沒說話呀...想吃什麼？']);
    exit;
}

// ==========================================
// 核心改動：只撈取相關學餐的餐點，避免字數爆表導致 429
// ==========================================
$dbDataText = "";

try {
    // 預設不過濾（如果使用者沒提到特定學餐）
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // 智慧偵測使用者問的是哪一個學餐，讓資料庫先做第一線篩選
    if (mb_strpos($userMsg, '心園') !== false) {
        $whereClause = "WHERE r.location LIKE :loc";
        $params['loc'] = '%心園%';
    } elseif (mb_strpos($userMsg, '理園') !== false) {
        $whereClause = "WHERE r.location LIKE :loc";
        $params['loc'] = '%理園%';
    } elseif (mb_strpos($userMsg, '輔園') !== false) {
        $whereClause = "WHERE r.location LIKE :loc";
        $params['loc'] = '%輔園%';
    }

    // 正統三表聯合查詢：items -> categories -> restaurants
    $sql = "SELECT r.location AS canteen_location, r.name AS restaurant_name, i.name AS item_name, i.price, i.calories 
            FROM items i
            JOIN categories c ON i.c_id = c.c_id
            JOIN restaurants r ON c.r_id = r.r_id
            $whereClause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $meals = $stmt->fetchAll();

    // 防呆機制：如果篩選後剛好沒資料（例如問了別的關鍵字），就放寬撈取前 30 筆，避免小抄流空
    if (empty($meals)) {
        $sql_fallback = "SELECT r.location AS canteen_location, r.name AS restaurant_name, i.name AS item_name, i.price, i.calories 
                         FROM items i
                         JOIN categories c ON i.c_id = c.c_id
                         JOIN restaurants r ON c.r_id = r.r_id
                         LIMIT 30";
        $stmt_fallback = $pdo->prepare($sql_fallback);
        $stmt_fallback->execute();
        $meals = $stmt_fallback->fetchAll();
    }

    // 將資料庫精簡篩選後的內容，組裝成給 Gemini 看的「真實知識庫小抄」
    $dbDataText = "【目前系統資料庫內登記的真實學餐與餐點清單】:\n";
    foreach ($meals as $row) {
        $dbDataText .= "- 位置: " . $row['canteen_location'] . " / 店家: " . $row['restaurant_name'] . " / 餐點: " . $row['item_name'] . " / 價格: " . (int)$row['price'] . "元 / 熱量: " . $row['calories'] . "大卡\n";
    }

} catch (PDOException $e) {
    echo json_encode([
        'reply' => '資料庫讀取失敗：' . $e->getMessage()
    ]);
    exit;
}

// ==========================================
// 提示詞設定：純文字親切回覆，完全杜絕 HTML 超連結與失控幻覺
// ==========================================
$systemKnowledge = "你是「輔仁大學校園美食與健康營養專家」。你必須「完全依據」以下提供的【真實資料庫清單】來回答學生的問題，口吻要親切、有活力、像熱心的學長姐一樣。

" . $dbDataText . "

【嚴格回答規則】
1. 餐廳位置（如：心園、理園、輔園）、店家與食物的組合必須完全符合清單。例如：如果學生問「理園有沒有自助餐」，你比對清單後發現「心園自助餐」是在心園，你必須「立刻糾正他」，並引導他去正確的餐廳位置（例如：『理園沒有自助餐喔！輔大只有心園有自助餐，快去心園看看吧！』）。
2. 如果學生有提到預算限制（例如：100元以內）或熱量限制（例如：低於500大卡），請幫他比對清單中的價格與熱量，篩選出符合條件的餐點推薦給他。
3. 如果整份清單都找不到符合學生想要的食物、預算或熱量條件，請禮貌告知資料庫目前沒有符合的資料。
4. 回答請保持親切、精煉，多用校園口吻，直接輸出純文字介紹，不要包含任何 HTML 超連結語法。";


// 5. 設定 Gemini API 參數 (採用最穩定的單次發送格式)
$model = "gemini-2.5-flash"; 
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $apiKey;

$data = [
    "system_instruction" => [
        "parts" => [
            ["text" => $systemKnowledge] // 帶入智慧過濾防幻覺小抄
        ]
    ],
    "contents" => [
        [
            "role" => "user", 
            "parts" => [
                ["text" => $userMsg]
            ]
        ]
    ], 
    "generationConfig" => [
        "temperature" => 0.4, // 適中溫度，兼顧多樣性與精準度
        "maxOutputTokens" => 800 // 放寬字數上限，確保熱量大卡完整吐完
    ]
];

// 6. 發送請求 (cURL)
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);


// 7. 解析並回傳結果
if ($httpCode !== 200) {
    echo json_encode(['reply' => "連線失敗 ($httpCode)"]);
} else {
    $result = json_decode($response, true);
    $aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "解析失敗";
    echo json_encode(['reply' => $aiReply]);
}
?>