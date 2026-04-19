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

// 預設抓取資料庫第一家餐廳 (r_id = 1) 作為示範
$store_id = 1; 
$store_name = "澳門華記";

try {
    // 嘗試從資料庫抓取真實餐廳名稱
    $res_query = $conn->query("SELECT name FROM restaurants WHERE r_id = $store_id");
    if ($res_query && $res_query->num_rows > 0) {
        $store_name = $res_query->fetch_assoc()['name'];
    }

    // 💡 從資料庫抓取這家餐廳的「真實最新評論」
    $sql_reviews = "SELECT c.*, i.item_name, a.name AS reviewer_name 
                    FROM comments c
                    JOIN items i ON c.item_id = i.item_id
                    JOIN categories cat ON i.c_id = cat.c_id
                    LEFT JOIN accounts a ON c.u_id = a.u_id
                    WHERE cat.r_id = $store_id AND c.status = 1
                    ORDER BY c.created_at DESC LIMIT 3";
    $reviews_result = $conn->query($sql_reviews);

} catch (mysqli_sql_exception $e) {
    // 若出錯則略過
}
?>

<style>
    body { background-color: #f4f7f9; padding-bottom: 20px; }

    /* 頂部綠色營運儀表板 */
    .store-header {
        background: linear-gradient(135deg, #4CAF50, #388E3C);
        color: white; padding: 40px 20px 60px;
        position: relative;
    }
    .store-header h1 { margin: 0; font-size: 24px; letter-spacing: 1px; }
    .store-header p { margin: 5px 0 20px; font-size: 14px; opacity: 0.9; }

    /* 頂部統計方塊 */
    .stats-container { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
    .stats-container::-webkit-scrollbar { display: none; }
    .stat-box {
        flex: 1; min-width: 100px; background: rgba(255, 255, 255, 0.2); 
        border-radius: 12px; padding: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .stat-box h4 { margin: 0 0 5px; font-size: 12px; opacity: 0.9; font-weight: normal; }
    .stat-box .num { font-size: 24px; font-weight: bold; margin: 0 0 5px; }
    .stat-box .trend { font-size: 11px; opacity: 0.8; }

    /* 白色內容區塊 */
    .dashboard-section {
        background: white; border-radius: 15px; margin: -20px 15px 20px;
        padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative; z-index: 10;
    }
    .dashboard-section + .dashboard-section { margin-top: 20px; }
    .section-title { font-size: 16px; font-weight: bold; color: #002B5B; margin: 0 0 15px; display: flex; align-items: center; gap: 8px; }

    /* 排行榜 */
    .ranking-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border: 1px solid #f0f0f0; border-radius: 10px; margin-bottom: 10px; }
    .rank-left { display: flex; align-items: center; gap: 15px; }
    .rank-badge { width: 32px; height: 32px; background: var(--primary-orange, #FF8C42); color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 14px; }
    .rank-info h5 { margin: 0; font-size: 15px; color: #333; }
    .rank-info p { margin: 4px 0 0; font-size: 12px; color: #888; }
    .rank-score { color: var(--primary-orange, #FF8C42); font-weight: bold; font-size: 15px; }

    /* 需要改進卡片 */
    .warning-item { background-color: #FFF8F8; border: 1px solid #FFCDD2; border-radius: 10px; padding: 15px; margin-bottom: 10px; }
    .warning-header { display: flex; justify-content: space-between; align-items: center; }
    .warning-header h5 { margin: 0; font-size: 15px; color: #333; }
    .warning-score { color: #F44336; font-weight: bold; font-size: 14px; }
    .warning-issue { color: #D32F2F; font-size: 13px; font-weight: bold; margin-top: 10px; }

    /* 評論列表 */
    .review-item { border-bottom: 1px solid #f0f0f0; padding: 15px 0; }
    .review-item:last-child { border-bottom: none; }
    .review-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .review-stars { color: #FFC107; font-size: 12px; }
    .review-text { font-size: 13px; color: #555; line-height: 1.5; margin: 0; }

    /* 💡 底部登出按鈕 */
    .logout-section { margin: 10px 15px 100px; }
    .logout-btn {
        background: white; display: flex; align-items: center; justify-content: center;
        padding: 16px; border-radius: 12px; color: #FF4D4D; text-decoration: none;
        font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.05); gap: 10px; border: 1px solid #FFEBEB;
    }
    .logout-btn:active { background: #FFF5F5; transform: scale(0.98); }
</style>

<div class="store-header">
    <h1>店家營運儀表板</h1>
    <p><?php echo htmlspecialchars($store_name); ?></p>
    
    <div class="stats-container">
        <div class="stat-box"><h4>平均評分</h4><div class="num">4.7</div><div class="trend">↑ 0.1 vs 上週</div></div>
        <div class="stat-box"><h4>本週評論</h4><div class="num">87</div><div class="trend">↑ 12 vs 上週</div></div>
        <div class="stat-box"><h4>本週銷量</h4><div class="num">534</div><div class="trend">↑ 8% vs 上週</div></div>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-title"><span>📈</span> 滿意度趨勢</div>
    <canvas id="satisfactionChart" height="120"></canvas>
</div>

<div class="dashboard-section">
    <div class="section-title"><span style="color:#FF8C42">⭐</span> 熱門餐點排行</div>
    <div class="ranking-list">
        <div class="ranking-item">
            <div class="rank-left"><div class="rank-badge">1</div><div class="rank-info"><h5>華記招牌飯</h5><p>本週銷售 156 份</p></div></div>
            <div class="rank-score">★ 4.8</div>
        </div>
        <div class="ranking-item">
            <div class="rank-left"><div class="rank-badge" style="background:#FFA726;">2</div><div class="rank-info"><h5>蜜汁叉燒飯</h5><p>本週銷售 128 份</p></div></div>
            <div class="rank-score">★ 4.6</div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-title"><span style="color:#D32F2F">👎</span> 需要改進的餐點</div>
    <div class="warning-item">
        <div class="warning-header"><h5>乾炒牛河</h5><div class="warning-score">★ 3.2</div></div>
        <div class="warning-issue">常見問題：太乾、份量少</div>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-title"><span>💬</span> 最新評論</div>
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
                <div style="font-size:12px; color:#002B5B; font-weight:bold; margin-bottom:5px;"><?php echo htmlspecialchars($rev['item_name']); ?></div>
                <p class="review-text"><?php echo htmlspecialchars($rev['content']); ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color:#999; font-size:13px; padding:20px 0;">目前尚無評價資料</p>
    <?php endif; ?>
</div>

<div class="logout-section">
    <a href="logout.php" class="logout-btn">登出</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('satisfactionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['04/05', '04/06', '04/07', '04/08', '04/09', '04/10', '04/11'],
            datasets: [{
                data: [4.2, 4.4, 4.3, 4.6, 4.5, 4.7, 4.6],
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#4CAF50',
                fill: true,
                tension: 0.3
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 5 }, x: { grid: { display: false } } } }
    });
</script>

<?php include('footer.php'); ?>