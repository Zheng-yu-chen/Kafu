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
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'price'; // 預設排價格
$sort_direction = isset($_GET['sort_direction']) ? $_GET['sort_direction'] : 'asc'; // 預設低到高

// 3. 判斷是否為「進階搜尋模式」
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
    
    // 在餐點模式下也綁定地區過濾
    if ($filter !== '全部') {
        $filter_safe = mysqli_real_escape_string($conn, $filter);
        $sql .= " AND r.location = '$filter_safe'";
    }
    
    $safe_sort_by = ($sort_by === 'calories') ? 'i.calories' : 'i.price';
    $safe_direction = ($sort_direction === 'desc') ? 'DESC' : 'ASC';
    
    $sql .= " ORDER BY $safe_sort_by $safe_direction";
    
} else {
    // 模式 B：預設模式，顯示整間「餐廳」
    $sql = "SELECT * FROM restaurants r WHERE 1=1";

    // 地區過濾
    if ($filter !== '全部') {
        $filter_safe = mysqli_real_escape_string($conn, $filter);
        $sql .= " AND r.location = '$filter_safe'";
    }
    
    // 餐廳清單預設按照餐廳 ID (或名稱) 排序即可
    $sql .= " ORDER BY r.r_id ASC";
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

<!-- ========================= Intro.js 資源 ========================= -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>

<style>
    /* ========================= Intro.js 樣式覆寫：全螢幕濾鏡感 ========================= */
    /* 1. 全螢幕背景遮罩（毛玻璃模糊 + 深藍色微透濾鏡） */
    .introjs-overlay {
        background: rgba(0, 15, 35, 0.65) !important; /* 偏深的輔大藍濾鏡，增加沉浸感 */
        backdrop-filter: blur(8px) !important; /* 毛玻璃模糊效果 */
        -webkit-backdrop-filter: blur(8px) !important; /* 支援蘋果設備 */
    }

    /* 2. 高亮聚焦的框框（加上微微的橘色發光，讓重點跳脫出來） */
    .introjs-helperLayer {
        background: transparent !important;
        border: 2px solid var(--primary-orange, #FF8C42) !important;
        border-radius: 16px !important;
        box-shadow: 0 0 25px rgba(255, 140, 66, 0.5) !important;
    }

    /* 3. 導覽對話框本體美化 */
    .introjs-tooltip { 
        border-radius: 16px !important; 
        background: rgba(255, 255, 255, 0.95) !important; /* 帶一點點透明度 */
        backdrop-filter: blur(10px) !important;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3) !important;
        border: 1px solid rgba(255,255,255,0.5) !important;
    }

    /* 4. 按鈕樣式維持輔大藍 */
    .introjs-button { 
        background: var(--fujen-blue, #002B5B) !important; 
        color: white !important; 
        text-shadow: none !important; 
        border-radius: 8px !important;
        font-weight: bold !important;
    }
    
    /* 基礎樣式 */
    .top-banner { background-color: var(--fujen-blue, #002B5B); color: white; padding: 12px 20px 55px; text-align: center; }
    .header-container { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%; margin-bottom: 10px; }
    .logo-img { height: 70px; width: auto; }
    .hero-title { font-size: 28px; font-weight: bold; grid-column: 2; margin: 0; }
    
    /* 搜尋與進階篩選區塊 */
    .search-wrapper { max-width: 320px; margin: 0 auto; position: relative; }
    .search-input-group { display: flex; gap: 8px; align-items: stretch; }
    
    .search-input-wrapper { flex: 1; position: relative; display: flex; }
    .search-extra {
        margin-top: 15px; 
        display: flex;
        justify-content: center; 
        align-items: center;
        width: 100%;
    }
    
    /* 排序切換鈕樣式 */
    .sort-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; padding: 0 4px; }
    .sort-options { display: flex; gap: 8px; }
    .sort-toggle-btn { 
        background: #f0f0f0; border: none; border-radius: 12px; 
        padding: 6px 12px; font-size: 12px; font-weight: bold; 
        color: #555; cursor: pointer; display: flex; align-items: center; gap: 4px; 
    }
    .sort-toggle-btn.active { background: #FFF3EB; color: var(--primary-orange, #FF8C42); }
    
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
        width: 22px; height: 22px; object-fit: contain; opacity: 0.6; transition: 0.2s;
    }
    .search-icon-btn:active img { opacity: 1; transform: scale(0.8); }

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
    
    /* 餐廳模式下的圖片外框 */
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
        vertical-align: middle; 
        margin-right: 4px;
        margin-bottom: 3px; 
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

    /* 實心橘色按盤按鈕 */
    .btn-solid-orange {
        background: var(--primary-orange, #FF8C42) !important;
        color: white !important;
        border: none !important;
        border-radius: 12px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(255, 140, 66, 0.3);
        transition: all 0.2s ease;
    }
    .btn-solid-orange:hover {
        background: #F57C00 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 140, 66, 0.4);
    }
    .btn-solid-orange:active { transform: translateY(0); }

    /* 實心深藍（輔大藍）前往餐廳按鈕 */
    .btn-solid-blue {
        background: var(--fujen-blue, #002B5B) !important;
        color: white !important;
        border: none !important;
        text-decoration: none;
        border-radius: 12px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(0, 43, 91, 0.2);
        transition: all 0.2s ease;
    }
    .btn-solid-blue:hover {
        background: #001f42 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 43, 91, 0.3);
    }
    .btn-solid-blue:active { transform: translateY(0); }

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
    #ai-assistant-fab img { width: 54px; height: 54px; object-fit: contain; border-radius: 50%;}

    .assistant-card {
        position: absolute !important;
        bottom: 170px;
        right: 25px;
        width: min(90vw, 320px);
        max-width: 320px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 1000;
        overflow: hidden;
        display: none;
        pointer-events: auto;
        box-sizing: border-box;
    }

    @media (max-width: 420px) {
        .assistant-card {
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            width: calc(100vw - 24px);
            bottom: 100px;
        }
    }
    .assistant-header { background: var(--fujen-blue, #002B5B); color: white; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; }
    .assistant-body { padding: 18px; font-size: 14px; }
</style>

<div class="top-banner">
    <div class="header-container" style="display: flex; justify-content: center; align-items: center; position: relative;">
        <a href="index.php">
            <img src="logo.png" alt="Logo" class="logo-img">
        </a>
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
            <div class="search-extra" style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                <!-- ========================= Intro.js Step 3 ========================= -->
                <div class="remaining-calorie" data-step="3" data-intro="系統會在此即時顯示您今日的熱量攝取狀況。">
                    今日剩餘熱量：<?php echo $remaining_cal; ?> kcal
                </div>
                <div id="random-dice-btn" onclick="fetchRandomDish()" 
                     style="cursor: pointer; font-size: 20px; background: rgba(255,255,255,0.2); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    🎲
                </div>
                <a href="favorites.php" id="home-favorite-btn" title="查看收藏餐點"
                       style="text-decoration: none; cursor: pointer; font-size: 18px; background: rgba(255,255,255,0.2); width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    ❤️
                </a>
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
        </form>
    </div>
</div>

<!-- ========================= Intro.js Step 1 ========================= -->
<div class="filter-container">
    <?php foreach (['全部', '心園', '理園', '輔園'] as $index => $nav): 
        // 建立 URL 參數，讓點選學餐分類時，能同時保留原本搜尋的關鍵字、預算、高蛋白等設定
        $query_args = $_GET;
        $query_args['filter'] = $nav;
        $link_url = "index.php?" . http_build_query($query_args);
        
        // 確保導覽只標記在第一顆按鈕
        $intro_attr = ($index === 0) ? ' data-step="1" data-intro="第一步：從這裡選擇您想用餐的學餐區域！"' : '';
    ?>
        <a href="<?php echo htmlspecialchars($link_url); ?>" class="filter-btn <?php echo ($filter == $nav) ? 'active' : ''; ?>" <?php echo $intro_attr; ?>><?php echo $nav; ?></a>
    <?php endforeach; ?>
</div>

<div class="restaurant-list">
    <?php if ($result->num_rows > 0): ?>
        
        <?php if ($is_advanced_search): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const form = document.getElementById('searchForm');
                    if (form) {
                        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="sort_by" id="form_sort_by" value="<?php echo $sort_by; ?>">`);
                        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="sort_direction" id="form_sort_direction" value="<?php echo $sort_direction; ?>">`);
                    }
                });
            </script>

            <div class="sort-bar">
                <span style="font-size: 14px; font-weight: bold; color: #666;">
                    為您找到的餐點 (<?php echo htmlspecialchars($filter); ?>)
                </span>
                <div class="sort-options">
                    <button type="button" class="sort-toggle-btn <?php echo ($sort_by == 'price') ? 'active' : ''; ?>" onclick="handleSortToggle('price')">
                        價格 
                        <?php if ($sort_by == 'price') echo ($sort_direction == 'desc') ? '↓' : '↑'; ?>
                    </button>
                    <button type="button" class="sort-toggle-btn <?php echo ($sort_by == 'calories') ? 'active' : ''; ?>" onclick="handleSortToggle('calories')">
                        熱量 
                        <?php if ($sort_by == 'calories') echo ($sort_direction == 'desc') ? '↓' : '↑'; ?>
                    </button>
                </div>
            </div>
            
            <?php $item_index = 0; ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="res-card" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="item-card-info">
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
                                    <?php if($row['carbs'] !== null) echo "<span>碳水化合物 " . floatval($row['carbs']) . "g</span>"; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end; margin-left: 10px;">
                        <!-- ========================= Intro.js Step 2 (餐點模式) ========================= -->
                        <?php 
                            $intro_attr_adv = ($item_index === 0) ? ' data-step="2" data-intro="點擊這裡，將喜歡的餐點加入托盤並計算熱量。"' : ''; 
                        ?>
                        <button type="button" class="btn-solid-orange" data-item-id="<?php echo $row['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES); ?>" onclick="openTrayModal(this.dataset.itemId, this.dataset.itemName)" <?php echo $intro_attr_adv; ?>>
                            加入托盤+
                        </button>
                        
                        <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="btn-solid-blue">
                            前往餐廳 ❯
                        </a>
                    </div>
                </div>
                <?php $item_index++; ?>
            <?php endwhile; ?>
            
        <?php else: ?>
            <?php $item_index = 0; ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <!-- ========================= Intro.js Step 2 (餐廳模式) ========================= -->
                <?php 
                    $intro_attr_res = ($item_index === 0) ? ' data-step="2" data-intro="第二步: 點擊進入餐廳，尋找您想吃的餐點並加入托盤！"' : ''; 
                ?>
                <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card" <?php echo $intro_attr_res; ?>>
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
                <?php $item_index++; ?>
            <?php endwhile; ?>
        <?php endif; ?>
        
    <?php else: ?>
        <div style="text-align:center; padding:50px; color:#ccc;">
            <div style="font-size:40px; margin-bottom:10px;">🥺</div>
            在「<?php echo htmlspecialchars($filter); ?>」找不到符合條件的結果<br>試著換個學餐或放寬篩選條件看看！
        </div>
    <?php endif; ?>
</div>

<div id="trayModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>加入托盤</h2>
                <p id="modalItemName">餐點名稱</p>
            </div>
            <button class="close-btn" onclick="closeTrayModal()">×</button>
        </div>

        <form action="add_to_tray.php" method="POST">
            <input type="hidden" name="item_id" id="modalItemId" value="">

            <div class="modal-body">
                <?php if (isset($_SESSION['u_id'])): ?>
                    <div class="form-group">
                        <label>用餐日期</label>
                        <div class="date-input-wrapper">
                            <span class="date-icon">📅</span>
                            <input type="date" name="eat_date" class="date-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>用餐時段</label>
                        <div class="meal-grid">
                            <label class="meal-option"><input type="radio" name="meal_time" value="早餐" required><span>早餐</span></label>
                            <label class="meal-option"><input type="radio" name="meal_time" value="午餐"><span>午餐</span></label>
                            <label class="meal-option"><input type="radio" name="meal_time" value="晚餐"><span>晚餐</span></label>
                            <label class="meal-option"><input type="radio" name="meal_time" value="點心"><span>點心</span></label>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="eat_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="hidden" name="meal_time" value="全天">
                    <div style="text-align:center; color:#888; padding: 10px 0 20px; font-size:14px; line-height: 1.5;">
                        <span style="font-size:24px; display:block; margin-bottom:5px;">👣</span>
                        您目前為訪客模式<br>將直接暫存於托盤中
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>餐點份數 / 數量</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="number" name="quantity" id="modalQty" value="1" min="1" max="99" class="qty-input">
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <button type="submit" class="submit-tray-btn">確認加入</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    display: none ;
    position: fixed !important;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 20000 !important;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.modal-box {
    background: white;
    width: 100%;
    max-width: 320px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease;
}
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header {
    background-color: var(--fujen-blue, #002B5B);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.modal-header h2 { margin: 0; font-size: 20px; }
.modal-header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; font-weight: normal; }
.close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; }
.modal-body { padding: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: bold; }
.date-input-wrapper { position: relative; }
.date-icon { position: absolute; left: 12px; top: 12px; font-size: 16px; color: #555; pointer-events: none; }
.date-input { width: 100%; padding: 12px 12px 12px 35px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
.meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.meal-option { display: block; }
.meal-option input { display: none; }
.meal-option span { display: block; text-align: center; padding: 12px; background: #eee; color: #333; border-radius: 8px; font-size: 14px; cursor: pointer; box-sizing: border-box; border: 1px solid transparent; transition: transform 0.1s ease, background 0.2s, color 0.2s; }
.meal-option span:active { transform: scale(0.94); }
.meal-option input:checked + span { background: var(--fujen-blue, #002B5B); color: #fff; font-weight: bold; }
.qty-control { display: flex; align-items: center; gap: 10px; }
.qty-btn { width: 45px; height: 45px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; font-size: 20px; cursor: pointer; transition: 0.2s; }
.qty-btn:active { background: #eee; }
.qty-input { flex: 1; text-align: center; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
.submit-tray-btn {
    width: 100%;
    background-color: #E6762D;
    color: white;
    padding: 15px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.3s, transform 0.1s, box-shadow 0.1s;
    box-shadow: 0 4px 0 #B35C22;
}
.submit-tray-btn:hover { background-color: #FF8336; }
</style>

<script>
async function sendMessage() {
    const chatInput = document.getElementById('chat-input');
    const chatBox = document.getElementById('chat-box');
    if (!chatInput || !chatBox) return;

    const message = chatInput.value.trim();
    if (!message) return;

    appendMessage('user', message);
    chatInput.value = '';

    const loadingId = 'loading-' + Date.now();
    appendMessage('ai', '正在幫你挑選美食...', loadingId);

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });

        const rawText = await response.text();
        try {
            const data = JSON.parse(rawText);
            document.getElementById(loadingId).innerText = data.reply || "我現在有點頭暈，晚點再說。";
        } catch (jsonErr) {
            console.error("PHP 回傳內容異常:", rawText);
            document.getElementById(loadingId).innerText = "後端回傳格式不對，請檢查 chat_api.php。";
        }
    } catch (e) {
        console.error("Fetch 錯誤:", e);
        document.getElementById(loadingId).innerText = "網路連線失敗 🥺";
    }
}

function appendMessage(role, text, id = '') {
    const chatBox = document.getElementById('chat-box');
    const msgDiv = document.createElement('div');
    
    msgDiv.style.padding = "10px 14px";
    msgDiv.style.borderRadius = "15px";
    msgDiv.style.fontSize = "13px";
    msgDiv.style.maxWidth = "85%";
    msgDiv.style.marginBottom = "8px";
    msgDiv.style.lineHeight = "1.4";
    
    if (role === 'user') {
        msgDiv.style.alignSelf = "flex-end";
        msgDiv.style.background = "#002B5B"; 
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

document.addEventListener("DOMContentLoaded", function() {
    // 1. 【保留】您原本的 AI 小助理與網址參數判斷邏輯
    const aiWrapper = document.getElementById('ai-wrapper');
    if (aiWrapper) document.body.appendChild(aiWrapper);

    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('low_cal') || urlParams.has('high_pro') || urlParams.has('is_veg')) {
        const advPanel = document.getElementById('advPanel');
        if (advPanel) advPanel.classList.add('active');
    }

    // 2. 【更新】進階跨頁版 Intro.js 全螢幕濾鏡 + 下次不再顯示腳本
    const canShowTour = <?php echo $canRecommendRemaining ? 'true' : 'false'; ?>;

    if (canShowTour && !localStorage.getItem('kafu_onboarding_done')) {
        setTimeout(() => {
            const tour = introJs();

            // 1. 定義基礎步驟 (所有人可見)
            let steps = [
                {
                    element: document.querySelector('.filter-btn'), 
                    intro: "第一步：從這裡選擇您想用餐的學餐區域！",
                    position: 'bottom'
                },
                {
                    element: document.querySelector('.res-card'),
                    intro: "第二步：點擊進入餐廳，尋找您想吃的餐點並加入托盤！",
                    position: 'bottom'
                }
            ];

           // 關鍵修改：檢查元素是否真的存在於畫面上
            // 如果沒登入導致這些元素隱藏或沒產生，這個函式會自動略過，不會報錯
            const addIfExist = (selector, text) => {
                const el = document.querySelector(selector);
                if (el) {
                    steps.push({ element: el, intro: text, position: 'bottom' });
                }
            };

            // 檢查這三個功能按鈕是否存在
            addIfExist('.remaining-calorie', "第三步：系統會在此即時顯示您今日的剩餘熱量攝取狀況。");
            addIfExist('#random-dice-btn', "第四步：不知道吃什麼嗎？點擊這裡使用「隨機推薦」功能！");
            addIfExist('#home-favorite-btn', "第五步：點擊這裡查看您收藏的美味餐點，隨時回味！");

            // 設定導覽
            tour.setOptions({
                nextLabel: '下一步',
                prevLabel: '上一步',
                doneLabel: '下一步',
                showStepNumbers: true,
                showBullets: false,
                scrollTo: 'element',
                steps: steps
            });
              
            // 當導覽氣泡渲染時，動態在左下角塞入「下次不再顯示」的勾選框
            tour.onchange(function() {
                setTimeout(() => {
                    const tooltipButtons = document.querySelector('.introjs-tooltipbuttons');
                    
                    // 檢查是否已經存在，避免重複生成
                    if (tooltipButtons && !document.getElementById('introjs-dont-show-wrapper')) {
                        const wrapper = document.createElement('div');
                        wrapper.id = 'introjs-dont-show-wrapper';
                        wrapper.style.display = 'inline-flex';
                        wrapper.style.alignItems = 'center';
                        wrapper.style.marginRight = 'auto'; // 利用 flex 將按鈕推至右側
                        wrapper.style.fontSize = '13px';
                        wrapper.style.userSelect = 'none';

                        // 插入 HTML，套用您的橘色主體色
                        wrapper.innerHTML = `
                            <input type="checkbox" id="introjs-dont-show" style="margin-right: 6px; accent-color: var(--primary-orange, #FF8C42); cursor: pointer; width: 15px; height: 15px;">
                            <label for="introjs-dont-show" style="cursor: pointer; font-weight: normal; margin: 0; color: #555;">下次不再顯示</label>
                        `;
                        
                        tooltipButtons.style.display = 'flex';
                        tooltipButtons.style.alignItems = 'center';
                        tooltipButtons.insertBefore(wrapper, tooltipButtons.firstChild);
                    }
                }, 50);
            });

            // 監聽點擊「下一步 (進入餐廳)」結束首頁導覽
            tour.oncomplete(function() {
                const checkBox = document.getElementById('introjs-dont-show');
                if (checkBox && checkBox.checked) {
                    localStorage.setItem('kafu_onboarding_done', 'true');
                    localStorage.removeItem('kafu_tour_step');
                } else {
                    // 使用者沒勾選「下次不再顯示」，代表要繼續進行跨頁導覽，設定步驟 3 標記
                    localStorage.setItem('kafu_tour_step', '3');
                    
                    // 自動幫使用者尋找第一個導覽目標的連結並跳轉
                    const firstResCard = document.querySelector('[data-step="2"]');
                    if (firstResCard) {
                        if (firstResCard.tagName === 'A') {
                            window.location.href = firstResCard.href;
                        } else {
                            // 如果是進階搜尋餐點模式，往上找卡片再尋找「前往餐廳 ❯」的按鈕連結
                            const link = firstResCard.closest('.res-card')?.querySelector('a.btn-solid-blue');
                            if (link) window.location.href = link.href;
                        }
                    }
                }
            });

            // 監聽點選 ✕ 跳出導覽
            tour.onexit(function() {
                const checkBox = document.getElementById('introjs-dont-show');
                if (checkBox && checkBox.checked) {
                    localStorage.setItem('kafu_onboarding_done', 'true');
                }
                // 中途主動關閉則清除跨頁標記，不要跳轉
                localStorage.removeItem('kafu_tour_step');
            });

            tour.start();

            // 💡 額外優化：如果使用者在步驟 2 時沒有點導覽對話框的按鈕，而是直接用滑鼠手動點擊了畫面上的餐廳卡片，也要幫他帶入步驟 3 標記
            const step2Element = document.querySelector('[data-step="2"]');
            if (step2Element) {
                step2Element.addEventListener('click', function() {
                    const checkBox = document.getElementById('introjs-dont-show');
                    if (!(checkBox && checkBox.checked)) {
                        localStorage.setItem('kafu_tour_step', '3');
                    }
                });
            }
        }, 1000);
    }
});
function toggleAssistant() {
    const card = document.getElementById('assistant-card');
    if (card) {
        const isHidden = card.style.display === 'none' || card.style.display === '';
        card.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            const chatBox = document.getElementById('chat-box');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
}

function toggleAdvPanel() {
    const panel = document.getElementById('advPanel');
    if (panel) panel.classList.toggle('active');
}

function fetchRandomDish() {
    const btn = document.getElementById('random-dice-btn');
    btn.style.transform = 'rotate(720deg)';
    
    fetch('get_recommend.php?mode=random')
    .then(res => res.json())
    .then(data => {
        if(data.success && data.r_id) {
            const resName = encodeURIComponent(data.restaurant || "好吃的店家");
            window.location.href = `restaurant_detail.php?r_id=${data.r_id}&from=dice&name=${resName}`;
        } else {
            alert('抽籤失敗，請稍後再試');
            btn.style.transform = 'rotate(0deg)';
        }
    })
    .catch(err => {
        console.error(err);
        btn.style.transform = 'rotate(0deg)';
    });
}

function handleSortToggle(targetField) {
    const urlParams = new URLSearchParams(window.location.search);
    let currentField = urlParams.get('sort_by') || 'price';
    let currentDirection = urlParams.get('sort_direction') || 'asc';
    let nextDirection = 'asc';
    
    if (currentField === targetField) {
        nextDirection = (currentDirection === 'asc') ? 'desc' : 'asc';
    } else {
        nextDirection = 'asc';
    }
    
    urlParams.set('sort_by', targetField);
    urlParams.set('sort_direction', nextDirection);
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

function openTrayModal(itemId, itemName) {
    console.log('openTrayModal', itemId, itemName);
    const modal = document.getElementById('trayModal');
    const itemIdInput = document.getElementById('modalItemId');
    const itemNameLabel = document.getElementById('modalItemName');
    const qtyInput = document.getElementById('modalQty');
    if (!modal || !itemIdInput || !itemNameLabel || !qtyInput) return;

    itemIdInput.value = itemId;
    itemNameLabel.innerText = itemName;
    qtyInput.value = 1;
    modal.style.display = 'flex';
}

function changeQty(amt) {
    const qtyInput = document.getElementById('modalQty');
    if (!qtyInput) return;
    let currentVal = parseInt(qtyInput.value, 10) || 1;
    let newVal = currentVal + amt;
    if (newVal < 1) newVal = 1;
    qtyInput.value = newVal;
}

function closeTrayModal() {
    const modal = document.getElementById('trayModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('trayModal');
    if (modal && e.target === modal) {
        closeTrayModal();
    }
});
</script>

<?php include('footer.php'); ?>