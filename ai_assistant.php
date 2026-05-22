<?php
session_start();
include('db.php');
include('header.php');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>輔大美食 AI 助手</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 整頁式聊天室專用佈局 */
        .chat-page-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px); /* 扣除頂部 header 與底部 footer 的高度 */
            background-color: #f8f9fa;
        }

        /* 聊天歷史紀錄區塊 */
        .chat-main-box {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* 底部對話輸入欄區塊 */
        .chat-input-bar {
            padding: 12px 15px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            align-items: center;
            position: sticky;
            bottom: 65px; /* 剛好黏在底部導覽列上方 */
        }

        .chat-styled-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 24px;
            padding: 10px 18px;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
            height: 40px;
            box-sizing: border-box;
        }
        .chat-styled-input:focus {
            border-color: var(--fujen-blue, #002B5B);
        }

        .chat-send-btn {
            background: var(--primary-orange, #FF8C42);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 6px rgba(255, 140, 66, 0.3);
            transition: transform 0.1s;
            flex-shrink: 0;
        }
        .chat-send-btn:active {
            transform: scale(0.95);
        }

        /* 訊息泡泡樣式升級 */
        .bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            max-width: 80%;
            line-height: 1.5;
            word-break: break-all;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        
        .bubble.user {
            align-self: flex-end;
            background: var(--fujen-blue, #002B5B);
            color: white;
            border-bottom-right-radius: 2px;
        }

        .bubble.ai {
            align-self: flex-start;
            background: white;
            color: #333;
            border-bottom-left-radius: 2px;
            border: 1px solid #edeededf;
        }
    </style>
</head>
<body>

<div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
    <h1 style="margin: 0; font-size: 22px;">輔大美食 AI 助手</h1>
</div>

<div class="chat-page-container">
    
    <div id="chat-box" class="chat-main-box">
        <div class="bubble ai">
            嗨！我是輔大美食小助手 🧑‍🍳，今天想在校園裡吃點什麼呢？<br><br>
            你可以試著問我：<br>
            • 「心園有什麼推薦的美味餐點？」<br>
            • 「幫我找 100 元以內的飽足下午餐」<br>
            • 「熱量低於 500 大卡的健康午餐有哪些？」
        </div>
    </div>

    <div class="chat-input-bar">
        <input type="text" id="chat-input" placeholder="對小助手說話..." class="chat-styled-input" autocomplete="off">
        <button onclick="sendMessage()" class="chat-send-btn">➤</button>
    </div>

</div>

<script>
async function sendMessage() {
    const chatInput = document.getElementById('chat-input');
    const chatBox = document.getElementById('chat-box');
    if (!chatInput || !chatBox) return;

    const message = chatInput.value.trim();
    if (!message) return;

    // 渲染使用者說的話
    appendMessage('user', message);
    chatInput.value = '';

    // 生成一個唯一的讀取狀態 ID
    const loadingId = 'loading-' + Date.now();
    appendMessage('ai', '正在為您挑選校園美食...', loadingId);

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });

        const rawText = await response.text();
        try {
            const data = JSON.parse(rawText);
            document.getElementById(loadingId).innerText = data.reply || "小助手開小差了，請稍後再試。";
        } catch (jsonErr) {
            console.error("PHP 回傳內容異常:", rawText);
            document.getElementById(loadingId).innerText = "小助手思緒有點混亂，請檢查後端設定。";
        }
    } catch (e) {
        console.error("Fetch 錯誤:", e);
        document.getElementById(loadingId).innerText = "通訊失敗，請檢查網路連線 🥺";
    }
}

function appendMessage(role, text, id = '') {
    const chatBox = document.getElementById('chat-box');
    const msgDiv = document.createElement('div');
    
    msgDiv.className = `bubble ${role}`;
    if (id) msgDiv.id = id;
    
    msgDiv.innerText = text;
    chatBox.appendChild(msgDiv);
    
    // 自動捲動到最新對話訊息
    chatBox.scrollTop = chatBox.scrollHeight;
}

// 監聽 Enter 鍵快捷送出
document.addEventListener("DOMContentLoaded", function() {
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    }
});
</script>

<?php include('footer.php'); ?>
</body>
</html>