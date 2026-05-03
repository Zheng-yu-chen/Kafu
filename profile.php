<?php
include('db.php');
include('header.php');
session_start();

// 1. 檢查登入狀態
$u_id = $_SESSION['u_id'] ?? null; 
$is_logged_in = ($u_id !== null);

// 預設變數初始化
$user_name = "訪客模式"; 
$user_account = "尚未登入"; 
$display_cal = 0; 
$display_pro = 0;
$goal_cal = 2000; 
$chart_labels = [];
$chart_data = [];

if ($is_logged_in) {
    // A. 抓取帳號資訊與目標熱量
    $user_query = "SELECT name, accounts, goal_cal FROM accounts WHERE u_id = $u_id";
    $user_res = $conn->query($user_query);
    if ($user_res && $row = $user_res->fetch_assoc()) {
        $user_name = $row['name'] ?: "同學您好";
        $user_account = $row['accounts'];
        $goal_cal = $row['goal_cal'] ?: 2000;
    }

    // B. 抓取今日進度 (包含學餐與手動輸入)
    $today = date('Y-m-d');
    $sql_stats = "SELECT 
                    SUM(IFNULL(i.calories, l.total_calories)) as total_cal, 
                    SUM(IFNULL(i.protein, l.total_protein)) as total_pro 
                  FROM consumptionlogs l 
                  LEFT JOIN items i ON l.item_id = i.item_id 
                  WHERE l.u_id = $u_id AND DATE(l.recorded_at) = '$today'";

    $stats_result = $conn->query($sql_stats);
    if ($stats_result && $stats = $stats_result->fetch_assoc()) {
        $display_cal = (int)($stats['total_cal'] ?? 0);
        $display_pro = (int)($stats['total_pro'] ?? 0);
    }

    // C. 抓取過去 7 天趨勢 (包含學餐與手動輸入)
    $sql_trend = "SELECT 
                    DATE(l.recorded_at) as log_date, 
                    SUM(IFNULL(i.calories, l.total_calories)) as daily_cal 
                  FROM consumptionlogs l
                  LEFT JOIN items i ON l.item_id = i.item_id
                  WHERE l.u_id = $u_id AND l.recorded_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                  GROUP BY DATE(l.recorded_at)
                  ORDER BY log_date ASC";

    $trend_result = $conn->query($sql_trend);

    // 初始化 7 天陣列，確保圖表連續 (💡 已經刪除重複的區塊)
    $trend_map = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trend_map[$d] = 0;
    }
    while ($trend_row = $trend_result->fetch_assoc()) {
        $trend_map[$trend_row['log_date']] = (int)$trend_row['daily_cal'];
    }

    $chart_labels = [];
    $chart_data = [];
    foreach ($trend_map as $date => $cal) {
        $chart_labels[] = date('m/d', strtotime($date));
        $chart_data[] = $cal;
    }
}
?>

