<?php
session_start();
include('db.php');
include('header.php');

// 1. 取得一般篩選與搜尋參數
$filter = isset($_GET['filter']) ? $_GET['filter'] : '全部';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// 2. 取得進階篩選參數
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : 300; // 預設最大價格
$is_veg = isset($_GET['is_veg']) ? 1 : 0;
$low_cal = isset($_GET['low_cal']) ? 1 : 0;
$high_pro = isset($_GET['high_pro']) ? 1 : 0;

// 3. 判斷是否為「進階搜尋模式」 (只要有動到搜尋框、價格、標籤的任何一個，就切換為餐點模式)
$is_advanced_search = (!empty($search) || isset($_GET['price_max']) || $is_veg || $low_cal || $high_pro);

if ($is_advanced_search) {
    // 💡 模式 A：進階搜尋，顯示符合條件的「餐點」
    $sql = "SELECT i.item_id, i.name AS item_name, i.price, i.calories, i.protein, i.is_vegetarian, 
                   r.name AS res_name, r.r_id, r.location, r.image_url 
            FROM items i
            JOIN categories c ON i.c_id = c.c_id
            JOIN restaurants r ON c.r_id = r.r_id
            WHERE 1=1";
            
    // 關鍵字搜尋 (同時找餐點名或餐廳名)
    if (!empty($search)) {
        $sql .= " AND (i.name LIKE '%$search%' OR r.name LIKE '%$search%')";
    }
    
    // 價格拉桿篩選
    $sql .= " AND i.price <= $price_max";
    
    // 營養標籤篩選
    if ($is_veg) $sql .= " AND i.is_vegetarian = 1";
    if ($low_cal) $sql .= " AND i.calories < 500 AND i.calories IS NOT NULL";
    if ($high_pro) $sql .= " AND i.protein > 20 AND i.protein IS NOT NULL";
    
    // 地區篩選
    if ($filter !== '全部') {
        $filter_safe = mysqli_real_escape_string($conn, $filter);
        $sql .= " AND r.location = '$filter_safe'";
    }
    
    $sql .= " ORDER BY i.price ASC"; // 價格由低到高排序
    
} else {
    // 💡 模式 B：預設模式，顯示整間「餐廳」
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
?>

<style>
    /* 基礎樣式 */
    .top-banner { background-color: var(--fujen-blue, #002B5B); color: white; padding: 12px 20px 55px; text-align: center; }
    .header-container { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%; margin-bottom: 10px; }
    .logo-img { height: 70px; width: auto; }
    .hero-title { font-size: 28px; font-weight: bold; grid-column: 2; margin: 0; }
    
    /* 搜尋與進階篩選區塊 */
    .search-wrapper { max-width: 320px; margin: 0 auto; position: relative; }
    .search-input-group { display: flex; gap: 8px; }
    .search-input { flex: 1; padding: 10px 15px; border-radius: 20px; border: none; outline: none; background: rgba(255, 255, 255, 0.95); text-align: center; font-size: 14px;}
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
    .item-card-info { flex: 1; }
    .item-name { font-size: 16px; font-weight: bold; color: var(--fujen-blue, #002B5B); margin: 0 0 4px; }
    .item-meta { font-size: 12px; color: #888; margin: 0 0 6px; }
    .item-price { font-weight: bold; color: #E53935; font-size: 14px; }

    /* --- 💡 小助理隱形外框與按鈕 --- */
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
            <!-- 隱藏保留地點參數 -->
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            
            <div class="search-input-group">
                <input type="text" name="search" class="search-input" placeholder="今天想吃什麼？" value="<?php echo htmlspecialchars($search); ?>">
                <button type="button" class="adv-search-btn" onclick="toggleAdvPanel()">進階篩選</button>
            </div>

            <!-- 進階篩選面板 -->
            <div class="adv-panel" id="advPanel">
                <div class="range-group">
                    <div class="range-header">
                        <span>預算上限</span>
                        <span id="priceVal">$<?php echo $price_max; ?></span>
                    </div>
                    <input type="range" name="price_max" min="0" max="300" step="10" value="<?php echo $price_max; ?>" oninput="document.getElementById('priceVal').innerText = '$' + this.value">
                </div>
                
                <div class="tag-group">
                    <label>
                        <input type="checkbox" name="low_cal" class="filter-tag-checkbox" <?php if($low_cal) echo 'checked'; ?>>
                        <span class="filter-tag-label">🥗 低卡 (&lt; 500k)</span>
                    </label>
                    <label>
                        <input type="checkbox" name="high_pro" class="filter-tag-checkbox" <?php if($high_pro) echo 'checked'; ?>>
                        <span class="filter-tag-label">🥩 高蛋白 (&gt; 20g)</span>
                    </label>
                    <label>
                        <input type="checkbox" name="is_veg" class="filter-tag-checkbox" <?php if($is_veg) echo 'checked'; ?>>
                        <span class="filter-tag-label">🥬 素食</span>
                    </label>
                </div>
                
                <button type="submit" class="submit-adv-btn">套用篩選</button>
                <div style="text-align:center;">
                    <!-- 點擊清除條件，只保留地點參數，洗掉其他所有東西 -->
                    <a href="index.php?filter=<?php echo urlencode($filter); ?>" style="font-size:12px; color:#999; text-decoration:none;">✕ 清除所有條件</a>
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
            <!-- =============================== -->
            <!-- 💡 模式 A：顯示【餐點】列表        -->
            <!-- =============================== -->
            <h4 style="margin-top:0; margin-bottom:15px; color:#666;">為您找到的餐點：</h4>
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card">
                    <!-- 💡 已移除左側的 res-img 區塊，讓排版更乾淨 -->
                    <div class="item-card-info" style="margin-right: 10px;">
                        <h3 class="item-name"><?php echo htmlspecialchars($row['item_name']); ?></h3>
                        <p class="item-meta"><?php echo htmlspecialchars($row['res_name']); ?> • 📍 <?php echo htmlspecialchars($row['location']); ?></p>
                        <div class="item-price">$<?php echo floatval($row['price']); ?> 
                            <span style="font-size:12px; color:#888; font-weight:normal; margin-left:8px;">
                                <?php if($row['calories']) echo "🔥 ".$row['calories']." kcal"; ?>
                                <?php if($row['protein']) echo " | 💪 ".$row['protein']." g"; ?>
                            </span>
                        </div>
                    </div>
                    <div style="color: #ccc; font-size:12px; white-space:nowrap;">前往店家 ❯</div>
                </a>
            <?php endwhile; ?>
            
        <?php else: ?>
            <!-- =============================== -->
            <!-- 💡 模式 B：顯示原本的【餐廳】列表   -->
            <!-- =============================== -->
            <h4 style="margin-top:0; margin-bottom:15px; color:#666;">推薦店家：</h4>
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card">
                    <div class="res-img">
                        <img src="images/<?php echo $row['image_url']; ?>" class="res-actual-img" alt="店家圖片">
                    </div>
                    <div class="res-info">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p style="font-size: 12px; color: #888;">📍 <?php echo htmlspecialchars($row['location']); ?></p>
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

<!-- =============================== -->
<!-- 👨‍🍳 廚師小助理區塊              -->
<!-- =============================== -->
<div class="ai-fixed-wrapper" id="ai-wrapper">
    <div id="ai-assistant-fab" onclick="toggleAssistant()">
        <img src="icon/chef_icon.png" alt="廚師助理"> 
    </div>

    <div id="assistant-card" class="assistant-card">
        <div class="assistant-header">
            <span style="font-weight: bold;">美食家助理</span>
            <span onclick="toggleAssistant()" style="cursor:pointer; opacity: 0.7;">✕</span>
        </div>
        <div class="assistant-body">
            <p style="margin: 0;">不知道吃什麼？讓我幫你抽！</p>
            <div class="recommend-actions">
                <button class="btn-action" onclick="fetchRecommend('random')">🎲 隨機推薦</button>
                <button class="btn-action" onclick="toggleFilter()">🔍 挑選需求</button>
            </div>

            <div id="filter-section" style="display:none;" class="filter-options">
                <label class="checkbox-item"><input type="checkbox" class="pref-check" value="low_cal"> 🥗 低卡優先 (&lt; 500 kcal)</label>
                <label class="checkbox-item"><input type="checkbox" class="pref-check" value="high_pro"> 🥩 高蛋白需求 (&gt; 20g)</label>
                <label class="checkbox-item"><input type="checkbox" class="pref-check" value="is_veg"> 🥬 我想吃素 (素食專區)</label>
                <button class="btn-submit" onclick="fetchRecommend('filter')">幫我抽天菜！</button>
            </div>

            <div id="recommend-result" style="display:none;"></div>
        </div>
    </div>
</div>

<script>
// 控制進階篩選面板開關
function toggleAdvPanel() {
    const panel = document.getElementById('advPanel');
    panel.classList.toggle('active');
}

// 確保小助理脫離版面限制
document.addEventListener("DOMContentLoaded", function() {
    document.body.appendChild(document.getElementById('ai-wrapper'));
    
    // 如果網址列有進階搜尋參數，預設把進階面板打開
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('price_max') || urlParams.has('low_cal') || urlParams.has('high_pro') || urlParams.has('is_veg')) {
        document.getElementById('advPanel').classList.add('active');
    }
});

// 小助理的功能
function toggleAssistant() {
    const card = document.getElementById('assistant-card');
    card.style.display = (card.style.display === 'block') ? 'none' : 'block';
}

function toggleFilter() {
    const section = document.getElementById('filter-section');
    section.style.display = (section.style.display === 'block') ? 'none' : 'block';
    document.getElementById('recommend-result').style.display = 'none';
}

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
                <p style="font-size: 12px; margin: 5px 0; color:#555;">於 ${data.restaurant} <br>🔥 ${data.calories} kcal | 💪 ${data.protein} g</p>
                <a href="restaurant_detail.php?r_id=${data.r_id}" class="btn-go">前往餐廳看看 ❯</a>
            `;
        } else {
            resDiv.innerHTML = "<p style='color:#999; margin:0;'>暫時找不到符合條件的餐點...🥺<br>試著放寬條件看看！</p>";
        }
    });
}
</script>

<?php include('footer.php'); ?>