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
    <title>呷寶</title>
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
            padding: 20px;
            padding-bottom: 50px; /* 🌟 增加一點底部留白，避免最後一則訊息被加高的輸入框擋住 */
            display: flex;
            flex-direction: column;
            gap: 16px; /* 稍微拉大每一組對話之間的間距，視覺更舒服 */
        }

        /* 💡 新增：對話外層水平排列容器 */
        .chat-row {
            display: flex;
            align-items: flex-start;
            width: 100%;
        }
        
        /* 讓使用者的訊息靠右 */
        .chat-row.user-row {
            justify-content: flex-end;
        }

        /* 讓 AI 的訊息靠左，並給予頭貼和泡泡間距 */
        .chat-row.ai-row {
            justify-content: flex-start;
            gap: 10px;
        }

        /* 💡 新增：Chatbot 機器人頭貼樣式 */
        .bot-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;  /* 💡 核心防禦：防止被 flex 擠壓變形 */
            min-height: 40px; /* 💡 核心防禦：防止圖片載入前高度為 0 導致卡滾軸 */
            border-radius: 50%;
            background-color: #f0f0f0; 
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .bot-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* 底部對話輸入欄區塊 */
        .chat-input-bar {
            padding: 12px 15px 32px 15px; /* 底部增加 32px 的 Padding */
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            align-items: center;
            position: sticky;
            bottom: 58px; /* 讓它的白色背景完美貼齊底部導覽列 */
            z-index: 1000;
            box-sizing: border-box; /* 💡 確保 padding 不會撐開總寬度 */
            width: 100%;            /* 💡 預設直接填滿父容器 */
        }

        /* 手機版 RWD 微調 */
        @media (max-width: 420px) {
            .chat-input-bar {
                bottom: 90px; /* 依據你的設計，手機版往上推調整高度 */
                padding: 12px 12px 24px 12px; /* 稍微縮小手機版的內距，讓輸入框更有空間 */
            }
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
            max-width: 75%; /* 💡 稍微縮小最大寬度到 75%，幫左側頭貼留點舒適空間 */
            min-width: 0;   /* 💡 核心防禦：防止 Flex 容器內的中文字串無預警壞掉折行 */
            line-height: 1.5;
            word-break: break-word; /* 💡 改用 break-word，中英文清單才不會在奇怪的字元被切斷 */
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            white-space: pre-wrap; /* 保留 AI 回傳訊息中的所有斷行 */
            height: auto; /* 💡 確保高度跟著文字動態長高 */
        }
        
        .bubble.user {
            background: var(--fujen-blue, #002B5B);
            color: white;
            border-bottom-right-radius: 2px;
        }

        .bubble.ai {
            background: white;
            color: #333;
            border-bottom-left-radius: 2px;
            border: 1px solid #edeededf;
        }
    </style>
</head>
<body>

<div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
    <h1 style="margin: 0; font-size: 22px;">呷寶</h1>
</div>

<div class="chat-page-container">
    
    <div id="chat-box" class="chat-main-box">
        <div class="chat-row ai-row">
            <div class="bot-avatar">
                <img src="images/pokemon.jpg" alt="呷寶" 
                     onload="document.getElementById('chat-box').scrollTop = document.getElementById('chat-box').scrollHeight;"
                     onerror="this.style.display='none'; this.parentNode.innerText='🤖'">
            </div>
            <div class="bubble ai">嗨！我是呷寶，今天想在校園裡吃點什麼呢？<br><br>今天天氣真好，要不要去心園吃個香噴噴的鬆餅，或是去理園來份飽足的便當呢？<br>👉 <a href="index.php" style="color: #FF8C42; font-weight: bold; text-decoration: underline;">[點我查看校園所有店家]</a><br><br>你也可以直接問我：<br>• 「心園有什麼推薦的美味餐點？」<br>• 「幫我找 100 元以內的飽足下午餐」<br>• 「熱量低於 500 大卡的健康午餐有哪些？」</div>
        </div>
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

// 💡 核心修改：動態生成外層水平排列的 .chat-row，並在動態生成圖片時也加上雙重置底機制
function appendMessage(role, text, id = '') {
    const chatBox = document.getElementById('chat-box');
    
    // 1. 建立外層 row 容器
    const rowDiv = document.createElement('div');
    rowDiv.className = `chat-row ${role}-row`;
    
    // 2. 如果是 AI，先在外層塞入頭貼
    if (role === 'ai') {
        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'bot-avatar';
        avatarDiv.innerHTML = `<img src="images/pokemon.jpg" alt="呷寶" 
            onload="document.getElementById('chat-box').scrollTop = document.getElementById('chat-box').scrollHeight;"
            onerror="this.style.display='none'; this.parentNode.innerText='🤖'">`;
        rowDiv.appendChild(avatarDiv);
    }
    
    // 3. 建立原本的訊息泡泡
    const msgDiv = document.createElement('div');
    msgDiv.className = `bubble ${role}`;
    if (id) msgDiv.id = id;
    
    msgDiv.innerHTML = formatAIResponse(text); 
    
    // 4. 將泡泡塞入外層容器，再把外層容器塞入 chatBox
    rowDiv.appendChild(msgDiv);
    chatBox.appendChild(rowDiv);
    
    // 5. 塞入內容當下立馬先捲動一次
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
    
    // 💡 網頁 DOM 樹初始化完畢時，再強制做一次物理置底捲動
    const chatBox = document.getElementById('chat-box');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
});
</script>

<?php include('footer.php'); ?>
</body>
</html>