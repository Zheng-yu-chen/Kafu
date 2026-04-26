<?php
session_start();
include('db.php');
include('header.php');

// 1. 取得篩選與搜尋參數
$filter = isset($_GET['filter']) ? $_GET['filter'] : '全部';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// 2. 構建 SQL：串聯餐廳與餐點
$sql = "SELECT DISTINCT r.* FROM restaurants r
        LEFT JOIN categories c ON r.r_id = c.r_id
        LEFT JOIN items i ON c.c_id = i.c_id
        WHERE 1=1";

if ($filter !== '全部') {
    $filter_safe = mysqli_real_escape_string($conn, $filter);
    $sql .= " AND r.location = '$filter_safe'";
}

if (!empty($search)) {
    $sql .= " AND (r.name LIKE '%$search%' OR r.description LIKE '%$search%' OR i.name LIKE '%$search%')";
}

$result = $conn->query($sql);
?>

<style>
    .top-banner { background-color: var(--fujen-blue, #002B5B); color: white; padding: 12px 20px 55px; text-align: center; }
    .header-container { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%; margin-bottom: 10px; }
    .logo-img { height: 70px; width: auto; }
    .hero-title { font-size: 28px; font-weight: bold; grid-column: 2; margin: 0; }
    .search-input { width: 100%; padding: 8px 15px; border-radius: 20px; border: none; outline: none; background: rgba(255, 255, 255, 0.95); text-align: center; }
    .filter-container { display: flex; justify-content: center; gap: 10px; margin-top: -25px; padding: 0 20px; position: relative; z-index: 10; }
    .filter-btn { background: white; color: var(--fujen-blue, #002B5B); padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); font-weight: bold; }
    .filter-btn.active { background: var(--fujen-blue, #002B5B); color: white; }
    .restaurant-list { padding: 25px 20px; padding-bottom: 100px; }
    .res-card { background: white; border-radius: 15px; padding: 15px; display: flex; align-items: center; text-decoration: none; color: inherit; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .res-img { width: 60px; height: 60px; background: #FFF3EB; border-radius: 12px; margin-right: 15px; display: flex; justify-content: center; align-items: center; font-size: 26px; }
    
    /* --- 💡 神級排版：隱形固定外框 --- */
    .ai-fixed-wrapper {
        position: fixed !important; 
        bottom: 0;
        left: 50%;
        transform: translateX(-50%); 
        width: 100%;
        max-width: 450px; 
        height: 0; 
        z-index: 9999;
        pointer-events: none; 
    }

    #ai-assistant-fab {
        position: absolute !important;
        bottom: 100px;
        right: 25px; /* 💡 稍微再往內縮一點，讓它看起來更有呼吸空間 */
        width: 60px; height: 60px;
        background: var(--primary-orange, #FF8C42);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 30px; cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        pointer-events: auto; 
    }

    .assistant-card {
        position: absolute !important;
        bottom: 170px;
        right: 25px;
        width: 280px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 1000;
        overflow: hidden;
        display: none;
        pointer-events: auto; 
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
    <form action="index.php" method="GET" style="max-width: 260px; margin: 0 auto;">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <input type="text" name="search" class="search-input" placeholder="今天想吃什麼？🥗" value="<?php echo htmlspecialchars($search); ?>">
    </form>
</div>

<div class="filter-container">
    <?php foreach (['全部', '心園', '理園', '輔園'] as $nav): ?>
        <a href="index.php?filter=<?php echo urlencode($nav); ?>" class="filter-btn <?php echo ($filter == $nav) ? 'active' : ''; ?>"><?php echo $nav; ?></a>
    <?php endforeach; ?>
</div>

<div class="restaurant-list">
    <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
        <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card">
            <div class="res-img">🍴</div>
            <div class="res-info">
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <p style="font-size: 12px; color: #888;">📍 <?php echo htmlspecialchars($row['location']); ?></p>
            </div>
            <div style="margin-left: auto; color: #ccc;">❯</div>
        </a>
    <?php endwhile; else: ?>
        <div style="text-align:center; padding:50px; color:#ccc;">找不到相關餐廳</div>
    <?php endif; ?>
</div>

<div class="ai-fixed-wrapper" id="ai-wrapper">
    <div id="ai-assistant-fab" onclick="toggleAssistant()">👨‍🍳</div>

    <div id="assistant-card" class="assistant-card">
        <div class="assistant-header">
            <span style="font-weight: bold;">美食家助理</span>
            <span onclick="toggleAssistant()" style="cursor:pointer; opacity: 0.7;">✕</span>
        </div>
        <div class="assistant-body">
            <p style="margin: 0;">肚子餓了嗎？讓我幫你選！</p>
            <div class="recommend-actions">
                <button class="btn-action" onclick="fetchRecommend('random')">🎲 隨機推薦</button>
                <button class="btn-action" onclick="toggleFilter()">🔍 挑選需求</button>
            </div>

            <div id="filter-section" style="display:none;" class="filter-options">
                <label class="checkbox-item"><input type="checkbox" class="pref-check" value="low_cal"> 🥗 低卡優先 (&lt; 500 kcal)</label>
                <label class="checkbox-item"><input type="checkbox" class="pref-check" value="high_pro"> 🥩 高蛋白需求 (&gt; 20g)</label>
                <label class="checkbox-item"><input type="checkbox" class="pref-check" value="is_veg"> 🥬 我想吃素 (素食專區)</label>
                <button class="btn-submit" onclick="fetchRecommend('filter')">找出我的天菜</button>
            </div>

            <div id="recommend-result" style="display:none;"></div>
        </div>
    </div>
</div>

<script>
// 把小助理 HTML 結構搬到 body 底下
document.addEventListener("DOMContentLoaded", function() {
    document.body.appendChild(document.getElementById('ai-wrapper'));
});

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