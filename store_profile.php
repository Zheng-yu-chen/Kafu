<?php
session_start();
include('db.php');
include('header.php');

// 權限檢查：只有店家 (role_id 2) 或是管理員 (role_id 1) 可以看
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 1)) {
    echo "<script>alert('無權限訪問店家後台！'); window.location.href='login.php';</script>";
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 動態辨識登入的身分
if ($_SESSION['role_id'] == 2) {
    if (!isset($_SESSION['r_id'])) {
        echo "<script>alert('帳號設定異常，未綁定餐廳！'); window.location.href='login.php';</script>";
        exit();
    }
    $store_id = intval($_SESSION['r_id']); 
} else {
    $store_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 1; 
}

// 預設變數防呆
$store_name = "未知餐廳";
$total_avg = 0.0;
$total_count = 0;
$chart_labels = [];
$chart_data = [];
$weekly_comments_count = 0; 
$reviews_result = null;
$hot_items_list = [];
$warning_items_list = []; 

try {
    // 1. 抓取餐廳名稱
    $res_query = $conn->query("SELECT name FROM restaurants WHERE r_id = $store_id");
    if ($res_query && $res_query->num_rows > 0) {
        $store_name = $res_query->fetch_assoc()['name'];
    }

    // 2. 滿意度趨勢 SQL（直接透過 items 的 r_id 連回餐廳，不再經過 categories）
    $sql_trend = "SELECT c.rating, c.created_at
                  FROM comments c
                  JOIN items i ON c.item_id = i.item_id
                  JOIN categories cat ON i.c_id = cat.c_id
                  WHERE cat.r_id = $store_id 
                  ORDER BY c.created_at DESC, c.com_id DESC
                  LIMIT 7";
                  
    $trend_result = $conn->query($sql_trend);
    $daily_scores = [];
    while ($row = $trend_result->fetch_assoc()) {
        $date_key = date('m/d', strtotime($row['created_at']));
        $daily_scores[$date_key][] = intval($row['rating']);
    }
    
    foreach ($daily_scores as $date => $ratings) {
        $chart_labels[] = $date; 
        $average = array_sum($ratings) / count($ratings); 
        $chart_data[] = round($average, 1);
    }

    if (empty($chart_labels)) {
        $chart_labels = ['無資料1', '無資料2', '無資料3'];
        $chart_data = [5, 4, 5]; 
    } else {
        $chart_labels = array_reverse($chart_labels);
        $chart_data = array_reverse($chart_data);
    }

    // 3. 總平均星等與總評論數
   $sql_avg = "SELECT AVG(c.rating) AS total_avg, COUNT(c.com_id) AS total_count
                FROM comments c
                JOIN items i ON c.item_id = i.item_id
                JOIN categories cat ON i.c_id = cat.c_id
                WHERE cat.r_id = $store_id";
    $avg_result = $conn->query($sql_avg);
    if ($avg_result && $row = $avg_result->fetch_assoc()) {
        $total_avg = $row['total_avg'] ? round(floatval($row['total_avg']), 1) : 0.0;
        $total_count = intval($row['total_count']);
        $weekly_comments_count = $total_count; 
    }

    // 4. 🔥 熱門餐點排行 SQL
    $sql_hot_ranking = "SELECT i.name AS item_name, 
                               IFNULL(AVG(c.rating), 0.0) AS avg_score, 
                               COUNT(c.com_id) AS click_count
                        FROM items i
                        LEFT JOIN comments c ON i.item_id = c.item_id
                        JOIN categories cat ON i.c_id = cat.c_id
                        WHERE cat.r_id = $store_id
                        GROUP BY i.item_id, i.name
                        ORDER BY avg_score DESC, click_count DESC
                        LIMIT 3";
    
    $hot_ranking_result = $conn->query($sql_hot_ranking);
    if ($hot_ranking_result && $hot_ranking_result->num_rows > 0) {
        while ($hot_row = $hot_ranking_result->fetch_assoc()) {
            $hot_items_list[] = [
                'item_name'  => $hot_row['item_name'],
                'item_score' => round(floatval($hot_row['avg_score']), 1),
                'item_count' => intval($hot_row['click_count'])
            ];
        }
    }

    // 5. 動態抓取「需要改進的餐點」
   $sql_warning = "SELECT i.name AS item_name, 
                           ROUND(AVG(c.rating), 1) AS avg_score,
                           GROUP_CONCAT(DISTINCT c.content SEPARATOR '、') AS short_issues
                    FROM items i
                    JOIN comments c ON i.item_id = c.item_id
                    JOIN categories cat ON i.c_id = cat.c_id
                    WHERE cat.r_id = $store_id
                    GROUP BY i.item_id, i.name
                    HAVING avg_score <= 3.5
                    ORDER BY avg_score ASC
                    LIMIT 2";
    $warning_result = $conn->query($sql_warning);
    if ($warning_result && $warning_result->num_rows > 0) {
        while ($warn_row = $warning_result->fetch_assoc()) {
            $warning_items_list[] = [
                'item_name' => $warn_row['item_name'],
                'avg_score' => $warn_row['avg_score'],
                'issue'     => $warn_row['short_issues'] ? mb_strimwidth($warn_row['short_issues'], 0, 50, "...") : "顧客評價較低"
            ];
        }
    }

    // 6. 最新評論列表
   $sql_reviews = "SELECT c.*, a.name AS reviewer_name, i.name AS food_name
                    FROM comments c
                    LEFT JOIN accounts a ON c.u_id = a.u_id
                    JOIN items i ON c.item_id = i.item_id
                    JOIN categories cat ON i.c_id = cat.c_id
                    WHERE cat.r_id = $store_id
                    ORDER BY c.created_at DESC";
    $reviews_result = $conn->query($sql_reviews);
    
} catch (mysqli_sql_exception $e) {
    $error_msg = "資料載入失敗，請稍後再試。";
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background-color: #f4f7f9; padding-bottom: 20px; font-family: sans-serif; }
    
    /* --- 統一的輔大藍頂部設計 --- */
    .profile-header { background-color: #002B5B; color: white; padding: 50px 20px 80px; display: flex; align-items: center; gap: 15px; position: relative; }
    .avatar-circle { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; overflow: hidden; flex-shrink: 0;}
    
    /* 確保 user-info 佔滿剩餘寬度，防止跑版 */
    .user-info { display: flex; flex-direction: column; gap: 6px; flex: 1; overflow: hidden; }
    .user-info h2 { margin: 0; font-size: 20px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-info p { margin: 0; font-size: 13px; opacity: 0.8; }
    .admin-badge { background: #FF8C42; color: white; font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: bold; align-self: flex-start; margin-bottom: 4px; }

    /* --- 新增公告按鈕 --- */
    .btn-publish-announcement {
        padding: 8px 16px; /* 🌟 加大了內距，讓按鈕變大 */
        background-color: #FF8C42; color: #ffffff; border: none;
        text-decoration: none; border-radius: 20px; font-size: 13px; /* 🌟 加大了字體 */
        font-weight: bold; transition: all 0.2s ease; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        white-space: nowrap; flex-shrink: 0; /* 防止按鈕被壓縮 */
    }
    .btn-publish-announcement:active { transform: scale(0.95); }

    /* --- 懸浮整合卡片 --- */
    .stats-card-combined { background: white; border-radius: 15px; padding: 20px; margin: -40px 20px 20px; position: relative; z-index: 10; box-shadow: 0 4px 15px rgba(0,0,0,0.08); display: flex; justify-content: space-around; text-align: center; }
    .stat-item { flex: 1; }
    .stat-val { font-size: 24px; font-weight: 900; color: #FF8C42; margin-bottom: 5px; }
    .stat-label { font-size: 12px; color: #888; font-weight: bold; line-height: 1.4; }
    .stat-divider { width: 1px; background: #eee; }

    /* --- 區塊設定 --- */
    .dashboard-section { background: #ffffff; border-radius: 12px; padding: 20px; margin: 0 20px 25px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
    .section-title { font-size: 16px; font-weight: 600; color: #333333; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    
    .ranking-list { display: flex; flex-direction: column; gap: 12px; }
    .ranking-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: #ffffff; border-radius: 10px; border: 1px solid #edf2f7; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .rank-left { display: flex; align-items: center; gap: 15px; }
    .rank-info h5 { margin: 0 0 4px 0; font-size: 15px; color: #2d3748; font-weight: 600; }
    .rank-info p { margin: 0; font-size: 12px; color: #718096; }
    .rank-score { font-weight: bold; color: #ffb300; font-size: 16px; background: #fff9db; padding: 4px 10px; border-radius: 20px; }

    .warning-item { background-color: #FFF8F8; border: 1px solid #FFCDD2; border-radius: 10px; padding: 15px; margin-bottom: 10px; }
    .warning-header { display: flex; justify-content: space-between; align-items: center; }
    .warning-header h5 { margin: 0; font-size: 15px; color: #333; }
    .warning-score { color: #F44336; font-weight: bold; font-size: 14px; }
    .warning-issue { color: #D32F2F; font-size: 13px; font-weight: bold; margin-top: 10px; }

    .review-item { border-bottom: 1px solid #f0f0f0; padding: 15px 0; }
    .review-item:last-child { border-bottom: none; }
    .review-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .review-stars { color: #FFC107; font-size: 12px; }
    .review-text { font-size: 13px; color: #555; line-height: 1.5; margin: 0; }
    
    .logout-section { text-align: center; margin: 30px 0 100px; }
    .logout-btn { display: inline-block; background-color: white; color: #F44336; border: 1.5px solid #FFCDD2; padding: 10px 40px; border-radius: 25px; text-decoration: none; font-size: 15px; font-weight: bold; }
</style>

<div class="mobile-wrapper">
    <div class="profile-header">
        <div class="avatar-circle">🏪</div>
        
        <div class="user-info">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h2><?php echo htmlspecialchars($store_name); ?></h2>
                <a href="publish_announcement.php" class="btn-publish-announcement">
                    <i class="fa-solid fa-bullhorn" style="margin-right: 4px;"></i>新增公告
                </a>
            </div>
            <p>店家營運中心</p>
        </div>
        
    </div>

    <div class="stats-card-combined">
        <div class="stat-item">
            <div class="stat-val">★ <?php echo number_format($total_avg, 1); ?></div>
            <div class="stat-label">總平均評分<br><span style="font-size:10px; font-weight:normal; color:#999;">(共 <?php echo $total_count; ?> 則)</span></div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-val" style="color: #002B5B;"><?php echo $weekly_comments_count; ?></div>
            <div class="stat-label">最新評論數<br><span style="font-size:10px; font-weight:normal; color:#999;">(動態同步)</span></div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-title"><span>📈</span> 滿意度趨勢</div>
        <canvas id="satisfactionChart" height="120"></canvas>
    </div>

    <div class="dashboard-section">
        <div class="section-title"><span style="color:#FF8C42">⭐</span> 本店熱門餐點排行</div>
        <div class="ranking-list">
            <?php if (!empty($hot_items_list)): ?>
                <?php foreach ($hot_items_list as $index => $item): ?>
                    <?php
                        $medals = ['🥇', '🥈', '🥉'];
                        $medal = $medals[$index] ?? '🔹';
                    ?>
                    <div class="ranking-item">
                        <div class="rank-left">
                            <span style="font-size:18px; margin-right:5px;"><?php echo $medal; ?></span>
                            <div class="rank-info">
                                <h5><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                <p>(<?php echo $item['item_count']; ?> 則評論)</p>
                            </div>
                        </div>
                        <div class="rank-score">
                            <?php echo number_format($item['item_score'], 1); ?> ★
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center;color:#999;padding:20px 0;">目前暫無餐點排行資料</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-title"><span style="color:#D32F2F">👎</span> 需要改進的餐點</div>
        <?php if (!empty($warning_items_list)): ?>
            <?php foreach ($warning_items_list as $warn): ?>
                <div class="warning-item">
                    <div class="warning-header">
                        <h5><?php echo htmlspecialchars($warn['item_name']); ?></h5>
                        <div class="warning-score">★ <?php echo $warn['avg_score']; ?></div>
                    </div>
                    <div class="warning-issue">常見意見：<?php echo htmlspecialchars($warn['issue']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;color:#999;padding:10px 0;">本店餐點表現良好，暫無需要改進的餐點！</p>
        <?php endif; ?>
    </div>

    <div class="dashboard-section">
        <div class="section-title"><span>💬</span> 最新評論</div>
        <div id="js-review-container">
            <style>
                #js-review-container .review-item { display: none; }
                #js-review-container .review-item:nth-child(1),
                #js-review-container .review-item:nth-child(2),
                #js-review-container .review-item:nth-child(3) { display: block; }
            </style>
            <?php if (isset($reviews_result) && $reviews_result->num_rows > 0): ?>
                <?php while($rev = $reviews_result->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="review-top">
                            <div>
                                <strong style="font-size:14px;"><?php echo htmlspecialchars($rev['reviewer_name'] ?? '匿名'); ?></strong>
                                <div style="font-size:11px; color:#999;"><?php echo date('Y-m-d', strtotime($rev['created_at'])); ?></div>
                            </div>
                            <div class="review-stars"><?php echo str_repeat('★', $rev['rating']); ?></div>
                        </div>
                        <div style="font-size:12px; color:#002B5B; font-weight:bold; margin-bottom:5px;"><?php echo htmlspecialchars($rev['food_name']); ?></div>
                        <p class="review-text"><?php echo htmlspecialchars($rev['content']); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#999; font-size:13px; padding:20px 0;">
                    <?php echo isset($error_msg) ? $error_msg : '目前尚無評價資料'; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="logout-section">
        <a href="logout.php" class="logout-btn">登出</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartLabels = <?php echo json_encode($chart_labels); ?>;
    const chartData = <?php echo json_encode($chart_data); ?>;
    const ctx = document.getElementById('satisfactionChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                borderColor: '#FF8C42',
                backgroundColor: 'rgba(255, 140, 66, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#FF8C42',
                fill: true,
                tension: 0.3
            }]
        },
        options: { 
            plugins: { legend: { display: false } }, 
            scales: { 
                y: { min: 0, max: 5, ticks: { stepSize: 1 } }, 
                x: { grid: { display: false } } 
            } 
        }
    });
</script>

<?php include('footer.php'); ?>