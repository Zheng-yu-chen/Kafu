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
            position: relative;
            overflow: hidden; /* 💡 確保外層容器不亂包 */
        }

        /* 聊天歷史紀錄區塊 */
        .chat-main-box {
            flex: 1;
            overflow-y: auto;
            padding: 20px 20px 40px 20px; /* 關鍵修正：增加底部 padding，確保對話滾動時不會被輸入框擋住 */
            display: flex;
            flex-direction: column;
            gap: 12px;
            /* 💡 關鍵修正：強迫內層盒子在 Flex 布局下最大高度不能超出剩餘空間，才能順利觸發滾動條 */
            max-height: calc(100% - 65px); 
        }

        /* 底部對話輸入欄區塊 */
        .chat-input-bar {
            padding: 12px 15px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            align-items: center;
            position: relative; 
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05); /* 加點微陰影，更有質感 */
            height: 65px; /* 💡 固定高度，方便內層精準計算滾動範圍 */
            box-sizing: border-box;
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

        /* 訊息泡泡樣式 */
        .bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            max-width: 80%;
            line-height: 1.5;
            word-break: break-all;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            white-space: pre-wrap; /* 保留 AI 回傳訊息中的所有斷行 */
            height: auto; /* 💡 確保高度跟著文字動態長高 */
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
        <div class="bubble ai">嗨！我是輔大美食小助手 🧑‍🍳，今天想在校園裡吃點什麼呢？<br><br>今天天氣真好，要不要去心園吃個香噴噴的鬆餅，或是去理園來份飽足的便當呢？<br>👉 <a href="index.php" style="color: #FF8C42; font-weight: bold; text-decoration: underline;">[點我查看校園所有店家]</a><br><br>你也可以直接問我：<br>• 「心園有什麼推薦的美味餐點？」<br>• 「幫我找 100 元以內的飽足下午餐」<br>• 「熱量低於 500 大卡的健康午餐有哪些？」</div>
    </div>

    <div class="chat-input-bar">
        <input type="text" id="chat-input" placeholder="對小助手說話..." class="chat-styled-input" autocomplete="off">
        <button onclick="sendMessage()" class="chat-send-btn">➤</button>
    </div>

</div>

<script>
// 💡 終極安全版：完全棄用脆弱的正則表達式，改用最穩定的字串安全替換
function formatAIResponse(text) {
    if (!text) return '';
    try {
        // 1. 如果文字包含 ** 粗體，直接用簡單的字串分割替換，安全無毒
        let cleanText = text;
        if (cleanText.includes('**')) {
            let parts = cleanText.split('**');
            for (let i = 1; i < parts.length; i += 2) {
                if (parts[i]) {
                    parts[i] = '<strong>' + parts[i] + '</strong>';
                }
            }
            cleanText = parts.join('');
        }
        
        // 2. 徹底根治 Bug：把容易造成前端混淆或排版壞掉的列表星號（*），全部替換成安全的圓點（•）
        cleanText = cleanText.split('\n').map(line => {
            if (line.trim().startsWith('*')) {
                return line.replace('*', '•');
            }
            return line;
        }).join('\n');
        
        return cleanText;
    } catch (err) {
        console.error("排版解析失敗，輸出原始文字:", err);
        return text; 
    }
}

async function sendMessage() {
    const chatInput = document.getElementById('chat-input');
    const chatBox = document.getElementById('chat-box');
    if (!chatInput || !chatBox) return;

    const message = chatInput.value.trim();
    if (!message) return;

    // 渲染使用者說的話
    appendMessage('user', message);
    
    // 💡 強制防禦：立刻清空輸入框，防止連續發送或文字殘留打結
    chatInput.value = '';
    chatInput.focus();

    // 生成一個唯一的讀取狀態 ID
    const loadingId = 'loading-' + Date.now();
    appendMessage('ai', '正在為您挑選校園美食...', loadingId);
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });

        // 💡 如果伺服器直接回傳非 200 狀態（例如 429 限制）
        if (!response.ok) {
            throw new Error(`HTTP 錯誤狀態碼: ${response.status}`);
        }

        const rawText = await response.text();
        try {
            const data = JSON.parse(rawText);
            const formattedReply = formatAIResponse(data.reply || "小助手開小差了，請稍後再試。");
            document.getElementById(loadingId).innerHTML = formattedReply; 
        } catch (jsonErr) {
            console.error("PHP 回傳內容異常:", rawText);
            document.getElementById(loadingId).innerText = "小助手思緒有點混亂，請稍後重試。";
        }
    } catch (e) {
        console.error("Fetch 錯誤:", e);
        // 💡 如果是 429 流量鎖，給予同學最親切的提示
        if (e.message.includes('429')) {
            document.getElementById(loadingId).innerText = "【連線冷卻中 429】學長姐被問得太熱烈啦！請稍等 1 分鐘再問一次喔 🥺";
        } else {
            document.getElementById(loadingId).innerText = "通訊失敗，請檢查網路連線或更新 API 金鑰 🥺";
        }
    }
    
    // 確保滾動到最底部
    chatBox.scrollTop = chatBox.scrollHeight;
}

function appendMessage(role, text, id = '') {
    const chatBox = document.getElementById('chat-box');
    const msgDiv = document.createElement('div');
    
    msgDiv.className = `bubble ${role}`;
    if (id) msgDiv.id = id;
    
    msgDiv.innerHTML = formatAIResponse(text); 
    chatBox.appendChild(msgDiv);
    
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