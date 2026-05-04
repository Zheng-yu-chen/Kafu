<?php
session_start();
include('db.php');
include('header.php');

if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('請先登入'); window.location.href='login.php';</script>";
    exit();
}

$u_id = $_SESSION['u_id'];

// 取得目標熱量
$goal_cal = 2000; 
$user_res = $conn->query("SELECT goal_cal FROM accounts WHERE u_id = $u_id");
if ($user_res && $row = $user_res->fetch_assoc()) {
    $goal_cal = $row['goal_cal'] ?? 2000;
}

// 確保抓取 items 表中的 price, fat, carbs 以計算總和與單項顯示
$sql = "SELECT l.*, i.name as item_name, i.price, i.fat, i.carbs 
        FROM consumptionlogs l 
        LEFT JOIN items i ON l.item_id = i.item_id 
        WHERE l.u_id = ? 
        ORDER BY l.recorded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$result = $stmt->get_result();

$logs_by_date = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['recorded_at']));
    $logs_by_date[$date][] = $row;
}
?>

<style>
    body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; padding-bottom: 80px; }
    .header-section { background-color: #002B5B; color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }
    .header-title { display: flex; justify-content: space-between; align-items: center; }
    .header-title h2 { margin: 0; font-size: 24px; color: white; }
    .btn-manual { background: #FF8C42; color: white; border: none; padding: 8px 16px; border-radius: 12px; font-weight: bold; cursor: pointer; }
    
    .history-container { padding: 20px; max-width: 500px; margin: auto; }
    .date-label { font-weight: bold; color: #555; margin: 25px 0 10px; display: flex; justify-content: space-between; align-items: flex-end; }
    .history-card { background: white; border-radius: 18px; padding: 15px 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
    
    /* 縮小版的每日總計區塊 */
    .summary-main-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
    .total-price { font-size: 16px; font-weight: bold; color: #E53935; } 
    .total-cal { color: var(--primary-orange, #FF8C42); font-size: 20px; font-weight: 900; line-height: 1; }
    .total-cal small { font-size: 12px; font-weight: normal; margin-left: 4px; color: #888; }
    
    .summary-macro-row { display: flex; justify-content: space-between; background: #f9f9f9; padding: 8px 0; border-radius: 8px; margin-top: 8px; }
    .macro-item { text-align: center; flex: 1; }
    .macro-label { display: block; font-size: 10px; color: #888; margin-bottom: 2px; }
    .macro-val { font-size: 12px; font-weight: bold; color: #333; }
    
    /* 將三大營養素的顏色統一換成健康綠色 #4CAF50 */
    .macro-pro { color: #4CAF50; border-right: 1px solid #ddd; }
    .macro-fat { color: #4CAF50; border-right: 1px solid #ddd; }
    .macro-carbs { color: #4CAF50; }
    
    .progress-mini { width: 100%; height: 5px; background: #eee; border-radius: 3px; margin-top: 4px; overflow: hidden; }
    .progress-fill { height: 100%; background: #4CAF50; }

    /* 單筆紀錄列表樣式 */
    .log-row { display: flex; justify-content: space-between; align-items: stretch; padding: 15px 0; border-bottom: 1px solid #f2f2f2; }
    .log-row:last-child { border-bottom: none; padding-bottom: 0; }
    .item-detail { flex: 1; padding-left: 5px; display: flex; flex-direction: column; justify-content: center; }
    .item-title { font-weight: 600; color: #002B5B; margin-bottom: 2px; font-size: 1.05em; }
    .item-info { font-size: 0.8em; color: #aaa; margin-bottom: 4px; }
    
    /* 單品三大營養素 */
    .item-macros { display: flex; gap: 8px; font-size: 11px; font-weight: bold; }
    
    .item-stats { display: flex; align-items: baseline; justify-content: flex-end; gap: 8px; margin-right: 8px; margin-top: 6px;}
    .item-price { font-weight: bold; color: #E53935; font-size: 14px; }
    .item-calories { font-weight: bold; color: var(--primary-orange, #FF8C42); font-size: 18px; }
    
    .action-icons { display: flex; flex-direction: column; justify-content: center; gap: 6px; margin-left: 5px; border-left: 1px solid #eee; padding-left: 10px; }
    .btn-edit, .btn-delete { background: white; border: 1px solid #ddd; font-size: 11px; cursor: pointer; padding: 4px 8px; border-radius: 6px; font-weight: bold; transition: 0.2s; }
    .btn-edit { color: #2196F3; border-color: #bbdefb; }
    .btn-edit:hover { background: #e3f2fd; }
    .btn-delete { color: #f44336; border-color: #ffcdd2; }
    .btn-delete:hover { background: #ffebee; }
    
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; justify-content: center; align-items: center; padding: 20px; }
    .modal-box { background: white; width: 100%; max-width: 350px; border-radius: 18px; overflow: hidden; }
    .modal-header { background-color: #002B5B; color: white; padding: 20px; display: flex; justify-content: space-between; }
    .form-group { margin-bottom: 15px; }
    .form-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
</style>

<div class="header-section">
    <a href="profile.php" class="back-btn">❮ 返回個人檔案</a>
    <div class="header-title">
        <div>
            <h2>飲食歷史</h2>
            <p>回顧與管理您的營養數據</p>
        </div>
        <button class="btn-manual" onclick="openManualModal()">+ 手動輸入</button>
    </div>
</div>

<div class="history-container">
    <?php foreach ($logs_by_date as $date => $items): 
        // 計算每日的各項總和
        $day_cal = 0;
        $day_pro = 0;
        $day_price = 0;
        $day_fat = 0;
        $day_carbs = 0;
        
        foreach ($items as $log) {
            $day_cal += $log['total_calories'];
            $day_pro += $log['total_protein'];
            $day_price += $log['price'] ?? 0;
            $day_fat += $log['fat'] ?? 0;
            $day_carbs += $log['carbs'] ?? 0;
        }
        $percent = ($goal_cal > 0) ? min(100, round(($day_cal / $goal_cal) * 100)) : 0;
    ?>
    <div class="date-label">
        <span style="font-size: 16px;"><?php echo ($date == date('Y-m-d')) ? "今天" : $date; ?></span>
    </div>
    
    <div class="history-card">
        <!-- 日期總計區塊 -->
        <div style="border-bottom: 2px dashed #eee; padding-bottom: 12px; margin-bottom: 10px;">
            <div class="summary-main-row">
                <div class="total-price">$<?php echo floatval($day_price); ?></div>
                <div class="total-cal">🔥 <?php echo $day_cal; ?> <small>/ <?php echo $goal_cal; ?> kcal</small></div>
            </div>
            <div class="progress-mini"><div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
            <div class="summary-macro-row">
                <div class="macro-item macro-pro">
                    <span class="macro-label">蛋白質</span>
                    <span class="macro-val"><?php echo number_format($day_pro, 1); ?> g</span>
                </div>
                <div class="macro-item macro-fat">
                    <span class="macro-label">脂肪</span>
                    <span class="macro-val"><?php echo number_format($day_fat, 1); ?> g</span>
                </div>
                <div class="macro-item macro-carbs">
                    <span class="macro-label">碳水</span>
                    <span class="macro-val"><?php echo number_format($day_carbs, 1); ?> g</span>
                </div>
            </div>
        </div>

        <!-- 個別紀錄列表 (加入全品項營養素) -->
        <?php foreach ($items as $log): 
            $display_name = $log['item_id'] ? $log['item_name'] : $log['manual_item_name'];
        ?>
        <div class="log-row">
            <div class="item-detail">
                <div class="item-title"><?php echo htmlspecialchars($display_name); ?></div>
                <div class="item-info">
                    <?php echo date('H:i', strtotime($log['recorded_at'])); ?> • 
                    <?php $meals = [1=>'早餐', 2=>'午餐', 3=>'晚餐', 4=>'點心']; echo $meals[$log['daily_meal']] ?? '其他'; ?>
                </div>
                
                <!-- 單項餐點的營養素並排 -->
                <div class="item-macros">
                    <span style="color:#4CAF50;">蛋白質 <?php echo number_format($log['total_protein'], 1); ?>g</span>
                    <span style="color:#4CAF50;">脂肪 <?php echo number_format($log['fat'] ?? 0, 1); ?>g</span>
                    <span style="color:#4CAF50;">碳水 <?php echo number_format($log['carbs'] ?? 0, 1); ?>g</span>
                </div>
                
                <!-- 單項餐點的價格與熱量並排 -->
                <div class="item-stats">
                    <span class="item-price">$<?php echo floatval($log['price'] ?? 0); ?></span>
                    <span class="item-calories">🔥 <?php echo (int)$log['total_calories']; ?> <small style="font-size: 12px;">kcal</small></span>
                </div>
            </div>

            <!-- 編輯/刪除按鈕 -->
            <div class="action-icons">
                <!-- 💡 點擊編輯按鈕時，把脂肪和碳水的值也傳遞過去 -->
                <button class="btn-edit" onclick="openEditModal(
                    <?php echo $log['log_id']; ?>, 
                    '<?php echo htmlspecialchars($display_name); ?>', 
                    '<?php echo $date; ?>', 
                    <?php echo $log['daily_meal']; ?>,
                    <?php echo $log['total_calories']; ?>,
                    <?php echo $log['total_protein']; ?>,
                    <?php echo $log['fat'] ?? 0; ?>,
                    <?php echo $log['carbs'] ?? 0; ?>
                )">編輯</button>
                <button class="btn-delete" onclick="deleteLog(<?php echo $log['log_id']; ?>)">刪除</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- ================= 💡 新增校外食物彈窗 (擴充版) ================= -->
<div id="manualModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div><h2>新增校外食物</h2><p>自行輸入營養數據</p></div>
            <button onclick="closeManualModal()" style="background:none; border:none; color:white; font-size:24px;">×</button>
        </div>
        <form action="add_manual_history.php" method="POST">
            <div style="padding:20px;">
                <div class="form-group">
                    <label>食物名稱</label>
                    <input type="text" name="food_name" class="form-input" placeholder="例如：巷口乾麵" required>
                </div>
                
                <!-- 💡 2x2 網格：加入脂肪和碳水輸入框 -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;" class="form-group">
                    <div><label>熱量 (kcal)</label><input type="number" name="calories" class="form-input" required></div>
                    <div><label>蛋白質 (g)</label><input type="number" step="0.1" name="protein" class="form-input" required></div>
                    <div><label>脂肪 (g)</label><input type="number" step="0.1" name="fat" class="form-input" value="0"></div>
                    <div><label>碳水 (g)</label><input type="number" step="0.1" name="carbs" class="form-input" value="0"></div>
                </div>
                
                <div class="form-group">
                    <label>用餐時段</label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
                        <label><input type="radio" name="daily_meal" value="1"> 早餐</label>
                        <label><input type="radio" name="daily_meal" value="2" checked> 午餐</label>
                        <label><input type="radio" name="daily_meal" value="3"> 晚餐</label>
                        <label><input type="radio" name="daily_meal" value="4"> 點心</label>
                    </div>
                </div>
                <button type="submit" style="width:100%; background:#FF8C42; color:white; border:none; padding:15px; border-radius:12px; font-weight:bold;">加入紀錄</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= 💡 編輯紀錄彈窗 (擴充版) ================= -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header" style="background-color: #2196F3;">
            <div><h2>編輯飲食紀錄</h2><p>修改已存在的數據</p></div>
            <button onclick="closeEditModal()" style="background:none; border:none; color:white; font-size:24px;">×</button>
        </div>
        <form action="edit_history.php" method="POST">
            <input type="hidden" name="log_id" id="edit_log_id">
            <div style="padding:20px;">
                <div class="form-group">
                    <label>食物名稱</label>
                    <input type="text" name="food_name" id="edit_food_name" class="form-input" required>
                </div>
                
                <!-- 💡 2x2 網格：加入脂肪和碳水輸入框 -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;" class="form-group">
                    <div><label>熱量 (kcal)</label><input type="number" name="calories" id="edit_calories" class="form-input" required></div>
                    <div><label>蛋白質 (g)</label><input type="number" step="0.1" name="protein" id="edit_protein" class="form-input" required></div>
                    <div><label>脂肪 (g)</label><input type="number" step="0.1" name="fat" id="edit_fat" class="form-input"></div>
                    <div><label>碳水 (g)</label><input type="number" step="0.1" name="carbs" id="edit_carbs" class="form-input"></div>
                </div>
                
                <div class="form-group">
                    <label>日期</label>
                    <input type="date" name="eat_date" id="edit_eat_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>用餐時段</label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
                        <label><input type="radio" name="daily_meal" value="1" id="edit_meal_1"> 早餐</label>
                        <label><input type="radio" name="daily_meal" value="2" id="edit_meal_2"> 午餐</label>
                        <label><input type="radio" name="daily_meal" value="3" id="edit_meal_3"> 晚餐</label>
                        <label><input type="radio" name="daily_meal" value="4" id="edit_meal_4"> 點心</label>
                    </div>
                </div>
                <button type="submit" style="width:100%; background:#2196F3; color:white; border:none; padding:15px; border-radius:12px; font-weight:bold;">儲存修改</button>
            </div>
        </form>
    </div>
</div>

<script>
    function deleteLog(logId) {
        if (confirm('確定要刪除這筆紀錄嗎？')) { window.location.href = 'delete_history.php?log_id=' + logId; }
    }
    
    function openManualModal() { document.getElementById('manualModal').style.display = 'flex'; }
    function closeManualModal() { document.getElementById('manualModal').style.display = 'none'; }
    
    // 💡 接收脂肪和碳水參數，並填入輸入框
    function openEditModal(logId, name, date, meal, cal, pro, fat, carbs) {
        document.getElementById('edit_log_id').value = logId;
        document.getElementById('edit_food_name').value = name;
        document.getElementById('edit_eat_date').value = date;
        document.getElementById('edit_calories').value = cal;
        document.getElementById('edit_protein').value = pro;
        document.getElementById('edit_fat').value = fat;
        document.getElementById('edit_carbs').value = carbs;
        
        if (document.getElementById('edit_meal_' + meal)) {
            document.getElementById('edit_meal_' + meal).checked = true;
        }
        
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            closeManualModal();
            closeEditModal();
        }
    }
</script>

<?php include('footer.php'); ?>