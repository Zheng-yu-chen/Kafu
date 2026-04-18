<?php
include('db.php');//自己換成自己的db php檔案的名字

// 取得分類篩選
$filter = isset($_GET['filter']) ? $_GET['filter'] : '全部';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>KaFu - 輔大美食探索</title>
    <style>
        :root {
            --primary-orange: #FF8C42; 
            --fujen-blue: #002B5B; 
            --bg-light: #f4f7f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'PingFang TC', sans-serif; }
        body { background-color: var(--bg-light); padding-bottom: 80px; }

        /* --- CSS 部分 --- */
/* --- CSS 修正部分 --- */
/* --- CSS 修正部分 --- */
.top-banner {
    background-color: var(--fujen-blue);
    color: white;
    padding: 12px 20px 40px; /* 縮減上下間距，維持輕薄感 */
}

.header-container {
    display: grid;
    /* 將空間切成三份：左側(Logo)、中間(標題)、右側(等寬空白)，確保標題絕對置中 */
    grid-template-columns: 1fr auto 1fr; 
    align-items: center;
    width: 100%;
}
.logo-link {
    position: absolute; /* 關鍵：讓 Logo 浮動，不佔據排版空間 */
    left: 15px;         /* 靠左邊距 */
    top: 10px;          /* 距離頂部的距離，可以自行微調 */
    z-index: 10;
}

.logo-img {
    height: 100px; 
    width: auto;
    cursor: pointer;
    /* 增加一點點陰影讓 Logo 更有層次感 */
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
}

.hero-title {
    font-size: 32px;
    font-weight: bold;
    margin: 0;
    text-align: center;
    grid-column: 2; /* 強制標題佔據中間欄位 */
}

.hero-desc {
    text-align: center;
    font-size: 18px;
    opacity: 0.8;
    margin-top: 8px;
}
        /* --- 分類標籤 (圓角切換鈕) --- */
        .filter-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: -22px;
            padding: 0 20px;
        }

        .filter-btn {
            background: white;
            color: var(--fujen-blue);
            padding: 8px 18px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            font-weight: bold;
        }

        .filter-btn.active {
            background: var(--fujen-blue);
            color: white;
        }

        /* --- 雙推薦區塊 --- */
        .recommend-row {
            display: flex;
            gap: 12px;
            padding: 25px 20px;
        }

        .rec-box {
            flex: 1;
            padding: 18px 10px;
            border-radius: 15px;
            text-align: center;
            color: white;
            text-decoration: none;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .box-random { background: linear-gradient(135deg, #FF8C42, #FFB07C); }
        .box-filter { background: linear-gradient(135deg, #2D9F75, #4ECDC4); }

        .rec-icon { font-size: 24px; margin-bottom: 5px; }
        .rec-title { font-weight: bold; font-size: 15px; }

        /* --- 餐廳列表卡片 --- */
        .list-title { padding: 0 20px 10px; font-weight: bold; color: #666; font-size: 14px; }
        .restaurant-list { padding: 0 20px; }

        .res-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .res-img {
            width: 60px; height: 60px;
            background: #FFF3EB;
            border-radius: 12px;
            margin-right: 15px;
            display: flex; justify-content: center; align-items: center; font-size: 26px;
        }

        .res-info h3 { font-size: 17px; color: var(--fujen-blue); margin-bottom: 4px; }
        .res-meta { font-size: 12px; color: #888; display: flex; gap: 8px; margin-top: 5px; }
        .rating { color: #FF8C42; font-weight: bold; }

        /* --- 底部導覽列 --- */
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%;
            background: white; display: flex; justify-content: space-around;
            padding: 12px 0; border-top: 1px solid #eee;
        }

        .nav-item { text-align: center; color: #bbb; text-decoration: none; font-size: 11px; flex: 1; }
        .nav-item.active { color: var(--fujen-blue); font-weight: bold; }
        .nav-icon { font-size: 20px; margin-bottom: 3px; }
    </style>
</head>
<body>

<div class="top-banner">
    <div class="header-container">
        <a href="index.php">
            <img src="logo.png" alt="KaFu Logo" class="logo-img">
        </a>
        <h1 class="hero-title">輔大美食探索</h1>
    </div>
    <p class="hero-desc">今天想吃什麼呢？🥗</p>
</div>

<div class="filter-container">
    <a href="?filter=全部" class="filter-btn <?php echo $filter == '全部' ? 'active' : ''; ?>">全部</a>
    <a href="?filter=心園" class="filter-btn <?php echo $filter == '心園' ? 'active' : ''; ?>">心園</a>
    <a href="?filter=理園" class="filter-btn <?php echo $filter == '理園' ? 'active' : ''; ?>">理園</a>
    <a href="?filter=輔園" class="filter-btn <?php echo $filter == '輔園' ? 'active' : ''; ?>">輔園</a>
</div>

<div class="recommend-row">
    <a href="random_food.php" class="rec-box box-random">
        <div class="rec-icon">🎲</div>
        <div class="rec-title">隨機推薦</div>
    </a>
    
    <a href="smart_filter.php" class="rec-box box-filter">
        <div class="rec-icon">🎯</div>
        <div class="rec-title">個人需求</div>
    </a>
</div>

<div class="list-title">附近餐廳推薦</div>

<div class="restaurant-list">
    <a href="restaurant_detail.php?r_id=1" class="res-card">
        <div class="res-img">🍴</div>
        <div class="res-info">
            <h3>心園麵店</h3>
            <div class="res-meta">
                <span class="rating">★ 4.2</span>
                <span>📍 心園</span>
            </div>
        </div>
        <div style="margin-left: auto; color: #ccc;">❯</div>
    </a>
</div>

<nav class="bottom-nav">
    <a href="index.php" class="nav-item active">
        <div class="nav-icon">🏠</div>
        <div>店家</div>
    </a>
    <a href="#" class="nav-item">
        <div class="nav-icon">📋</div>
        <div>菜單</div>
    </a>
    <a href="#" class="nav-item">
        <div class="nav-icon">💬</div>
        <div>評價</div>
    </a>
    <a href="login.php" class="nav-item">
        <div class="nav-icon">👤</div>
        <div>我的</div>
    </a>
</nav>

</body>
</html>