<style>
    body { background: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
    .profile-header { background-color: #002B5B; color: white; padding: 60px 20px 80px; display: flex; align-items: center; gap: 15px; position: relative; }
    .avatar-circle { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    .user-info h2 { margin: 0; font-size: 20px; }
    .user-info p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }
    
    .stats-card-combined {
        background: white; border-radius: 15px; padding: 20px;
        margin: -40px 20px 20px; position: relative; z-index: 10; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .summary-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
    .summary-row h2 { font-size: 16px; color: #333; margin: 0; }
    .total-cal { font-size: 28px; font-weight: bold; color: #FF8C42; }
    .progress-box { background: #e0e0e0; height: 12px; border-radius: 6px; overflow: hidden; margin-bottom: 15px; }
    .progress-fill { background: #4CAF50; height: 100%; transition: width 0.6s ease; }
    .protein-info { display: flex; justify-content: space-between; align-items: center; background: #E8F5E9; padding: 12px 15px; border-radius: 10px; color: #2E7D32; font-weight: bold; font-size: 14px; }
    
    .white-section { background: white; margin: 20px; padding: 20px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-header { font-weight: bold; font-size: 15px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    .menu-link { display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #333; padding: 12px 0; border-top: 1px solid #f0f0f0; }
    .menu-text h4 { margin: 0; font-size: 14px; }
    .menu-text p { margin: 4px 0 0; font-size: 12px; color: #888; }
    .btn-login-top { position: absolute; right: 20px; top: 60px; background: white; color: #002B5B; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: bold; }
    
    /* 💡 統一的登出按鈕樣式 */
    .logout-section { text-align: center; margin: 30px 0 100px; }
    .logout-btn {
        display: inline-block;
        background-color: white;
        color: #F44336;
        border: 1.5px solid #FFCDD2;
        padding: 10px 40px;
        border-radius: 25px;
        text-decoration: none;
        font-size: 15px;
        font-weight: bold;
        transition: 0.2s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .logout-btn:active { background-color: #FFF5F5; transform: scale(0.95); }
</style>

<div class="profile-header">
    <div class="avatar-circle"><?php echo $is_logged_in ? "👤" : "👣"; ?></div>
    <div class="user-info">
        <h2><?php echo htmlspecialchars($user_name); ?></h2>
        <p><?php echo $is_logged_in ? "學號：" . htmlspecialchars($user_account) : "登入後開啟健康追蹤功能"; ?></p>
    </div>
    <?php if (!$is_logged_in): ?>
        <a href="login.php" class="btn-login-top">登入</a>
    <?php endif; ?>
</div>

<div class="stats-card-combined">
    <?php if ($is_logged_in): ?>
        <div class="summary-row">
            <h2>今日攝取進度</h2>
            <span class="total-cal"><?php echo $display_cal; ?> <small style="font-size:14px; color:#888;">/ <?php echo $goal_cal; ?> kcal</small></span>
        </div>
        <?php $percent = ($goal_cal > 0) ? min(($display_cal / $goal_cal) * 100, 100) : 0; ?>
        <div class="progress-box">
            <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
        </div>
        <div class="protein-info">
            <span>💪 今日總蛋白質</span>
            <span style="font-size: 18px;"><?php echo $display_pro; ?> g</span>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding: 10px 0;">
            <p style="color: #888; margin-bottom: 10px;">目前為訪客模式，無法紀錄數據</p>
            <a href="login.php" style="color: #FF8C42; font-weight: bold; text-decoration: none;">點此登入 ❯</a>
        </div>
    <?php endif; ?>
</div>

<div class="white-section">
    <div class="section-header"><span>📈</span> 本週攝取趨勢</div>
    <?php if ($is_logged_in): ?>
        <canvas id="trendChart" height="180"></canvas>
    <?php else: ?>
        <div style="text-align:center; padding:30px 0; color:#ccc;">
            <div style="font-size:40px;">📊</div>
            <p>登入後解鎖趨勢分析</p>
        </div>
    <?php endif; ?>
</div>

<div class="white-section">
    <div class="section-header"><span>🛠️</span> 個人服務</div>
    <a href="<?php echo $is_logged_in ? 'history.php' : 'login.php'; ?>" class="menu-link">
        <div class="menu-text">
            <h4>飲食歷史紀錄</h4>
            <p>檢視過去在輔大校園的每一餐</p>
        </div>
        <div style="color:#ccc;">❯</div>
    </a>
    <a href="<?php echo $is_logged_in ? 'settings.php' : 'login.php'; ?>" class="menu-link" style="border-top: 1px solid #f0f0f0;">
        <div class="menu-text">
            <h4>詳細設定</h4>
            <p>修改目標熱量、飲食偏好</p>
        </div>
        <div style="color:#ccc;">❯</div>
    </a>
</div>

<?php if ($is_logged_in): ?>
    <div class="logout-section">
        <a href="logout.php" class="logout-btn">登出</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{ 
                label: '每日熱量', 
                data: <?php echo json_encode($chart_data); ?>, 
                borderColor: '#FF8C42', 
                backgroundColor: 'rgba(255, 140, 66, 0.1)', 
                borderWidth: 3, 
                fill: true, 
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#FF8C42'
            }]
        },
        options: { 
            responsive: true,
            plugins: { legend: { display: false } }, 
            scales: { 
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, 
                x: { grid: { display: false } } 
            } 
        }
    });
    </script>
<?php endif; ?>

<?php include('footer.php'); ?>