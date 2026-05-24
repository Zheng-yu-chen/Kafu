<?php
include('db.php');
include('header.php');

// 1. 檢查登入狀態
$u_id = $_SESSION['u_id'] ?? null; 
$is_logged_in = ($u_id !== null);

// 預設變數初始化
$user_name = "訪客模式"; 
$user_account = "尚未登入"; 
$user_photo = null; 
$display_cal = 0; 
$display_pro = 0;
$display_price = 0;
$display_fat = 0;
$display_carbs = 0;
$goal_cal = 2000; 
$chart_labels = [];
$chart_data = [];

if ($is_logged_in) {
    // A. 修改 SQL 加入 user_photo
    $user_query = "SELECT name, accounts, goal_cal, user_photo FROM accounts WHERE u_id = $u_id";
    $user_res = $conn->query($user_query);
    
    if ($user_res && $row = $user_res->fetch_assoc()) {
        $user_name = $row['name'] ?: "同學您好";
        $user_account = $row['accounts'];
        $goal_cal = $row['goal_cal'] ?: 2000;
        $user_photo = $row['user_photo']; 
    }

    // B. 抓取今日進度：一併加總 price, fat, carbs
    $today = date('Y-m-d');
    $sql_stats = "SELECT 
                   SUM(COALESCE(i.calories, l.total_calories)) as total_cal, 
                    SUM(COALESCE(i.protein, l.total_protein)) as total_pro,
                    SUM(CASE WHEN l.price > 0 THEN l.price ELSE COALESCE(i.price, 0) END) as total_price, 
                    SUM(COALESCE(NULLIF(l.total_fat, 0), i.fat, 0)) as total_fat, 
                    SUM(COALESCE(NULLIF(l.total_carbs, 0), i.carbs, 0)) as total_carbs
                  FROM consumptionlogs l 
                  LEFT JOIN items i ON l.item_id = i.item_id 
                  WHERE l.u_id = $u_id AND DATE(l.recorded_at) = '$today'";

    $stats_result = $conn->query($sql_stats);
    if ($stats_result && $stats = $stats_result->fetch_assoc()) {
        $display_cal = (int)($stats['total_cal'] ?? 0);
        $display_pro = (float)($stats['total_pro'] ?? 0);
        $display_price = (float)($stats['total_price'] ?? 0);
        $display_fat = (float)($stats['total_fat'] ?? 0);
        $display_carbs = (float)($stats['total_carbs'] ?? 0);
    }

    // C. 抓取過去 7 天趨勢
    $sql_trend = "SELECT 
                    DATE(l.recorded_at) as log_date, 
                    SUM(IFNULL(i.calories, l.total_calories)) as daily_cal 
                  FROM consumptionlogs l
                  LEFT JOIN items i ON l.item_id = i.item_id
                  WHERE l.u_id = $u_id AND l.recorded_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                  GROUP BY DATE(l.recorded_at)
                  ORDER BY log_date ASC";

    $trend_result = $conn->query($sql_trend);

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

    .avatar-circle { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; overflow: hidden; }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

    .user-info { display: flex; flex-direction: column; gap: 6px; }
    .user-info h2 { margin: 0; font-size: 20px; line-height: 1.2; }
    .user-info p { margin: 0; font-size: 13px; opacity: 0.8; }

    .upload-hint { 
        font-size: 11px; background: rgba(255, 255, 255, 0.15); padding: 3px 10px; 
        border-radius: 12px; cursor: pointer; transition: 0.2s; align-self: flex-start; 
        display: inline-flex; align-items: center;
    }
    .upload-hint:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.02); }
    
    .stats-card-combined {
        background: white; border-radius: 15px; padding: 20px;
        margin: -40px 20px 20px; position: relative; z-index: 10; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    
    .progress-box { background: #e0e0e0; height: 12px; border-radius: 6px; overflow: hidden; margin-bottom: 8px; }
    /* 💡 拔掉原本寫死的顏色，加入顏色漸變動畫 */
    .progress-fill { height: 100%; transition: width 0.6s ease, background-color 0.6s ease; }
    
    .summary-main-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
    .total-price { font-size: 20px; font-weight: bold; color: #E53935; } 
    .total-cal { color: var(--primary-orange, #FF8C42); font-size: 32px; font-weight: 900; line-height: 1; }
    .total-cal small { font-size: 14px; font-weight: normal; margin-left: 5px; color: #888; }
    
    .summary-macro-row { display: flex; justify-content: space-between; background: #f5f5f5; padding: 12px 0; border-radius: 10px; margin-top: 15px; }
    .macro-item { text-align: center; flex: 1; }
    .macro-label { display: block; font-size: 12px; color: #888; margin-bottom: 4px; }
    .macro-val { font-size: 15px; font-weight: bold; color: #333; }
    .macro-pro { color: #1976d2; border-right: 1px solid #ddd; } 
    .macro-fat { color: #fbc02d; border-right: 1px solid #ddd; } 
    .macro-carbs { color: #388e3c; } 

    .white-section { background: white; margin: 20px; padding: 20px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-header { font-weight: bold; font-size: 15px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    
    .header-icon { width: 22px; height: 22px; object-fit: contain; }
    .empty-chart-icon { width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px; opacity: 0.5; }

    .menu-link { display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #333; padding: 12px 0; border-top: 1px solid #f0f0f0; }
    .menu-text h4 { margin: 0; font-size: 14px; }
    .menu-text p { margin: 4px 0 0; font-size: 12px; color: #888; }
    .btn-login-top { position: absolute; right: 20px; top: 60px; background: white; color: #002B5B; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: bold; }
    
    .logout-section { text-align: center; margin: 30px 0 100px; }
    .logout-btn { display: inline-block; background-color: white; color: #F44336; border: 1.5px solid #FFCDD2; padding: 10px 40px; border-radius: 25px; text-decoration: none; font-size: 15px; font-weight: bold; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    .logout-btn:active { background-color: #FFF5F5; transform: scale(0.95); }
</style>

<div class="profile-header">
    <div class="avatar-container" onclick="document.getElementById('avatarInput').click();">
        <div class="avatar-circle">
            <?php 
            if ($is_logged_in) {
                if (!empty($user_photo) && file_exists("uploads/" . $user_photo)) {
                    echo '<img src="uploads/' . htmlspecialchars($user_photo) . '" alt="頭像">';
                } else {
                    echo "👤"; 
                }
            } else {
                echo "👣"; 
            }
            ?>
        </div>
        <?php if ($is_logged_in): ?>
            <div class="upload-hint">更換頭像</div>
        <?php endif; ?>
    </div>

    <div class="user-info">
        <h2><?php echo htmlspecialchars($user_name); ?></h2>
        
        <?php if ($is_logged_in): ?>
            <form id="avatarForm" action="upload_avatar.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit();">
            </form>
        <?php endif; ?>

        <p><?php echo $is_logged_in ? "帳號：" . htmlspecialchars($user_account) : "登入後開啟健康追蹤功能"; ?></p>
    </div>
    <?php if (!$is_logged_in): ?>
        <a href="login.php" class="btn-login-top">登入</a>
    <?php endif; ?>
</div>

<div class="stats-card-combined">
    <?php if ($is_logged_in): ?>
        <h2 style="font-size: 16px; color: #333; margin: 0 0 12px 0;">今日攝取進度</h2>
        
        <div class="summary-main-row">
            <div class="total-price">$<?php echo floatval($display_price); ?></div>
            <div class="total-cal">🔥<?php echo $display_cal; ?> <small>/ <?php echo $goal_cal; ?> kcal</small></div>
        </div>

        <?php 
        // 💡 1. 處理百分比、進度條顏色與剩餘熱量
        $percentage = ($goal_cal > 0) ? round(($display_cal / $goal_cal) * 100) : 0;
        $bar_width = ($percentage > 100) ? 100 : $percentage; // 最高不超過100%防破版
        $remaining_cal = $goal_cal - $display_cal;

        // 決定進度條顏色
        if ($percentage < 80) {
            $bar_color = "#4CAF50"; // 🟢 安全範圍 (綠色)
        } elseif ($percentage <= 100) {
            $bar_color = "#FF8C42"; // 🟠 快達標 (橘色)
        } else {
            $bar_color = "#E53935"; // 🔴 已超標 (紅色)
        }
        ?>

        <div class="progress-box">
            <div class="progress-fill" style="width: <?php echo $bar_width; ?>%; background-color: <?php echo $bar_color; ?>;"></div>
        </div>
        
        <div style="font-size: 13px; text-align: right; margin-bottom: 12px; color: <?php echo ($remaining_cal < 0) ? '#E53935' : '#666'; ?>;">
            <?php if ($remaining_cal > 0): ?>
                剩餘可攝取：<strong style="color: <?php echo $bar_color; ?>;"><?php echo $remaining_cal; ?> kcal</strong>
            <?php elseif ($remaining_cal == 0): ?>
                <strong>🎉 完美達標！</strong>
            <?php else: ?>
                已超標：<strong><?php echo abs($remaining_cal); ?> kcal</strong>
            <?php endif; ?>
        </div>

        <div class="summary-macro-row">
            <div class="macro-item macro-pro">
                <span class="macro-label">蛋白質</span>
                <span class="macro-val"><?php echo number_format($display_pro, 1); ?> g</span>
            </div>
            <div class="macro-item macro-fat">
                <span class="macro-label">脂肪</span>
                <span class="macro-val"><?php echo number_format($display_fat, 1); ?> g</span>
            </div>
            <div class="macro-item macro-carbs">
                <span class="macro-label">碳水</span>
                <span class="macro-val"><?php echo number_format($display_carbs, 1); ?> g</span>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding: 10px 0;">
            <p style="color: #888; margin-bottom: 10px;">目前為訪客模式，無法紀錄數據</p>
            <a href="login.php" style="color: #FF8C42; font-weight: bold; text-decoration: none;">點此登入 ❯</a>
        </div>
    <?php endif; ?>
</div>

<div class="white-section">
    <div class="section-header">
        <img src="icon/diagram_icon.png" alt="趨勢" class="header-icon"> 
        本週攝取趨勢
    </div>
    
    <?php if ($is_logged_in): ?>
        <canvas id="trendChart" height="180"></canvas>
    <?php else: ?>
        <div style="text-align:center; padding:30px 0; color:#ccc;">
            <div><img src="icon/diagram_icon.png" alt="圖表" class="empty-chart-icon"></div>
            <p>登入後解鎖趨勢分析</p>
        </div>
    <?php endif; ?>
</div>

<div class="white-section">
    <div class="section-header">
        <img src="icon/settings_icon.png" alt="設定" class="header-icon"> 
        個人服務
    </div>
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

    <a href="<?php echo $is_logged_in ? 'bug_report.php' : 'login.php'; ?>" class="menu-link" style="border-top: 1px solid #f0f0f0;">
        <div class="menu-text">
            <h4>系統錯誤回報</h4>
            <p>回報系統 Bug 或菜單資料錯誤</p>
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