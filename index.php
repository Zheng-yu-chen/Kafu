<?php
session_start();
include('db.php');
include('header.php');

$canRecommendRemaining = true;
if (isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1, 2])) {
    $canRecommendRemaining = false;
}

// 1. 取得一般篩選與搜尋參數
$filter = isset($_GET['filter']) ? $_GET['filter'] : '全部';
$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($conn, $_GET['search'])) : '';

// 2. 取得進階篩選參數
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : 300; 
$cal_max = isset($_GET['cal_max']) ? intval($_GET['cal_max']) : 2000;
$is_veg = isset($_GET['is_veg']) ? 1 : 0;
$low_cal = isset($_GET['low_cal']) ? 1 : 0;
$high_pro = isset($_GET['high_pro']) ? 1 : 0;

// 3.  判斷是否為「進階搜尋模式」
$is_advanced_search = (!empty($search) || $price_max < 300 || $cal_max < 2000 || $is_veg || $low_cal || $high_pro);

if ($is_advanced_search) {
    // 模式 A：進階搜尋，顯示符合條件的「餐點」
    $sql = "SELECT i.item_id, i.name AS item_name, i.price, i.calories, i.protein, i.fat, i.carbs, i.is_vegetarian, 
                   r.name AS res_name, r.r_id, r.location, r.image_url 
            FROM items i
            JOIN categories c ON i.c_id = c.c_id
            JOIN restaurants r ON c.r_id = r.r_id
            WHERE 1=1";
            
    // 關鍵字搜尋 
    if (!empty($search)) {
        $sql .= " AND (i.name LIKE '%$search%' OR r.name LIKE '%$search%')";
    }
    
    // 價格拉桿篩選
    $sql .= " AND i.price <= $price_max";
    
    // 熱量上限篩選
    $sql .= " AND i.calories <= $cal_max AND i.calories IS NOT NULL";
    
    // 營養標籤篩選
    if ($is_veg) $sql .= " AND i.is_vegetarian = 1";
    if ($low_cal) $sql .= " AND i.calories < 500 AND i.calories IS NOT NULL";
    if ($high_pro) $sql .= " AND i.protein > 20 AND i.protein IS NOT NULL";
    
    // 地區篩選
    if ($filter !== '全部') {
        $filter_safe = mysqli_real_escape_string($conn, $filter);
        $sql .= " AND r.location = '$filter_safe'";
    }
    
    $sql .= " ORDER BY i.price ASC"; 
    
} else {
    // 模式 B：預設模式，顯示整間「餐廳」
    $sql = "SELECT DISTINCT r.* FROM restaurants r
            LEFT JOIN categories c ON r.r_id = c.r_id
            LEFT JOIN items i ON c.c_id = i.c_id
            WHERE 1=1";

    if ($filter !== '全部') {
        $filter_safe = mysqli_real_escape_string($conn, $filter);
        $sql .= " AND r.location = '$filter_safe'";
    }
}

$result = $conn->query($sql);

