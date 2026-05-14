<?php
// 1. 設定回傳格式為 JSON
header('Content-Type: application/json');

// 2. 引入「保險箱」env.php
if (file_exists('env.php')) {
    include('env.php');
} else {
    echo json_encode(['reply' => '錯誤：找不到 env.php 檔案，請根據範本建立一個。']);
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

// 2. API 設定

$model = "gemini-2.5-flash"; 
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $apiKey;

// 3. 建立請求資料 (使用正式的 system_instruction 結構)
$data = [
    "system_instruction" => [
        "parts" => [
            ["text" => "你是一位輔仁大學校園美食專家。請簡短回覆學生的問題，口吻要親切有活力。如果學生問推薦餐點，請根據校園內的心園、理園、輔園餐廳來回答。"]
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
        "temperature" => 0.7,
        "maxOutputTokens" => 500
    ]
];

// 4. 使用 cURL 發送請求
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 5. 錯誤處理與結果解析
if ($httpCode !== 200) {
    echo json_encode([
        'reply' => "哎呀，連線失敗了 (錯誤碼 $httpCode)",
        'debug' => json_decode($response, true)
    ]);
    exit;
}

$result = json_decode($response, true);
$aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "我現在腦袋空空的，請再問一次！";

// 6. 回傳結果給前端
echo json_encode(['reply' => $aiReply]);