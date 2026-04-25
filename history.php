<?php
session_start();
include('db.php');
include('header.php');

// 1. 🔒 登入檢查
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

// 3. 抓取歷史紀錄
$sql = "SELECT l.*, i.name as item_name, i.calories, i.protein 
        FROM consumptionlogs l 
        JOIN items i ON l.item_id = i.item_id 
        WHERE l.u_id = ? 
        ORDER BY l.recorded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$result = $stmt->get_result();

// 4. 依據日期分組
$logs_by_date = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['recorded_at']));
    $logs_by_date[$date][] = $row;
}
?>

<style>
    body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; padding-bottom: 80px; }
    
    /* 💡 統一的深藍色標題區塊樣式 */
    .header-section { 
        background-color: var(--fujen-blue, #002B5B); 
        color: white; padding: 30px 20px 20px; 
        position: relative; 
    }
    
    /* 💡 左上角返回按鈕：樣式完全對接 restaurant_detail.php */
    .back-btn { 
        color: white; text-decoration: none; font-size: 14px; 
        display: inline-block; margin-bottom: 15px; opacity: 0.9; 
    }
    
    .header-title h2 { margin: 0; font-size: 24px; color: white; }
    .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }

    .history-container { padding: 20px; max-width: 500px; margin: auto; }
    .date-label { font-weight: bold; color: #555; margin: 25px 0 10px; display: flex; justify-content: space-between; align-items: flex-end; }
    .day-total { font-size: 0.8em; color: #888; font-weight: normal; text-align: right; }
    
    .history-card { background: white; border-radius: 18px; padding: 5px 15px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 15px; }
    .log-row { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #f2f2f2; position: relative; }
    .log-row:last-child { border-bottom: none; }
    
    .icon-box { width: 42px; height: 42px; background: #f0f4f8; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.2em; flex-shrink:0; }
    .item-detail { flex: 1; overflow: hidden; }
    .item-title { font-weight: 600; color: var(--fujen-blue, #002B5B); margin-bottom: 2px; font-size: 1.05em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-info { font-size: 0.8em; color: #aaa; }
    
    .item-stats { text-align: right; margin-right: 5px; }
    .item-calories { font-weight: bold; color: #FF8C42; font-size: 1.1em; }
    .item-protein { font-size: 0.75em; color: #4CAF50; display: block; }
    
    .action-icons { display: flex; flex-direction: column; gap: 6px; margin-left: 10px; border-left: 1px solid #eee; padding-left: 12px; justify-content: center; }
    .btn-edit, .btn-delete { background: white; border: 1px solid #ddd; font-size: 11px; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: 0.2s; font-weight: bold; }
    .btn-edit { color: var(--fujen-blue, #002B5B); }
    .btn-delete { color: #F44336; }
    
    .progress-mini { width: 100%; height: 4px; background: #eee; border-radius: 2px; margin-top: 4px; overflow: hidden; }
    .progress-fill { height: 100%; background: #FF8C42; }

    /* 編輯彈窗樣式保持不變 */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; justify-content: center; align-items: center; padding: 20px; }
    .modal-box { background: white; width: 100%; max-width: 320px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .modal-header { background-color: var(--fujen-blue, #002B5B); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
</style>

<div class="header-section">
    <a href="profile.php" class="back-btn">❮ 返回個人檔案</a>
    <div class="header-title">
        <h2>🗓️ 飲食歷史</h2>
        <p>回顧與管理您的營養數據</p>
    </div>
</div>

<div class="history-container">
    <?php if (empty($logs_by_date)): ?>
        <div style="text-align:center; padding:80px 20px; color:#ccc;">
            <div style="font-size:3em; margin-bottom:10px;">🍽️</div>
            <p>目前尚無紀錄</p>
        </div>
    <?php endif; ?>

    <?php foreach ($logs_by_date as $date => $items): 
        $day_sum = array_sum(array_column($items, 'calories'));
        $percent = ($goal_cal > 0) ? min(100, round(($day_sum / $goal_cal) * 100)) : 0;
    ?>
    <div class="date-label">
        <span><?php echo ($date == date('Y-m-d')) ? "今天" : $date; ?></span>
        <div class="day-total">
            今日：<?php echo $day_sum; ?> / <?php echo $goal_cal; ?> kcal
            <div class="progress-mini"><div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
        </div>
    </div>
    
    <div class="history-card">
        <?php foreach ($items as $log): ?>
        <div class="log-row">
            <div class="icon-box">
                <?php $icons = [1=>'🍳', 2=>'🍱', 3=>'🍲', 4=>'🍰', 0=>'🍴']; echo $icons[$log['daily_meal']] ?? '🍴'; ?>
            </div>
            <div class="item-detail">
                <div class="item-title"><?php echo htmlspecialchars($log['item_name']); ?></div>
                <div class="item-info">
                    <?php echo date('H:i', strtotime($log['recorded_at'])); ?> • 
                    <?php $meals = [1=>'早餐', 2=>'午餐', 3=>'晚餐', 4=>'點心', 0=>'其他']; echo $meals[$log['daily_meal']] ?? '其他'; ?>
                </div>
            </div>
            <div class="item-stats">
                <span class="item-calories"><?php echo $log['calories']; ?> <small>kcal</small></span>
                <span class="item-protein">蛋白質 <?php echo $log['protein']; ?>g</span>
            </div>
            <div class="action-icons">
                <button class="btn-edit" onclick="openEditModal(<?php echo $log['log_id']; ?>, '<?php echo htmlspecialchars($log['item_name']); ?>', '<?php echo $date; ?>', <?php echo $log['daily_meal']; ?>)">編輯</button>
                <button class="btn-delete" onclick="deleteLog(<?php echo $log['log_id']; ?>)">刪除</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div><h2>編輯紀錄</h2><p id="modalItemName">餐點名稱</p></div>
            <button class="close-btn" onclick="closeEditModal()" style="background:none; border:none; color:white; font-size:24px;">×</button>
        </div>
        <form action="edit_history.php" method="POST">
            <input type="hidden" name="log_id" id="editLogId">
            <div style="padding:20px;">
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">更改日期</label>
                    <input type="date" name="eat_date" id="editDate" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;" required>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">更改時段</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <label><input type="radio" name="daily_meal" value="1" id="meal_1"> 早餐</label>
                        <label><input type="radio" name="daily_meal" value="2" id="meal_2"> 午餐</label>
                        <label><input type="radio" name="daily_meal" value="3" id="meal_3"> 晚餐</label>
                        <label><input type="radio" name="daily_meal" value="4" id="meal_4"> 點心</label>
                    </div>
                </div>
                <button type="submit" style="width:100%; background:#4CAF50; color:white; border:none; padding:15px; border-radius:8px; font-weight:bold;">儲存變更</button>
            </div>
        </form>
    </div>
</div>

<script>
    function deleteLog(logId) {
        if (confirm('確定要刪除這筆紀錄嗎？')) { window.location.href = 'delete_history.php?log_id=' + logId; }
    }
    function openEditModal(logId, itemName, dateStr, mealVal) {
        document.getElementById('editLogId').value = logId;
        document.getElementById('modalItemName').innerText = itemName;
        document.getElementById('editDate').value = dateStr;
        let radio = document.getElementById('meal_' + (mealVal || 2));
        if (radio) radio.checked = true;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
</script>

<?php include('footer.php'); ?>