<?php
include('db.php'); // 確保 db.php 內的連線變數是 $conn

// 1. 取得分類篩選
$filter = isset($_GET['filter']) ? $_GET['filter'] : '全部';

/**
 * 2. 後端對接邏輯：依照你最新的 Restaurants 表
 * 欄位：r_id, name, location, description
 */
if ($filter == '全部') {
    $sql = "SELECT * FROM Restaurants";
} else {
    $filter_safe = mysqli_real_escape_string($conn, $filter);
    $sql = "SELECT * FROM Restaurants WHERE location = '$filter_safe'";
}

$result = $conn->query($sql);
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

        /* --- 頂部橫幅修正 (解決擋住的問題) --- */
        .top-banner {
            background-color: var(--fujen-blue);
            color: white;
            padding: 12px 20px 50px; /* 增加底部間距 */
            position: relative;
            z-index: 1;
        }

        .header-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr; 
            align-items: center;
            width: 100%;
        }

        .logo-img { height: 70px; width: auto; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15)); }

        .hero-title { font-size: 28px; font-weight: bold; text-align: center; grid-column: 2; }

        .hero-desc { text-align: center; font-size: 16px; opacity: 0.8; margin-top: 8px; }

        /* --- 分類標籤 (加上 z-index 確保不被擋住) --- */
        .filter-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: -25px; 
            padding: 0 20px;
            position: relative;
            z-index: 999; /* 確保在最前方 */
        }

        .filter-btn {
            background: white;
            color: var(--fujen-blue);
            padding: 8px 18px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            font-weight: bold;
        }

        .filter-btn.active { background: var(--fujen-blue); color: white; }

        /* --- 餐廳列表卡片 --- */
        .restaurant-list { padding: 25px 20px; }

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
        .res-meta { font-size: 12px; color: #888; display: flex; gap: 8px; }

        /* --- 底部導覽列 --- */
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%;
            background: white; display: flex; justify-content: space-around;
            padding: 12px 0; border-top: 1px solid #eee;
            z-index: 1000;
        }
        .nav-item { text-align: center; color: #bbb; text-decoration: none; font-size: 11px; flex: 1; }
        .nav-item.active { color: var(--fujen-blue); font-weight: bold; }
    </style>
</head>
<body>

<div class="top-banner">
    <div class="header-container">
        <a href="index.php">
            <img src="logo.png" alt="Logo" class="logo-img">
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

<div class="restaurant-list">
    <?php 
    if ($result && $result->num_rows > 0): 
        while($row = $result->fetch_assoc()):
            // 這裡對應你最新架構的 r_id
            $current_r_id = $row['r_id'];
    ?>
        <a href="restaurant_detail.php?r_id=<?php echo $current_r_id; ?>" class="res-card">
            <div class="res-img">🍴</div>
            <div class="res-info">
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <div class="res-meta">
                    <span>📍 <?php echo htmlspecialchars($row['location']); ?></span>
                </div>
                <p style="font-size: 11px; color: #aaa; margin-top: 4px;">
                    <?php echo htmlspecialchars($row['description']); ?>
                </p>
            </div>
            <div style="margin-left: auto; color: #ccc;">❯</div>
        </a>
    <?php 
        endwhile; 
    else: 
        echo '<div style="text-align:center; padding:50px; color:#ccc;">目前尚無餐廳資料</div>';
    endif; 
    ?>
</div>

<nav class="bottom-nav">
    <a href="index.php" class="nav-item active"><div>🏠</div><div>店家</div></a>
    <a href="tray.php" class="nav-item"><div>📋</div><div>托盤</div></a>
    <a href="#" class="nav-item"><div>💬</div><div>評價</div></a>
    <a href="login.php" class="nav-item"><div>👤</div><div>我的</div></a>
</nav>

</body>
</html>