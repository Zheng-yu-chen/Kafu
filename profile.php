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
    // 1. 真正用來撈出前 5 則顯示在選單裡的資料
    $announcement_query = "SELECT title, content, created_at FROM announcements ORDER BY created_at DESC LIMIT 5";
    $announcement_result = $conn->query($announcement_query);

    // 🎯 修正核心 1：算總數，同時拿到最新一則公告的 ID 作為已讀標記
    $count_query = "SELECT COUNT(id) AS total, MAX(id) AS latest_id FROM announcements";
    $count_result = $conn->query($count_query);
    $count_row = $count_result ? $count_result->fetch_assoc() : null;

    $announcement_count = $count_row ? intval($count_row['total']) : 0;
    $latest_announcement_id = $count_row && $count_row['latest_id'] ? intval($count_row['latest_id']) : 0;

?>

<style>
    body { background: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
    /* ==========================================================================
   通知鈴鐺與下拉選單樣式
   ========================================================================== */

    /* 鈴鐺最外層貨櫃定位 */
    .notification-dropdown {
        position: relative;
        display: inline-block;
        margin-left: auto; /* 👈 關鍵核心：自動向左塞滿空間，把鈴鐺推到最右邊 */
        margin-right: 10px; /* 👈 加分微調：讓鈴鐺跟最右邊邊緣保持一點點呼吸空間 */
    }

    /* 鈴鐺按鈕本體 */
    .notification-trigger {
        background: none;
        border: none;
        color: #666666; /* 預設深灰色。如果你的導覽列是綠底，可以改成 #ffffff */
        font-size: 20px;
        cursor: pointer;
        position: relative;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease;
    }

    /* 滑鼠移上去時，鈴鐺變成亮橘色 */
    .notification-trigger:hover {
        color: #FF9800; 
    }

    /* 未讀通知小紅點 */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #ff4d4f; /* 警示紅 */
        color: #ffffff;
        font-size: 11px;
        font-weight: bold;
        border-radius: 10px;
        padding: 2px 6px;
        line-height: 1;
        min-width: 12px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(255, 77, 79, 0.3);
    }

    /* 公告下拉選單卡片本體 */
    .notification-menu {
        display: none; /* 預設隱藏 */
        position: absolute;
        right: 0;
        top: 100%;
        width: 290px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12); /* 質感微陰影 */
        z-index: 1000;
        margin-top: 12px;
        overflow: hidden;
        border: 1px solid #edf2f7;
    }
    /* 確定要有這行！當有 .show 時才顯示選單 */
    .notification-menu.show {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* 下拉選單頂部標頭 */
    .notification-header {
        background-color: #f8fafc;
        padding: 14px 16px;
        font-size: 14px;
        font-weight: bold;
        color: #333333;
        border-bottom: 1px solid #edf2f7;
    }

    /* 公告列表滾動區域 */
    .notification-list {
        max-height: 320px;
        overflow-y: auto;
    }

    /* 單條公告項目 */
    .notification-item {
        padding: 14px 16px;
        border-bottom: 1px solid #f0f4f8;
        transition: background-color 0.2s ease;
    }

    /* 滑鼠移入公告項目變淺灰藍色 */
    .notification-item:hover {
        background-color: #f8fafc;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    /* 公告標題 */
    .noti-title {
        font-size: 13px;
        font-weight: bold;
        color: #2d3748;
        margin-bottom: 4px;
    }

    /* 公告內容（超過兩行自動變 ...） */
    .noti-content {
        font-size: 12px;
        color: #718096;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* 公告時間 */
    .noti-time {
        font-size: 10px;
        color: #a0aec0;
        margin-top: 6px;
        text-align: right;
    }

    /* 完全沒有公告時的空白狀態 */
    .notification-empty {
        padding: 30px 20px;
        text-align: center;
        color: #a0aec0;
        font-size: 13px;
    }
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
    
    <div class="notification-dropdown">
        <button class="notification-trigger" id="notiBtn" type="button" data-latest-id="<?php echo $latest_announcement_id; ?>">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <i class="fa-solid fa-bell"></i>
            
            <?php if ($announcement_count > 0): ?>
                <span class="notification-badge" id="notiBadge"><?php echo $announcement_count; ?></span>
            <?php endif; ?>
        </button>
        
        <div class="notification-menu" id="notiMenu">
            <div class="notification-header">最新店家公告</div>
            <div class="notification-list">
                <?php 
                if ($announcement_result) {
                    $announcement_result->data_seek(0);
                }
                if ($announcement_count > 0 && $announcement_result) {    
                    while($row = $announcement_result->fetch_assoc()) {
                        ?>
                        <div class="notification-item">
                            <div class="noti-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="noti-content"><?php echo htmlspecialchars($row['content']); ?></div>
                            <div class="noti-time"><?php echo date('m/d H:i', strtotime($row['created_at'])); ?></div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="notification-empty">目前沒有任何公告</div>';
                }
                ?>
            </div>
        </div>
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
        $percentage = ($goal_cal > 0) ? round(($display_cal / $goal_cal) * 100) : 0;
        $bar_width = ($percentage > 100) ? 100 : $percentage; 
        $remaining_cal = $goal_cal - $display_cal;

        if ($percentage < 80) {
            $bar_color = "#4CAF50"; 
        } elseif ($percentage <= 100) {
            $bar_color = "#FF8C42"; 
        } else {
            $bar_color = "#E53935"; 
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
    document.addEventListener("DOMContentLoaded", function () {
        const chartCanvas = document.getElementById('trendChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels ?? []); ?>,
                    datasets: [{ 
                        label: '每日熱量', 
                        data: <?php echo json_encode($chart_data ?? []); ?>, 
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
        }
    });
    </script>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const notiBtn = document.getElementById('notiBtn');
    const notiMenu = document.getElementById('notiMenu');
    const notiBadge = document.getElementById('notiBadge');

    if (notiBtn && notiMenu) {
        // 💡 剛載入網頁時立馬檢查：如果上次點過的就是目前最新這則公告，紅點直接隱藏！
        if (notiBadge) {
            const lastReadId = localStorage.getItem('last_read_announcement_id');
            const currentLatestId = notiBtn.getAttribute('data-latest-id');
            if (lastReadId === currentLatestId) {
                notiBadge.style.display = 'none';
            }
        }

        notiBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 💡 點擊鈴鐺看公告的當下：把目前的最新公告 ID 刻進手機記憶體
            if (notiBadge) {
                const currentLatestId = notiBtn.getAttribute('data-latest-id');
                localStorage.setItem('last_read_announcement_id', currentLatestId);
                
                // 讓小紅點優雅地淡出消失
                notiBadge.style.transition = 'opacity 0.3s ease';
                notiBadge.style.opacity = '0';
                setTimeout(() => { notiBadge.style.display = 'none'; }, 300);
            }

            if (notiMenu.classList.contains('show')) {
                notiMenu.classList.remove('show');
            } else {
                notiMenu.classList.add('show');
            }
        });

        notiMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        document.addEventListener('click', function () {
            notiMenu.classList.remove('show');
        });
    }
});
</script>

<?php include('footer.php'); ?>