<?php
include('db.php');
session_start();

// 1. 手動設定測試 ID (對接學生A)
$u_id = 3; 

// 2. 執行查詢用戶資訊
$sql_user = "SELECT * FROM accounts WHERE u_id = $u_id";
$user_result = $conn->query($sql_user);

if (!$user_result) {
    die("SQL 語法錯誤: " . $conn->error);
}
$user = $user_result->fetch_assoc();

// 3. 統計今日攝取 (注意這裡的 AS 名稱)
// 4. 統計今日攝取 (修正欄位名稱為 i_id)
$today = date('Y-m-d');
$sql_stats = "SELECT SUM(i.calories) as total_cal, SUM(i.protein) as total_pro 
              FROM ConsumptionLogs l 
              JOIN items i ON l.item_id = i.i_id 
              WHERE l.u_id = $u_id AND DATE(l.date) = '$today'";
$stats_result = $conn->query($sql_stats);
$stats = ($stats_result) ? $stats_result->fetch_assoc() : null;

/**
 * 4. 預設數值處理 【修正重點】
 * 要對應上面 SQL 寫的 total_cal 和 total_pro
 */
$display_cal = $stats['total_cal'] ?? 0;
$display_pro = $stats['total_pro'] ?? 0;
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人中心 - KaFu</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --fujen-blue: #002B5B; --text-gray: #888; }
        body { background-color: #f4f7f9; font-family: -apple-system, sans-serif; margin: 0; padding-bottom: 80px; }
        
        /* 頂部藍色背景個人資訊 */
        .profile-header {
    background-color: var(--fujen-blue);
    color: white;
    padding: 60px 20px 80px; /* 增加下方填充，讓卡片有空間浮動 */
    display: flex; 
    align-items: center; 
    gap: 15px;
}
        .avatar-circle {
            width: 60px; height: 60px; background: rgba(255,255,255,0.2);
            border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px;
        }
        .user-info h2 { margin: 0; font-size: 20px; }
        .user-info p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }

        /* 今日熱量數值卡片 */
        /* 2. 修正卡片列的定位 */
.stats-row {
    display: flex; 
    gap: 15px; 
    padding: 0 20px; 
    margin-top: -56px; /* 調整負邊距，讓它剛好壓在藍白交界處 */
    position: relative;
    z-index: 10;
}
        /* 3. 強化卡片的可視度 */
.stat-card {
    flex: 1; 
    background: rgba(255, 255, 255, 0.2); /* 增加一點亮度 */
    backdrop-filter: blur(15px); /* 毛玻璃特效 */
    -webkit-backdrop-filter: blur(15px); /* 確保 iOS/Safari 相容 */
    border: 1px solid rgba(255, 255, 255, 0.3); 
    border-radius: 12px; 
    padding: 18px 15px; 
    color: white;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* 加入陰影增加立體感 */
}
        /* 4. 確保文字顏色清晰 */
.stat-label { 
    font-size: 11px; 
    opacity: 0.9; 
    margin-bottom: 5px;
}
        .stat-value { 
    font-size: 26px; 
    font-weight: bold; 
}
        .stat-unit { font-size: 12px; margin-left: 3px; font-weight: normal; }

        /* 白色區塊容器 */
        .white-section {
            background: white; margin: 20px; padding: 20px; border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-header { font-weight: bold; font-size: 15px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        /* 選單項目 */
        .menu-link {
            display: flex; justify-content: space-between; align-items: center;
            text-decoration: none; color: #333; padding: 10px 0; border-top: 1px solid #f0f0f0;
        }
        .menu-text h4 { margin: 0; font-size: 14px; }
        .menu-text p { margin: 4px 0 0; font-size: 12px; color: var(--text-gray); }

        .logout-link {
            display: flex; justify-content: center; align-items: center; gap: 5px;
            color: #666; font-size: 14px; margin-top: 20px; text-decoration: none;
        }
    </style>
</head>
<body>

<div class="profile-header">
    <div class="avatar-circle">👤</div>
    <div class="user-info">
        <h2><?php echo htmlspecialchars($user['name'] ?? '輔大學生'); ?></h2>
        <p>帳號：<?php echo htmlspecialchars($user['accounts'] ?? '未登入'); ?></p>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">今日熱量</div>
        <div class="stat-value"><?php echo $display_cal; ?><span class="stat-unit">kcal</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">今日蛋白質</div>
        <div class="stat-value"><?php echo $display_pro; ?><span class="stat-unit">g</span></div>
    </div>
</div>

<div class="white-section">
    <div class="section-header">
        <span>📈</span> 本週攝取趨勢
        <span style="margin-left:auto; font-size:11px; color:#999; font-weight:normal;">平均 1700 kcal/天</span>
    </div>
    <canvas id="trendChart" height="150"></canvas>
</div>

<div class="white-section">
    <div class="section-header"><span>📅</span> 歷史紀錄</div>
    <a href="history.php" class="menu-link">
        <div class="menu-text">
            <h4>查看完整歷史紀錄</h4>
            <p>檢視與管理您的飲食歷史</p>
        </div>
        <div style="color:#ccc;">❯</div>
    </a>
</div>

<div class="white-section">
    <div class="section-header"><span>⚙️</span> 設定</div>
    <a href="settings.php" class="menu-link">
        <div class="menu-text">
            <h4>完整設定</h4>
            <p>目標、偏好、通知等設定</p>
        </div>
        <div style="color:#ccc;">❯</div>
    </a>
</div>

<a href="logout.php" class="logout-link">🚪 登出</a>

<p style="text-align:center; font-size:10px; color:#ccc; margin-top:20px;">KaFu v1.0 • Fu Jen Catholic University</p>

<script>
    // 初始化趨勢圖
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['04/05', '04/06', '04/07', '04/08', '04/09', '04/10', '04/11'],
            datasets: [{
                label: '熱量 (kcal)',
                data: [1800, 2150, 1900, 2200, 1750, 2100, 0], // 後續可用 PHP 迴圈產出 SQL 數據
                borderColor: '#FF8C42',
                backgroundColor: 'rgba(255, 140, 66, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
<?php include('footer.php'); ?>
</body>
</html>