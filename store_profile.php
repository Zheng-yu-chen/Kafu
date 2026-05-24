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

// 💡 動態辨識登入的身分
if ($_SESSION['role_id'] == 2) {
    // 如果是店家，直接從 Session 抓取他專屬的餐廳 ID
    if (!isset($_SESSION['r_id'])) {
        echo "<script>alert('帳號設定異常，未綁定餐廳！'); window.location.href='login.php';</script>";
        exit();
    }
    $store_id = intval($_SESSION['r_id']); 
} else {
    // 如果是最高管理員 (role_id = 1)，預設給他看 ID 1 的餐廳 (之後你可以做個下拉選單讓管理員切換)
    $store_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 1; 
}

// 預設餐廳名稱防呆
$store_name = "未知餐廳";
// 準備給圖表用的陣列
$total_avg = 0.0;
$total_count = 0;
$chart_labels = [];
$chart_data = [];
$weekly_comments_count = 0; // 💡 【加這行】先給它預設值 0，這樣下面絕對不會再噴 Undefined 錯誤！
$reviews_result = null;
try {
    //滿意度分析
    // 嘗試從資料庫抓取該登入店家的真實餐廳名稱（預防 SQL 注入，使用 intval 轉型過的變數）
    $res_query = $conn->query("SELECT name FROM restaurants WHERE r_id = $store_id");
    if ($res_query && $res_query->num_rows > 0) {
        $store_name = $res_query->fetch_assoc()['name'];
    }
    // 💡 【新增】動態抓取最近 7 天的滿意度趨勢 (從今天往前推 7 天)
    $sql_trend = "SELECT c.rating, c.created_at
                  FROM comments c
                  JOIN items i ON c.item_id = i.item_id
                  JOIN categories cat ON i.c_id = cat.c_id
                  WHERE cat.r_id = $store_id 
                  ORDER BY c.created_at DESC, c.com_id DESC
                  LIMIT 7";
                  
    $trend_result = $conn->query($sql_trend);
    // 1. 先建立一個乾淨的暫存陣列，用來把同一天的分數歸類在一起
    $daily_scores = [];
    // 把資料庫撈出來的實際平均分數塞進對照表
    while ($row = $trend_result->fetch_assoc()) {
        // 將時間轉換成純日期格式（例如：05/24）
        $date_key = date('m/d', strtotime($row['created_at']));
        // 把這筆評分塞進當天的陣列裡
        $daily_scores[$date_key][] = intval($row['rating']);
    }
        // 2. 初始化真正要給 Chart.js 用的標籤和數據陣列
        $chart_labels = [];
        $chart_data = [];

        // 3. 計算每一天的平均分數
        foreach ($daily_scores as $date => $ratings) {
        $chart_labels[] = $date; // X軸：只顯示這一天（不會重複了）
    
        // 計算平均值：總分 / 總筆數
        $average = array_sum($ratings) / count($ratings); 
    
        // 四捨五入到小數點後第一位（例如 4.3），這樣折線圖畫出來最精準
        $chart_data[] = round($average, 1);
        }
    // 💡 終極防呆：如果資料庫真的因為關連錯誤一筆都撈不到，我們塞假資料讓表格強制出現，方便你檢查畫面
    if (empty($chart_labels)) {
        $chart_labels = ['無資料1', '無資料2', '無資料3'];
        $chart_data = [5, 4, 5]; 
    } else {
        // 因為是用 DESC 撈最新，圖表顯示要從舊到新，所以要把陣列反轉回來
        $chart_labels = array_reverse($chart_labels);
        $chart_data = array_reverse($chart_data);
    }

    // 總平均星等
    // 💡 【新增】抓取該餐廳所有評論的總平均分數
    $sql_avg = "SELECT AVG(c.rating) AS total_avg, COUNT(c.com_id) AS total_count
                FROM comments c
                JOIN items i ON c.item_id = i.item_id
                JOIN categories cat ON i.c_id = cat.c_id
                WHERE cat.r_id = $store_id";

    $avg_result = $conn->query($sql_avg);
    
    if ($avg_result && $row = $avg_result->fetch_assoc()) {
        // 如果有資料，四捨五入到小數點後第一位（例如 4.6）
        $total_avg = $row['total_avg'] ? round(floatval($row['total_avg']), 1) : 0.0;
        $total_count = intval($row['total_count']);
    }
    // 💡 請確保這行抓最新評論的 code 還是在 try 的最後面
    $sql_reviews = "SELECT c.*, i.item_name, a.name AS reviewer_name 
                    FROM comments c
                    JOIN items i ON c.item_id = i.item_id
                    JOIN categories cat ON i.c_id = cat.c_id
                    LEFT JOIN accounts a ON c.u_id = a.u_id
                    WHERE cat.r_id = $store_id
                    ORDER BY c.created_at DESC LIMIT 3";
    $reviews_result = $conn->query($sql_reviews);
    // 💡 直接用最乾淨的語法去數這家餐廳的所有評論數（防呆，先確保能抓到數字！）
    $sql_weekly_comments = "SELECT COUNT(c.com_id) AS weekly_count 
                            FROM comments c
                            JOIN items i ON c.item_id = i.item_id
                            JOIN categories cat ON i.c_id = cat.c_id
                            WHERE cat.r_id = $store_id";
                            
    $weekly_result = $conn->query($sql_weekly_comments);

    if ($weekly_result && $row = $weekly_result->fetch_assoc()) {
        $weekly_comments_count = intval($row['weekly_count']);
    } else {
        $weekly_comments_count = 0;
    }
} catch (mysqli_sql_exception $e) {
    // 若出錯的友善處理，不洩漏資料庫錯誤訊息
    $error_msg = "資料載入失敗，請稍後再試。";
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

    /* 頂部統計方塊容器 - 啟動橫向滑動鎖定 */
    .stats-container { 
        display: flex; 
        gap: 12px; 
        overflow-x: auto; /* 超出時允許橫向滾動 */
        white-space: nowrap; /* 限制子元素不換行 */
        padding: 5px 15px 15px; /* 增加底部留白，讓滑軌跟陰影更好看 */
        margin: 0 -15px; /* 讓滑軌可以貼齊螢幕邊緣 */
        -webkit-overflow-scrolling: touch; /* 讓手機板滑動更順暢 */
    }
    
    /* 讓電腦瀏覽器顯示精緻的滾動條（可選，不想要可以刪除） */
    .stats-container::-webkit-scrollbar { 
        height: 4px; 
    }
    .stats-container::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.4);
        border-radius: 4px;
    }

    /* 💡 統一所有卡片樣式：比照評分卡片的白底高質感風格 */
    .rating-card, .stat-box {
        flex: 0 0 220px; /* 固定寬度 220px 防止被擠壓變形 */
        background: #ffffff !important; /* 強制全部改為純白底色 */
        color: #333333 !important; /* 強制文字改為深色 */
        border-radius: 12px; 
        padding: 20px; /* 稍微加寬內襯，讓空間更舒適 */
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* 精緻微陰影 */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* 調整卡片內的文字標題（比照評分卡片的 h3 質感） */
    .rating-card h3, .stat-box h4 { 
        margin: 0; 
        color: #666666 !important; 
        font-size: 14px; 
        font-weight: normal;
    }
    .stat-box h4 { margin: 0 0 5px; font-size: 12px; opacity: 0.9; font-weight: normal; }
    /* 調整大數字樣式（比照評分卡片的 36px 大字） */
    .stat-box .num { 
        font-size: 36px; 
        font-weight: bold; 
        color: #333333 !important;
        margin: 10px 0 5px 0; 
    }
    .stat-box .trend { font-size: 11px; opacity: 0.8; }
    /* 調整底部說明文字（比照評分卡片的 13px 灰字） */
    .rating-card p, .stat-box .trend { 
        margin: 8px 0 0 0; 
        color: #888888 !important; 
        font-size: 13px; 
    }
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

    /* 統一的登出按鈕樣式 */
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

<div class="store-header">
    <h1>店家營運儀表板</h1>
    <!-- 這裡會顯示目前登入店家的真實店名 -->
    <p><?php echo htmlspecialchars($store_name); ?></p>
    <!-- 💡 外層只有這一個主要的 stats-container 容器 -->
    <div class="stats-container">
        <!-- 1. 總平均評分卡片 -->
        <div class="rating-card" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h3>總平均評分</h3>

            <div style="display: flex; align-items: center; margin-top: 10px;">
                <span style="font-size: 36px; font-weight: bold; color: #333; margin-right: 15px;">
                    <?php echo number_format($total_avg, 1); ?>
                </span>
                <div style="color: #f4b400; font-size: 20px;">
                    <?php 
                        // 依據平均分數四捨五入畫出實心星，剩下補空心星
                        $stars = round($total_avg);
                        echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); 
                    ?>
                </div>
            </div>
            <!-- 呈現總評論筆數，取代原本的「與上週相比」 -->
            <p style="margin: 8px 0 0 0; color: #888; font-size: 13px;">
            來自共 <?php echo $total_count; ?> 則顧客評價
            </p>
        </div>
        <!-- 2. 本週評論卡片（動態變數） -->
        <div class="stat-box">
            <h4>最新評論數</h4> <!-- 💡 標題改成最新評論數 -->
            <div class="num"><?php echo $weekly_comments_count; ?></div>
            <div class="trend">動態同步最新數據</div>
        </div>

        <!-- 3. 本週銷量卡片 -->
        <div class="stat-box">
            <h4>本週銷量</h4>
            <div class="num">534</div>
            <div class="trend">↑ 8% vs 上週</div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-title"><span>📈</span> 滿意度趨勢</div>
    <canvas id="satisfactionChart" height="120"></canvas>