$remaining_cal = null;
$remaining_cal_text = "";
if (isset($_SESSION['u_id']) && $canRecommendRemaining) {
    $u_id = $_SESSION['u_id'];
    $goal_cal = 2000;
    $goal_res = $conn->query("SELECT goal_cal FROM accounts WHERE u_id = $u_id");
    if ($goal_res && $row = $goal_res->fetch_assoc()) {
        $goal_cal = $row['goal_cal'] ?: 2000;
    }

    $today = date('Y-m-d');
    $cal_res = $conn->query("SELECT SUM(COALESCE(i.calories, l.total_calories, 0)) AS total_cal
                             FROM consumptionlogs l
                             LEFT JOIN items i ON l.item_id = i.item_id
                             WHERE l.u_id = $u_id AND DATE(l.recorded_at) = '$today'");
    $consumed_cal = 0;
    if ($cal_res && $cal_row = $cal_res->fetch_assoc()) {
        $consumed_cal = (int)$cal_row['total_cal'];
    }

    $remaining_cal = max(0, $goal_cal - $consumed_cal);
    $remaining_cal_text = "今日剩餘熱量：{$remaining_cal} kcal";
} elseif (isset($_SESSION['u_id']) && !$canRecommendRemaining) {
    $remaining_cal_text = "";
}
?>

<style>
    /* 基礎樣式 */
    .top-banner { background-color: var(--fujen-blue, #002B5B); color: white; padding: 12px 20px 55px; text-align: center; }
    .header-container { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%; margin-bottom: 10px; }
    .logo-img { height: 70px; width: auto; }
    .hero-title { font-size: 28px; font-weight: bold; grid-column: 2; margin: 0; }
    
    /* 搜尋與進階篩選區塊 */
    .search-wrapper { max-width: 320px; margin: 0 auto; position: relative; }
    .search-input-group { display: flex; gap: 8px; align-items: stretch; }
    
    .search-input-wrapper { flex: 1; position: relative; display: flex; }
    .search-extra { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .remaining-calorie { color: white; font-size: 14px; opacity: 0.95; }
    .recommend-btn { background: #FFECB3; color: #663C00; border: none; border-radius: 20px; padding: 8px 14px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
    .recommend-btn:disabled { opacity: 0.55; cursor: not-allowed; }
    .recommend-panel { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); align-items: center; justify-content: center; padding: 20px; }
    .recommend-card { width: 100%; max-width: 380px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 16px 40px rgba(0,0,0,0.2); }
    .recommend-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; background: #002B5B; color: white; }
    .recommend-title { font-size: 15px; font-weight: 700; }
    .recommend-close { border: none; background: transparent; color: white; font-size: 20px; cursor: pointer; line-height: 1; }
    .recommend-body { max-height: 320px; overflow-y: auto; padding: 14px 18px 18px; }
    .recommend-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .recommend-item:last-child { border-bottom: none; }
    .recommend-item-name { font-size: 15px; font-weight: 700; color: #002B5B; margin: 0; }
    .recommend-item-meta { font-size: 12px; color: #666; margin: 4px 0 0; }
    .recommend-empty { color: #555; font-size: 13px; line-height: 1.6; }
    .recommend-actions-row { display: flex; justify-content: flex-end; padding: 0 18px 18px; }
    .recommend-close-bottom { border: none; background: #f0f0f0; color: #333; border-radius: 12px; padding: 10px 16px; cursor: pointer; font-weight: bold; }
    .search-input { 
        width: 100%; padding: 10px 40px 10px 15px; 
        border-radius: 20px; border: none; outline: none; 
        background: rgba(255, 255, 255, 0.95); text-align: left; font-size: 14px; box-sizing: border-box;
    }
    
    .search-icon-btn {
        position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; padding: 5px;
        display: flex; align-items: center; justify-content: center;
    }
    .search-icon-btn img {
        width: 18px; height: 18px; object-fit: contain; opacity: 0.6; transition: 0.2s;
    }
    .search-icon-btn:active img { opacity: 1; transform: scale(0.9); }

    .adv-search-btn { background: var(--primary-orange, #FF8C42); color: white; border: none; border-radius: 20px; padding: 0 15px; cursor: pointer; font-weight: bold; font-size: 13px;}
    
    /* 展開的進階面板 */
    .adv-panel { display: none; background: white; color: #333; padding: 15px; border-radius: 12px; margin-top: 10px; text-align: left; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .adv-panel.active { display: block; }
    
    /* 拉桿樣式 */
    .range-group { margin-bottom: 15px; }
    .range-header { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin-bottom: 8px; color: var(--fujen-blue, #002B5B); }
    input[type=range] { width: 100%; accent-color: var(--primary-orange, #FF8C42); }
    
    /* 標籤按鈕樣式 */
    .tag-group { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
    .filter-tag-checkbox { display: none; }
    .filter-tag-label { background: #f0f0f0; color: #555; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer; border: 1px solid transparent; transition: 0.2s; }
    .filter-tag-checkbox:checked + .filter-tag-label { background: #FFF3EB; color: var(--primary-orange, #FF8C42); border-color: var(--primary-orange, #FF8C42); font-weight: bold; }
    
    .submit-adv-btn { width: 100%; background: var(--fujen-blue, #002B5B); color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-bottom: 8px; }

    /* 地區過濾按鈕 */
    .filter-container { display: flex; justify-content: center; gap: 10px; margin-top: -25px; padding: 0 20px; position: relative; z-index: 10; }
    .filter-btn { background: white; color: var(--fujen-blue, #002B5B); padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); font-weight: bold; }
    .filter-btn.active { background: var(--fujen-blue, #002B5B); color: white; }
    
    /* 列表區域 */
    .restaurant-list { padding: 25px 20px; padding-bottom: 100px; }
    .res-card { background: white; border-radius: 15px; padding: 15px; display: flex; align-items: center; text-decoration: none; color: inherit; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    
    /* 圖片外框與內層設定 */
    .res-img { width: 60px; height: 60px; background: #FFF3EB; border-radius: 12px; margin-right: 15px; display: flex; justify-content: center; align-items: center; overflow: hidden; flex-shrink: 0; font-size: 26px; }
    .res-actual-img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
    
    /* 餐點卡片專用文字設定 */
    .item-card-info { flex: 1; display: flex; justify-content: space-between; align-items: center; width: 100%; }
    .item-text-content { flex: 1; }
    .item-name { font-size: 16px; font-weight: bold; color: var(--fujen-blue, #002B5B); margin: 0 0 4px; }
    
    .dest-icon {
        width: 16px;
        height: 16px;
        object-fit: contain;
        vertical-align: middle; /* 💡 改成 middle 讓它垂直置中對齊文字 */
        margin-right: 4px;
        margin-bottom: 3px; /* 💡 稍微加上一點底邊距，把圖示往上推 */
        opacity: 0.9;
    }

    .item-meta { font-size: 12px; color: #888; margin: 0 0 6px; }
    
    /* 排版更新：價格、熱量與三大營養素 */
    .item-nutrition-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .item-price { font-weight: bold; color: #E53935; font-size: 14px; }
    
    /* 小火焰圖片 CSS */
    .fire-icon { width: 12px; height: 12px; object-fit: contain; vertical-align: middle; margin-right: 2px; margin-bottom: 2px;}
    
    /* 統一換成橘色粗體字 */
    .item-macros { display: flex; gap: 6px; font-size: 12px; font-weight: bold; color: var(--primary-orange, #FF8C42); border-left: 1px solid #ddd; padding-left: 8px; }

    .btn-go-text { color: #ccc; font-size: 12px; white-space: nowrap; margin-left: 10px; }

    /* --- 小助理隱形外框與按鈕 --- */
    .ai-fixed-wrapper {
        position: fixed !important; 
        bottom: 0; left: 50%; transform: translateX(-50%); 
        width: 100%; max-width: 414px; height: 0; 
        z-index: 9999; pointer-events: none; 
    }
    #ai-assistant-fab {
        position: absolute !important; bottom: 70px; right: 10px; 
        width: 60px; height: 60px;
        background: var(--primary-orange, #FF8C42); border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); pointer-events: auto; overflow: hidden; 
    }
    #ai-assistant-fab img { width: 36px; height: 36px; object-fit: contain; }

    .assistant-card {
        position: absolute !important; bottom: 170px; right: 25px;
        width: 280px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 1000; overflow: hidden; display: none; pointer-events: auto; 
    }
    .assistant-header { background: var(--fujen-blue, #002B5B); color: white; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; }
    .assistant-body { padding: 18px; font-size: 14px; }
    .recommend-actions { display: flex; gap: 10px; margin: 15px 0; }
    .btn-action { flex: 1; padding: 10px; border: 1.5px solid var(--primary-orange, #FF8C42); background: white; color: var(--primary-orange, #FF8C42); border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 13px; }
    .filter-options { background: #f9f9f9; padding: 12px; border-radius: 12px; }
    .checkbox-item { display: block; margin-bottom: 8px; font-size: 13px; cursor: pointer; }
    .btn-submit { width: 100%; background: var(--fujen-blue, #002B5B); color: white; border: none; padding: 10px; border-radius: 10px; margin-top: 10px; font-weight: bold; cursor: pointer; }
    #recommend-result { margin-top: 15px; padding: 12px; background: #FFF3EB; border-radius: 12px; border-left: 4px solid var(--primary-orange, #FF8C42); }
    .result-name { font-weight: bold; color: var(--fujen-blue, #002B5B); display: block; }
    .btn-go { display: inline-block; margin-top: 8px; color: var(--primary-orange, #FF8C42); text-decoration: none; font-weight: bold; }
</style>

<div class="top-banner">
    <div class="header-container">
        <a href="index.php"><img src="logo.png" alt="Logo" class="logo-img"></a>
        <h1 class="hero-title">輔大美食探索</h1>
    </div>
    
    <div class="search-wrapper">
        <form action="index.php" method="GET" id="searchForm">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            
            <div class="search-input-group">
                <div class="search-input-wrapper">
                    <input type="text" name="search" class="search-input" placeholder="今天想吃什麼？" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-icon-btn" title="搜尋">
                        <img src="icon/search_icon.png" alt="搜尋">
                    </button>
                </div>
                
                <button type="button" class="adv-search-btn" onclick="toggleAdvPanel()">進階篩選</button>
            </div>

            <?php if ($canRecommendRemaining && isset($_SESSION['u_id'])): ?>
            <div class="search-extra">
                <div class="remaining-calorie"><?php echo $remaining_cal_text; ?></div>
                <button type="button" class="recommend-btn" onclick="fetchRecommendRemaining(<?php echo $remaining_cal ?? 0; ?>)">查看推薦餐點</button>
            </div>
            <?php endif; ?>

            <div class="adv-panel" id="advPanel">
                <div class="range-group">
                    <div class="range-header">
                        <span>預算上限</span>
                        <span id="priceVal">$<?php echo $price_max; ?></span>
                    </div>
                    <input type="range" name="price_max" min="0" max="300" step="10" value="<?php echo $price_max; ?>" oninput="document.getElementById('priceVal').innerText = '$' + this.value">
                </div>
                <div class="range-group">
                    <div class="range-header">
                        <span>熱量上限</span>
                        <span id="calVal"><?php echo $cal_max; ?> kcal</span>
                    </div>
                    <input type="range" name="cal_max" min="200" max="2000" step="50" value="<?php echo $cal_max; ?>" oninput="document.getElementById('calVal').innerText = this.value + ' kcal'">
                </div>
                
                <div class="tag-group">
                    <label>
                        <input type="checkbox" name="low_cal" class="filter-tag-checkbox" <?php if($low_cal) echo 'checked'; ?>>
                        <span class="filter-tag-label">低卡 (&lt; 500k)</span>
                    </label>
                    <label>
                        <input type="checkbox" name="high_pro" class="filter-tag-checkbox" <?php if($high_pro) echo 'checked'; ?>>
                        <span class="filter-tag-label">高蛋白 (&gt; 20g)</span>
                    </label>
                    <label>
                        <input type="checkbox" name="is_veg" class="filter-tag-checkbox" <?php if($is_veg) echo 'checked'; ?>>
                        <span class="filter-tag-label">素食</span>
                    </label>
                </div>
                
                <button type="submit" class="submit-adv-btn">套用篩選</button>
                <div style="text-align:center;">
                    <a href="index.php?filter=<?php echo urlencode($filter); ?>" style="font-size:12px; color:#999; text-decoration:none;">✕ 清除所有條件</a>
                </div>
            </div>

            <div id="remainingRecommendPanel" class="recommend-panel" onclick="closeRecommendPanel(event)">
                <div class="recommend-card" onclick="event.stopPropagation();">
                    <div class="recommend-header">
                        <span class="recommend-title">符合今日剩餘熱量的推薦餐點</span>
                        <button type="button" class="recommend-close" onclick="closeRecommendPanel(event)">✕</button>
                    </div>
                    <div id="recommendBody" class="recommend-body"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="filter-container">
    <?php foreach (['全部', '心園', '理園', '輔園'] as $nav): ?>
        <a href="index.php?filter=<?php echo urlencode($nav); ?>" class="filter-btn <?php echo ($filter == $nav) ? 'active' : ''; ?>"><?php echo $nav; ?></a>
    <?php endforeach; ?>
</div>

<div class="restaurant-list">
    <?php if ($result->num_rows > 0): ?>
        
        <?php if ($is_advanced_search): ?>
            <h4 style="margin-top:0; margin-bottom:15px; color:#666;">為您找到的餐點：</h4>
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card">
                    <div class="item-card-info" style="margin-right: 10px;">
                        <div class="item-text-content">
                            <h3 class="item-name"><?php echo htmlspecialchars($row['item_name']); ?></h3>
                            <p class="item-meta">
                                <?php echo htmlspecialchars($row['res_name']); ?> • 
                                <img src="icon/destination_icon.png" alt="地點" class="dest-icon"> <?php echo htmlspecialchars($row['location']); ?>
                            </p>
                            
                            <div class="item-nutrition-row">
                                <span class="item-price">$<?php echo floatval($row['price']); ?></span>
                                <?php if($row['calories']): ?>
                                    <span style="font-size: 12px; color: #888;">
                                        <img src="icon/fire_icon.png" class="fire-icon" alt="熱量"> <?php echo $row['calories']; ?> kcal
                                    </span>
                                <?php endif; ?>
                                
                                <div class="item-macros">
                                    <?php if($row['protein'] !== null) echo "<span>蛋白質 " . floatval($row['protein']) . "g</span>"; ?>
                                    <?php if($row['fat'] !== null) echo "<span>脂肪 " . floatval($row['fat']) . "g</span>"; ?>
                                    <?php if($row['carbs'] !== null) echo "<span>碳水 " . floatval($row['carbs']) . "g</span>"; ?>
                                </div>
                            </div>
                        </div>
                        <div class="btn-go-text">前往店家 ❯</div>
                    </div>
                </a>
            <?php endwhile; ?>
            
        <?php else: ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card">
                    <div class="res-img">
                        <img src="images/<?php echo $row['image_url']; ?>" class="res-actual-img" alt="店家圖片">
                    </div>
                    <div class="res-info">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p style="font-size: 12px; color: #888;">
                            <img src="icon/destination_icon.png" alt="地點" class="dest-icon"> <?php echo htmlspecialchars($row['location']); ?>
                        </p>
                    </div>
                    <div style="margin-left: auto; color: #ccc;">❯</div>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
        
    <?php else: ?>
        <div style="text-align:center; padding:50px; color:#ccc;">
            <div style="font-size:40px; margin-bottom:10px;">🥺</div>
            找不到符合條件的結果<br>試著放寬篩選條件看看！
        </div>
    <?php endif; ?>
</div>

<div class="ai-fixed-wrapper" id="ai-wrapper">
    <div id="ai-assistant-fab" onclick="toggleAssistant()">
        <img src="images/fju.png" alt="AI助理"> 
    </div>

    <div id="assistant-card" class="assistant-card" style="width: 320px;">
        <div class="assistant-header">
            <span style="font-weight: bold;">輔大美食 AI 助手</span>
            <span onclick="toggleAssistant()" style="cursor:pointer; opacity: 0.7;">✕</span>
        </div>
        
        <div id="chat-box" style="height: 300px; overflow-y: auto; padding: 15px; background: #fdfdfd; display: flex; flex-direction: column; gap: 10px;">
            <div style="background: #eee; padding: 8px 12px; border-radius: 10px; align-self: flex-start; max-width: 80%; font-size: 13px;">
                嗨！我是輔大美食小助手，今天想吃點什麼？可以問我「心園有什麼好吃的？」或者「100元以內的午餐」。
            </div>
        </div>

        <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px;">
            <input type="text" id="chat-input" placeholder="想吃什麼..." style="flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 5px 15px; font-size: 13px; outline: none;">
            <button onclick="sendMessage()" style="background: var(--primary-orange, #FF8C42); color: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; display: flex; align-items: center; justify-content: center;">➤</button>
        </div>
    </div>
</div>

<script>
// --- 1. 送出訊息與 API 串接 ---
async function sendMessage() {
    const chatInput = document.getElementById('chat-input');
    const chatBox = document.getElementById('chat-box');
    if (!chatInput || !chatBox) return;

    const message = chatInput.value.trim();
    if (!message) return;

    // 顯示使用者訊息
    appendMessage('user', message);
    chatInput.value = '';

    // 顯示思考中
    const loadingId = 'loading-' + Date.now();
    appendMessage('ai', '正在幫你挑選美食...', loadingId);

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });

        const rawText = await response.text(); // 先抓原始文字，方便 Debug
        
        try {
            const data = JSON.parse(rawText);
            document.getElementById(loadingId).innerText = data.reply || "我現在有點頭暈，晚點再說。";
        } catch (jsonErr) {
            // 如果 PHP 報錯（噴出 HTML 錯誤訊息），會在這裡捕捉到
            console.error("PHP 回傳內容異常:", rawText);
            document.getElementById(loadingId).innerText = "後端回傳格式不對，請檢查 chat_api.php。";
        }
    } catch (e) {
        console.error("Fetch 錯誤:", e);
        document.getElementById(loadingId).innerText = "網路連線失敗 🥺";
    }
}

// --- 2. 訊息氣泡生成 ---
function appendMessage(role, text, id = '') {
    const chatBox = document.getElementById('chat-box');
    const msgDiv = document.createElement('div');
    
    // 樣式調整
    msgDiv.style.padding = "10px 14px";
    msgDiv.style.borderRadius = "15px";
    msgDiv.style.fontSize = "13px";
    msgDiv.style.maxWidth = "85%";
    msgDiv.style.marginBottom = "8px";
    msgDiv.style.lineHeight = "1.4";
    
    if (role === 'user') {
        msgDiv.style.alignSelf = "flex-end";
        msgDiv.style.background = "#002B5B"; // 輔大藍
        msgDiv.style.color = "white";
        msgDiv.style.borderBottomRightRadius = "2px";
    } else {
        msgDiv.style.alignSelf = "flex-start";
        msgDiv.style.background = "#f0f0f0";
        msgDiv.style.color = "#333";
        msgDiv.style.borderBottomLeftRadius = "2px";
        if (id) msgDiv.id = id;
    }
    
    msgDiv.innerText = text;
    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// --- 3. 初始化與事件監聽 ---
document.addEventListener("DOMContentLoaded", function() {
    // 確保 AI 助理按鈕在最上層
    const aiWrapper = document.getElementById('ai-wrapper');
    if (aiWrapper) document.body.appendChild(aiWrapper);

    // 綁定 Enter 鍵
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    }

    // --- 原本的篩選面板與推薦功能 (保留) ---
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('low_cal') || urlParams.has('high_pro') || urlParams.has('is_veg')) {
        const advPanel = document.getElementById('advPanel');
        if (advPanel) advPanel.classList.add('active');
    }
});

// 切換助理視窗顯示
function toggleAssistant() {
    const card = document.getElementById('assistant-card');
    if (card) {
        const isHidden = card.style.display === 'none' || card.style.display === '';
        card.style.display = isHidden ? 'block' : 'none';
        // 開啟時自動捲動到底部
        if (isHidden) {
            const chatBox = document.getElementById('chat-box');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
}

// 原本的進階篩選切換
function toggleAdvPanel() {
    const panel = document.getElementById('advPanel');
    if (panel) panel.classList.toggle('active');
}
function toggleAssistant() {
    const card = document.getElementById('assistant-card');
    card.style.display = (card.style.display === 'block') ? 'none' : 'block';
}

function toggleFilter() {
    const section = document.getElementById('filter-section');
    section.style.display = (section.style.display === 'block') ? 'none' : 'block';
    document.getElementById('recommend-result').style.display = 'none';
}

const canRecommendRemaining = <?php echo $canRecommendRemaining ? 'true' : 'false'; ?>;

function fetchRecommend(mode) {
    let url = 'get_recommend.php?mode=' + mode;
    
    if(mode === 'filter') {
        const prefs = Array.from(document.querySelectorAll('.pref-check:checked')).map(c => c.value);
        if(prefs.length > 0) url += '&prefs=' + prefs.join(',');
    }

    fetch(url)
    .then(res => res.json())
    .then(data => {
        const resDiv = document.getElementById('recommend-result');
        resDiv.style.display = 'block';
        if(data.success) {
            resDiv.innerHTML = `
                <small style="color: #FF8C42;">💡 推薦試試：</small>
                <span class="result-name">${data.name}</span>
                <p style="font-size: 12px; margin: 5px 0; color:#555;">於 ${data.restaurant} <br><img src="icon/fire_icon.png" style="width:10px; vertical-align:middle; margin-right:2px;"> ${data.calories} kcal | 蛋白質 ${data.protein} g</p>
                <a href="restaurant_detail.php?r_id=${data.r_id}" class="btn-go">前往餐廳看看 ❯</a>
            `;
        } else {
            resDiv.innerHTML = "<p style='color:#999; margin:0;'>暫時找不到符合條件的餐點...🥺<br>試著放寬條件看看！</p>";
        }
    });
}

function fetchRecommendRemaining(maxCal) {
    if (!canRecommendRemaining) {
        alert('管理員與店家身分無法使用剩餘熱量推薦功能。');
        return;
    }
    const panel = document.getElementById('remainingRecommendPanel');
    const body = document.getElementById('recommendBody');
    panel.style.display = 'flex';
    if (maxCal <= 0) {
        body.innerHTML = "<p class='recommend-empty'>您今日已達或超過熱量目標，請先調整紀錄再查看推薦餐點。</p>";
        return;
    }

    body.innerHTML = "<p class='recommend-empty'>讀取符合剩餘熱量的餐點...</p>";
    fetch(`get_recommend.php?mode=remaining&max_cal=${encodeURIComponent(maxCal)}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.items && data.items.length) {
            body.innerHTML = `
                ${data.items.map(item => `
                    <div class="recommend-item">
                        <p class="recommend-item-name">${item.name}</p>
                        <p class="recommend-item-meta">${item.restaurant} • ${item.calories} kcal • $${item.price}</p>
                        <a href="restaurant_detail.php?r_id=${item.r_id}" style="color:#FF8C42; font-size:12px; font-weight:bold; text-decoration:none;">前往店家 ❯</a>
                    </div>
                `).join('')}
            `;
        } else {
            body.innerHTML = "<p class='recommend-empty'>沒有找到符合剩餘熱量的餐點，可修改篩選條件或稍後再試。</p>";
        }
    })
    .catch(() => {
        body.innerHTML = "<p class='recommend-empty'>無法取得推薦，請稍後再試。</p>";
    });
}

function closeRecommendPanel(event) {
    if (event) event.stopPropagation();
    const panel = document.getElementById('remainingRecommendPanel');
    panel.style.display = 'none';
}
</script>

<?php include('footer.php'); ?>