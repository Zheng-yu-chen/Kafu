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
// 核心修正：改用精準 r_id 隨機抽樣，徹底解放資料庫與 429 流量鎖
// ==========================================
$dbDataText = "";

try {
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // 💡 智慧分流：直接指定各校園學餐的 r_id 範圍，完全跳過高耗能的模糊查詢
    if (mb_strpos($userMsg, '理園') !== false) {
        // 理園包含：4.辛蔬料理, 5.澳門華記, 6.娃子早餐店, 7.覓朵朵, 8.豪客來, 10.熊賀炒飯
        // 使用 ORDER BY RAND() LIMIT 12 隨機抽樣 12 筆招牌菜，字數降到極低，徹底根治 429
        $whereClause = "WHERE r.r_id IN (4, 5, 6, 7, 8, 10) ORDER BY RAND() LIMIT 12";
    } elseif (mb_strpos($userMsg, '輔園') !== false) {
        // 輔園包含：11.食福, 12.深川味, 13.埃及教父, 14.八方雲集, 15.奇奇, 16.新羅, 17.雲瀚, 18.新東家, 19.瑪納
        $whereClause = "WHERE r.r_id IN (11, 12, 13, 14, 15, 16, 17, 18, 19) ORDER BY RAND() LIMIT 12";
    } elseif (mb_strpos($userMsg, '心園') !== false) {
        // 心園包含：1.巧瑋鬆餅, 2.心園麵店, 3.心園自助餐
        $whereClause = "WHERE r.r_id IN (1, 2, 3) ORDER BY RAND() LIMIT 12";
    } else {
        // 如果使用者沒說位置，隨機抓 12 筆墊檔
        $whereClause = "ORDER BY RAND() LIMIT 12";
    }

    // 正統三表聯合查詢：利用 categories 作為橋樑串接 items 與 restaurants
    $sql = "SELECT r.location AS canteen_location, r.name AS restaurant_name, i.name AS item_name, i.price, i.calories 
            FROM items i
            JOIN categories c ON i.c_id = c.c_id
            JOIN restaurants r ON c.r_id = r.r_id
            $whereClause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $meals = $stmt->fetchAll();

    // 組裝精簡、安全的「知識庫小抄」
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

//推送用