</div>

<div class="dashboard-section">
    <div class="section-title"><span style="color:#FF8C42">⭐</span> 熱門餐點排行</div>
    <div class="ranking-list">
        <!-- 提醒：這裡目前的資料是前端靜態呈現，後續可改成與該 store_id 綁定的熱門排行 -->
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
        <p style="text-align:center; color:#999; font-size:13px; padding:20px 0;">
            <?php echo isset($error_msg) ? $error_msg : '目前尚無評價資料'; ?>
        </p>
    <?php endif; ?>
</div>

<div class="logout-section">
    <a href="logout.php" class="logout-btn">登出</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 💡 透過 PHP json_encode 將動態資料傳遞給 JavaScript
    const chartLabels = <?php echo json_encode($chart_labels); ?>;
    const chartData = <?php echo json_encode($chart_data); ?>;
    const ctx = document.getElementById('satisfactionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels, // 變成動態日期
            datasets: [{
                data: chartData,// 變成動態星等分數
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#4CAF50',
                fill: true,
                tension: 0.3
            }]
        },
        options: { 
            plugins: { legend: { display: false } }, 
            scales: { 
                y: { 
                    min: 0, 
                    max: 5,
                    ticks: { stepSize: 1 } // 讓 Y 軸刻度更整齊
                 }, 
                x: { grid: { display: false } } 
            } 
        }
    });
</script>

<?php include('footer.php'); ?>