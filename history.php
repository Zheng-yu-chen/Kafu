<?php
session_start();
include('db.php');
include('header.php');

// 1. 🔒 登入檢查：確保只有登入者可以查看歷史紀錄
if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('請先登入'); window.location.href='login.php';</script>";
    exit();
}

$u_id = $_SESSION['u_id'];

// 2. 取得使用者設定的目標熱量
$goal_cal = 2000; 
$user_res = $conn->query("SELECT goal_cal FROM accounts WHERE u_id = $u_id");
if ($user_res && $row = $user_res->fetch_assoc()) {
    $goal_cal = $row['goal_cal'] ?? 2000;
}

// 3. 核心修正：使用 JOIN 確保只抓取學餐資料庫中存在的餐點
// 直接從 items 表格抓取 calories 與 protein，確保數據準確
$sql = "SELECT l.*, i.name as item_name, i.calories, i.protein 
        FROM consumptionlogs l 
        JOIN items i ON l.item_id = i.item_id 
        WHERE l.u_id = ? 
        ORDER BY l.recorded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$result = $stmt->get_result();

// 4. 將資料依據「日期」進行分組
$logs_by_date = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['recorded_at']));
    $logs_by_date[$date][] = $row;
}
?>

<style>
    body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
    .history-container { padding: 20px; max-width: 500px; margin: auto; }
    .history-header { margin-bottom: 25px; }
    .history-header h2 { color: #002B5B; margin-bottom: 5px; }
    .history-header p { color: #888; font-size: 0.9em; }

    .date-label { 
        font-weight: bold; color: #555; margin: 25px 0 10px; 
        display: flex; justify-content: space-between; align-items: flex-end; 
    }
    .day-total { font-size: 0.8em; color: #888; font-weight: normal; text-align: right; }
    
    /* 歷史紀錄卡片設計 */
    .history-card { background: white; border-radius: 18px; padding: 5px 15px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 15px; }
    .log-row { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #f2f2f2; }
    .log-row:last-child { border-bottom: none; }
    
    .icon-box { width: 42px; height: 42px; background: #f0f4f8; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.2em; }
    .item-detail { flex: 1; }
    .item-title { font-weight: 600; color: #002B5B; margin-bottom: 2px; font-size: 1.05em; }
    .item-info { font-size: 0.8em; color: #aaa; }
    
    .item-stats { text-align: right; }
    .item-calories { font-weight: bold; color: #FF8C42; font-size: 1.1em; }
    .item-protein { font-size: 0.75em; color: #4CAF50; display: block; }
    
    .progress-mini { width: 100%; height: 4px; background: #eee; border-radius: 2px; margin-top: 4px; overflow: hidden; }
    .progress-fill { height: 100%; background: #FF8C42; }

    .empty-state { text-align: center; padding: 80px 20px; color: #ccc; }
</style>

<div class="history-container">
    <div class="history-header">
        <h2>🗓️ 飲食歷史</h2>
        <p>回顧您在輔大校園的營養數據</p>
    </div>

    <?php if (empty($logs_by_date)): ?>
        <div class="empty-state">
            <div style="font-size: 3em; margin-bottom: 10px;">🍽️</div>
            <p>目前尚無紀錄<br>快去托盤搜尋餐點吧！</p>
        </div>
    <?php endif; ?>

    <?php foreach ($logs_by_date as $date => $items): 
        // 計算該日總熱量
        $day_sum = array_sum(array_column($items, 'calories'));
        $percent = ($goal_cal > 0) ? min(100, round(($day_sum / $goal_cal) * 100)) : 0;
    ?>
    <div class="date-label">
        <span><?php echo ($date == date('Y-m-d')) ? "今天" : $date; ?></span>
        <div class="day-total">
            今日：<?php echo $day_sum; ?> / <?php echo $goal_cal; ?> kcal
            <div class="progress-mini">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
    </div>
    
    <div class="history-card">
        <?php foreach ($items as $log): ?>
        <div class="log-row">
            <div class="icon-box">
                <?php 
                    // 根據用餐時段顯示圖示
                    $icons = [1=>'🍳', 2=>'🍱', 3=>'🍲', 4=>'🍰', 0=>'🍴'];
                    echo $icons[$log['daily_meal']] ?? '🍴';
                ?>
            </div>
            <div class="item-detail">
                <div class="item-title"><?php echo htmlspecialchars($log['item_name']); ?></div>
                <div class="item-info">
                    <?php echo date('H:i', strtotime($log['recorded_at'])); ?> • 
                    <?php 
                        $meals = [1=>'早餐', 2=>'午餐', 3=>'晚餐', 4=>'點心', 0=>'其他'];
                        echo $meals[$log['daily_meal']] ?? '其他';
                    ?>
                </div>
            </div>
            <div class="item-stats">
                <span class="item-calories"><?php echo $log['calories']; ?> <small>kcal</small></span>
                <span class="item-protein">蛋白質 <?php echo $log['protein']; ?>g</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div style="text-align: center; margin-top: 40px; padding-bottom: 20px;">
        <a href="profile.php" style="color:#002B5B; text-decoration:none; font-weight:bold;">← 返回個人中心</a>
    </div>
</div>

<?php include('footer.php'); ?>