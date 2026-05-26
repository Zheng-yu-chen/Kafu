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
$sql = "SELECT l.*, i.name as item_name, 
        CASE 
            WHEN l.item_id IS NULL THEN l.price
            ELSE (CASE WHEN l.price > 0 THEN l.price ELSE i.price END)
        END as final_price,
        CASE 
            WHEN l.item_id IS NULL THEN l.total_protein
            ELSE COALESCE(NULLIF(l.total_protein, 0), i.protein)
        END as final_protein,
        CASE 
            WHEN l.item_id IS NULL THEN l.total_fat
            ELSE COALESCE(NULLIF(l.total_fat, 0), i.fat)
        END as final_fat,
        CASE 
            WHEN l.item_id IS NULL THEN l.total_carbs
            ELSE COALESCE(NULLIF(l.total_carbs, 0), i.carbs)
        END as final_carbs
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

$date_summaries = [];
foreach ($logs_by_date as $date => $items) {
    $day_cal = 0;
    $day_pro = 0;
    $day_price = 0;
    $day_fat = 0;
    $day_carbs = 0;
    foreach ($items as $log) {
        $day_cal += $log['total_calories'];
        $day_pro += $log['final_protein'] ?? 0;
        $day_price += $log['final_price'];
        $day_fat += $log['final_fat'] ?? 0;
        $day_carbs += $log['final_carbs'] ?? 0;
    }
    $percent = ($goal_cal > 0) ? round(($day_cal / $goal_cal) * 100) : 0;
    $date_summaries[$date] = [
        'total_cal' => $day_cal,
        'total_pro' => $day_pro,
        'total_price' => $day_price,
        'total_fat' => $day_fat,
        'total_carbs' => $day_carbs,
        'percent' => $percent,
        'items' => count($items),
    ];
}

$selected_date = date('Y-m-d');
if (!isset($logs_by_date[$selected_date]) && !empty($logs_by_date)) {
    $selected_date = array_key_first($logs_by_date);
}
?>

<style>
    body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; padding-bottom: 80px; }
    .header-section { background-color: #002B5B; color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }
    .header-title { display: flex; justify-content: space-between; align-items: center; }
    .header-title h2 { margin: 0; font-size: 24px; color: white; }
    .btn-manual { background: #FF8C42; color: white; border: none; padding: 8px 16px; border-radius: 12px; font-weight: bold; cursor: pointer; }
    .history-container { padding: 20px; max-width: 900px; margin: auto; }
    .calendar-card { background: white; border-radius: 22px; padding: 8px; box-shadow: 0 3px 20px rgba(0,0,0,0.08); margin-bottom: 10px; }
    /* 讓標頭置中對齊 */
.calendar-header { 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    margin-bottom: 14px; 
}

.calendar-select {
    font-size: 20px;
    font-weight: 700;
    color: #002B5B;
    border: none;
    background: transparent;
    cursor: pointer;
    outline: none;
    font-family: inherit;
    padding: 2px 16px 2px 6px; /* 右邊留點空間放箭頭 */
    text-align: center;
    
    /* 核心：隱藏瀏覽器預設的醜箭頭 */
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    /* 使用內建的自訂向下箭頭 (▾) 作為背景圖，顏色採用與主題一致的深藍色 */
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='%23002B5B'><path d='M7 10l5 5 5-5z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 2px center; /* 讓箭頭靠右置中 */
    background-size: 12px;
}/* 滑鼠移上去時有微幅底色提示可以點擊 */
.calendar-select:hover {
    background: #eef2f7;
    border-radius: 8px;
}
    .calendar-title { font-size: 20px; font-weight: 700; color: #002B5B; }
    .calendar-nav button { background: #002B5B; color: white; border: none; border-radius: 12px; font-size: 14px; padding: 8px 14px; cursor: pointer; margin-left: 8px; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
    .calendar-weekday, .calendar-day { text-align: center; font-size: 11px; }
    .calendar-weekday { color: #777; font-weight: 700; }
    /* 更緊湊的日期方塊，避免長條狀 */
    .calendar-day { background: transparent; border-radius: 12px; min-height: 36px; padding: 2px 2px; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; cursor: pointer; transition: transform .1s, box-shadow .1s; }
    .calendar-day:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
    .calendar-day.inactive { visibility: hidden; pointer-events: none; }
    /* 不要整格橘色框，只靠小點提示有紀錄 */
    .calendar-day.has-entry { border: none; }
    /* 選取時把數字包成圓形深色標記，不要整格變色 */
    .calendar-day.selected { background: transparent; box-shadow: 0 6px 14px rgba(0,0,0,0.06); }
    .calendar-day .day-number { font-size: 13px; font-weight: 800; color: #222; display: block; margin-top: 2px; }
    .calendar-day.selected .day-number { background: #002B5B; color: #fff; width: 28px; height: 28px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 900; margin: 2px auto 2px; }
    .calendar-day .entry-dot { width: 6px; height: 6px; border-radius: 999px; background: #FF8C42; margin: 2px auto 0; }
    .history-card { background: white; border-radius: 18px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .history-summary-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
    .history-summary-row h3 { margin: 0; font-size: 20px; color: #002B5B; }
    .history-summary-row p { margin: 0; color: #666; }
    .summary-main-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
    .total-price { font-size: 18px; font-weight: bold; color: #E53935; }
    .total-cal { color: var(--primary-orange, #FF8C42); font-size: 24px; font-weight: 900; line-height: 1; }
    .total-cal small { font-size: 12px; font-weight: normal; margin-left: 4px; color: #fff; opacity: 0.9; }
    .progress-mini { width: 100%; height: 8px; background: rgba(255,255,255,0.3); border-radius: 4px; margin-top: 8px; overflow: hidden; }
    .progress-fill { height: 100%; background: rgba(255,255,255,0.92); }
    .summary-macro-row { display: flex; justify-content: space-between; background: #f9f9f9; padding: 14px 0; border-radius: 10px; margin-top: 16px; }
    .macro-item { text-align: center; flex: 1; }
    .macro-label { display: block; font-size: 11px; color: #888; margin-bottom: 4px; }
    .macro-val { font-size: 14px; font-weight: bold; color: #333; }
    .macro-pro, .macro-fat, .macro-carbs { color: #4CAF50; }
    .history-items { display: grid; gap: 14px; }
    .item-card { background: #ffffff; border-radius: 16px; padding: 18px; border: 1px solid #f2f2f2; }
    .item-card h4 { margin: 0 0 8px; font-size: 16px; color: #002B5B; }
    .item-meta { color: #777; font-size: 13px; margin-bottom: 12px; }
    .item-macros { display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; margin-bottom: 10px; }
    .item-macros span { background: #f5f9f6; border-radius: 999px; padding: 6px 10px; color: #4CAF50; }
    .item-stats { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
    .item-price { font-weight: bold; color: #E53935; }
    .item-calories { font-weight: bold; color: var(--primary-orange, #FF8C42); }
    .item-actions { display: flex; gap: 10px; margin-top: 12px; }
    .btn-edit, .btn-delete { background: white; border: 1px solid #ddd; font-size: 12px; cursor: pointer; padding: 8px 12px; border-radius: 10px; font-weight: bold; transition: 0.2s; }
    .btn-edit { color: #6aafe7; border-color: #bbdefb; }
    .btn-edit:hover { background: #e3f2fd; }
    .btn-delete { color: #f44336; border-color: #ffcdd2; }
    .btn-delete:hover { background: #ffebee; }
    @media (max-width: 720px) {
        .history-container { padding: 16px; }
        .calendar-grid { gap: 5px; }
        .calendar-day { min-height: 34px; padding: 2px 2px; }
    }
    
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
    .btn-edit { color: #6aafe7; border-color: #bbdefb; }
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
   <div class="calendar-card">
    <div class="calendar-header">
        <div class="calendar-title">
            <select id="selectYear" class="calendar-select"></select> 年
            <select id="selectMonth" class="calendar-select"></select> 月
        </div>
    </div>
    <div class="calendar-grid" id="calendarGrid">
        <div class="calendar-weekday">日</div>
        <div class="calendar-weekday">一</div>
        <div class="calendar-weekday">二</div>
        <div class="calendar-weekday">三</div>
        <div class="calendar-weekday">四</div>
        <div class="calendar-weekday">五</div>
        <div class="calendar-weekday">六</div>
    </div>
</div>

    <div class="history-card" id="selectedDayCard">
        <div class="history-summary-row">
            <div>
                <h3 id="selectedDayTitle"></h3>
                <p id="selectedDaySubtitle"></p>
            </div>
        </div>

        <div class="summary-main-row">
            <div class="total-price" id="selectedTotalPrice"></div>
            <div class="total-cal" id="selectedTotalCal"></div>
        </div>
        <div class="progress-mini"><div class="progress-fill" id="selectedProgress"></div></div>
        <div class="summary-macro-row">
            <div class="macro-item macro-pro">
                <span class="macro-label">蛋白質</span>
                <span class="macro-val" id="selectedTotalPro"></span>
            </div>
            <div class="macro-item macro-fat">
                <span class="macro-label">脂肪</span>
                <span class="macro-val" id="selectedTotalFat"></span>
            </div>
            <div class="macro-item macro-carbs">
                <span class="macro-label">碳水</span>
                <span class="macro-val" id="selectedTotalCarbs"></span>
            </div>
        </div>

        <div class="history-items" id="historyItems"></div>
    </div>
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
                    <div><label>熱量 (kcal)</label><input type="number" name="calories" class="form-input" value="0"min="0"></div>
                    <div><label>蛋白質 (g)</label><input type="number" step="0.1" name="protein" class="form-input" value="0.0" min="0"></div>
                    <div><label>脂肪 (g)</label><input type="number" step="0.1" name="fat" class="form-input" value="0.0" min="0"></div>
                    <div><label>碳水 (g)</label><input type="number" step="0.1" name="carbs" class="form-input" value="0.0" min="0"></div>
                    <div style="grid-column: span 2;"><label>價錢 ($)</label><input type="number" name="price" id="edit_price" class="form-input" placeholder="請輸入金額" min="0"></div>
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
        <div class="modal-header" style="background-color: #6aafe7;">
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
                    <div><label>熱量 (kcal)</label><input type="number" name="calories" id="edit_calories" class="form-input"min="0" ></div>
                    <div><label>蛋白質 (g)</label><input type="number" step="0.1" name="protein" id="edit_protein" class="form-input"min="0" ></div>
                    <div><label>脂肪 (g)</label><input type="number" step="0.1" name="fat" id="edit_fat" class="form-input"min="0"></div>
                    <div><label>碳水 (g)</label><input type="number" step="0.1" name="carbs" id="edit_carbs" class="form-input"min="0"></div>
                    <div style="grid-column: span 2;"><label>價錢 ($)</label><input type="number" name="price" id="edit_final_price" class="form-input" placeholder="請輸入金額"min="0"></div>
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
                <button type="submit" style="width:100%; background:#6aafe7; color:white; border:none; padding:15px; border-radius:12px; font-weight:bold;">儲存修改</button>
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
 // 🌟 修正後的編輯彈窗給值邏輯：確保變數順序與傳入參數完全一致
function openEditModal(logId, name, date, meal, cal, pro, fat, carbs, price) {
    document.getElementById('edit_log_id').value = logId;
        document.getElementById('edit_food_name').value = name || '';
        document.getElementById('edit_eat_date').value = date;
        
        document.getElementById('edit_calories').value = (cal !== null && cal !== undefined) ? cal : 0;
        document.getElementById('edit_protein').value = (pro !== null && pro !== undefined) ? parseFloat(Math.max(0, pro)).toFixed(1) : '0.0';
document.getElementById('edit_fat').value = (fat !== null && fat !== undefined) ? parseFloat(Math.max(0, fat)).toFixed(1) : '0.0';
document.getElementById('edit_carbs').value = (carbs !== null && carbs !== undefined) ? parseFloat(Math.max(0, carbs)).toFixed(1) : '0.0';
        document.getElementById('edit_final_price').value = (price !== null && price !== undefined && parseFloat(price) > 0) ? Math.round(parseFloat(price)) : '';
        if (document.getElementById('edit_meal_' + meal)) {
            document.getElementById('edit_meal_' + meal).checked = true;
        }
    document.getElementById('editModal').style.display = 'flex';
}
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

    const logSummaries = <?php echo json_encode($date_summaries); ?>;
    const logsByDate = <?php echo json_encode($logs_by_date); ?>;
    const goalCal = <?php echo json_encode((int)$goal_cal); ?>;
    let currentYear = <?php echo (int)date('Y', strtotime($selected_date)); ?>;
    let currentMonth = <?php echo (int)date('n', strtotime($selected_date)); ?>;
    let selectedDate = '<?php echo $selected_date; ?>';

    function formatChineseDate(dateString) {
        const parts = dateString.split('-');
        return `${parseInt(parts[1], 10)}月${parseInt(parts[2], 10)}日`;
    }

    function pad(value) {
        return value.toString().padStart(2, '0');
    }

    // 1. 原本的 changeCalendarMonth 函數可以整段刪除，換成底下的監聽處理函數：
    function handleSelectChange() {
        currentYear = parseInt(document.getElementById('selectYear').value, 10);
        currentMonth = parseInt(document.getElementById('selectMonth').value, 10);
        
        renderCalendar(currentYear, currentMonth);
        
        // 保留你原本切換月份時自動抓取該月第一天紀錄的邏輯
        const monthDates = Object.keys(logSummaries).filter(d => {
            const [y, m] = d.split('-').map(Number);
            return y === currentYear && m === currentMonth;
        }).sort();
        
        if (monthDates.length > 0) {
            selectDay(monthDates[0]);
        } else {
            selectedDate = null;
            updateSelectedDay();
        }
    }

    // 新增：初始化年份與月份的選項（此處年份預設生成 2020 到 2035 年）
    function initCalendarSelects() {
        const selectYear = document.getElementById('selectYear');
        const selectMonth = document.getElementById('selectMonth');

        // 生成年份
        selectYear.innerHTML = '';
        for (let y = 2020; y <= 2035; y++) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            selectYear.appendChild(opt);
        }

        // 生成月份
        selectMonth.innerHTML = '';
        for (let m = 1; m <= 12; m++) {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            selectMonth.appendChild(opt);
        }

        // 綁定變更事件
        selectYear.addEventListener('change', handleSelectChange);
        selectMonth.addEventListener('change', handleSelectChange);
    }

    function renderCalendar(year, month) {
        // 2. 將原先的 calendarTitle.textContent = ... 改為同步選單的值：
        document.getElementById('selectYear').value = year;
        document.getElementById('selectMonth').value = month;

        const calendarGrid = document.getElementById('calendarGrid');
        const firstDay = new Date(year, month - 1, 1);
        const firstWeekday = firstDay.getDay();
        const daysInMonth = new Date(year, month, 0).getDate();

        const cells = [];
        for (let i = 0; i < firstWeekday; i += 1) {
            cells.push({ empty: true });
        }
        for (let day = 1; day <= daysInMonth; day += 1) {
            const dateKey = `${year}-${pad(month)}-${pad(day)}`;
            const hasEntry = Boolean(logSummaries[dateKey]);
            cells.push({ day, dateKey, hasEntry });
        }
        while (cells.length % 7 !== 0) {
            cells.push({ empty: true });
        }

        const existingDays = Array.from(calendarGrid.querySelectorAll('.calendar-day'));
        existingDays.forEach(node => node.remove());

        cells.forEach(cell => {
            const dayEl = document.createElement('div');
            dayEl.className = 'calendar-day';
            if (cell.empty) {
                dayEl.classList.add('inactive');
                calendarGrid.appendChild(dayEl);
                return;
            }
            dayEl.dataset.dateKey = cell.dateKey;
            if (cell.hasEntry) { dayEl.classList.add('has-entry'); }
            if (cell.dateKey === selectedDate) { dayEl.classList.add('selected'); }

            const numberEl = document.createElement('div');
            numberEl.className = 'day-number';
            numberEl.textContent = cell.day;
            dayEl.appendChild(numberEl);

            if (cell.hasEntry) {
                const dotEl = document.createElement('div');
                dotEl.className = 'entry-dot';
                dayEl.appendChild(dotEl);
            }

            dayEl.style.cursor = 'pointer';
            dayEl.addEventListener('click', () => selectDay(cell.dateKey));
            calendarGrid.appendChild(dayEl);
        });
    }

    function selectDay(dateKey) {
        selectedDate = dateKey;
        const calendarCells = Array.from(document.querySelectorAll('.calendar-day'));
        calendarCells.forEach(cell => {
            if (cell.dataset.dateKey === dateKey) {
                cell.classList.add('selected');
            } else {
                cell.classList.remove('selected');
            }
        });
        updateSelectedDay(dateKey);
    }

    function updateSelectedDay(dateKey = selectedDate) {
        const selectedDayTitle = document.getElementById('selectedDayTitle');
        const selectedDaySubtitle = document.getElementById('selectedDaySubtitle');
        const selectedTotalPrice = document.getElementById('selectedTotalPrice');
        const selectedTotalCal = document.getElementById('selectedTotalCal');
        const selectedTotalPro = document.getElementById('selectedTotalPro');
        const selectedTotalFat = document.getElementById('selectedTotalFat');
        const selectedTotalCarbs = document.getElementById('selectedTotalCarbs');
        const selectedProgress = document.getElementById('selectedProgress');
        const historyItems = document.getElementById('historyItems');

        if (!dateKey || !logsByDate[dateKey]) {
            selectedDayTitle.textContent = '尚未選擇有紀錄的日期';
            selectedDaySubtitle.textContent = '請點擊有記錄的日期。';
            selectedTotalPrice.textContent = '$0';
            selectedTotalCal.textContent = '🔥 0 / ' + goalCal + ' kcal';
            selectedTotalPro.textContent = '0 g';
            selectedTotalFat.textContent = '0 g';
            selectedTotalCarbs.textContent = '0 g';
            selectedProgress.style.width = '0%';
            selectedProgress.style.backgroundColor = '#4CAF50'; // 重置為綠色
            historyItems.innerHTML = '<div class="item-card"><p style="color:#666; margin:0;">此日尚無飲食紀錄。</p></div>';
            return;
        }

        const summary = logSummaries[dateKey];
        selectedDayTitle.textContent = formatChineseDate(dateKey);
        selectedDaySubtitle.textContent = `共 ${summary.items} 筆紀錄，已達 ${summary.percent}%`; 
        selectedTotalPrice.textContent = '$' + parseFloat(summary.total_price).toFixed(0);
        selectedTotalCal.textContent = `🔥 ${summary.total_cal} / ${goalCal} kcal`;
        selectedTotalPro.textContent = parseFloat(summary.total_pro).toFixed(1) + ' g';
        selectedTotalFat.textContent = parseFloat(summary.total_fat).toFixed(1) + ' g';
        selectedTotalCarbs.textContent = parseFloat(summary.total_carbs).toFixed(1) + ' g';
        
        // 💡 根據百分比設定進度條顏色：綠、橘、紅
        let barColor = "#4CAF50"; // 預設綠色
        if (summary.percent >= 80 && summary.percent <= 100) {
            barColor = "#FF8C42"; // 橘色
        } else if (summary.percent > 100) {
            barColor = "#E53935"; // 紅色
        }
        selectedProgress.style.width = summary.percent + '%';
        selectedProgress.style.backgroundColor = barColor;

        historyItems.innerHTML = '';
        logsByDate[dateKey].forEach(log => {
            const itemName = log.item_id ? log.item_name : log.manual_item_name;
            const itemCard = document.createElement('div');
            itemCard.className = 'item-card';

            const titleEl = document.createElement('h4');
            titleEl.textContent = itemName;
            itemCard.appendChild(titleEl);

            const metaEl = document.createElement('div');
            metaEl.className = 'item-meta';
            const mealLabel = {1:'早餐',2:'午餐',3:'晚餐',4:'點心'}[log.daily_meal] || '其他';
            metaEl.textContent = `${new Date(log.recorded_at).toLocaleTimeString('zh-TW', {hour:'2-digit', minute:'2-digit'})} • ${mealLabel}`;
            itemCard.appendChild(metaEl);

            const macrosEl = document.createElement('div');
            macrosEl.className = 'item-macros';
            ['final_protein','final_fat','final_carbs'].forEach(key => {
                const span = document.createElement('span');
                const label = key === 'final_protein' ? '蛋白質' : (key === 'final_fat' ? '脂肪' : '碳水');
                span.textContent = `${label} ${parseFloat(log[key] || 0).toFixed(1)}g`;
                macrosEl.appendChild(span);
            });
            itemCard.appendChild(macrosEl);

            const statsEl = document.createElement('div');
            statsEl.className = 'item-stats';
            const priceEl = document.createElement('div');
            priceEl.className = 'item-price';
            priceEl.textContent = '$' + parseFloat(log.final_price || 0).toFixed(0);
            const caloriesEl = document.createElement('div');
            caloriesEl.className = 'item-calories';
            caloriesEl.textContent = `🔥 ${parseInt(log.total_calories || 0, 10)} kcal`;
            statsEl.appendChild(priceEl);
            statsEl.appendChild(caloriesEl);
            itemCard.appendChild(statsEl);

            const actionsEl = document.createElement('div');
            actionsEl.className = 'item-actions';
            const editBtn = document.createElement('button');
            editBtn.className = 'btn-edit';
            editBtn.type = 'button';
            editBtn.textContent = '編輯';
           // 尋找你程式碼中的 editBtn 點擊事件，將其內容完整替換成這樣：
editBtn.addEventListener('click', () => {
    openEditModal(
        log.log_id,
        itemName,
        dateKey,
        log.daily_meal,
        log.total_calories,
        log.final_protein, // 🌟 修正：把 total_protein 改成 final_protein
        log.final_fat,     // 確保使用 final_ 變數
        log.final_carbs,   // 確保使用 final_ 變數
        log.final_price
    );
});
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-delete';
            deleteBtn.type = 'button';
            deleteBtn.textContent = '刪除';
            deleteBtn.addEventListener('click', () => deleteLog(log.log_id));
            actionsEl.appendChild(editBtn);
            actionsEl.appendChild(deleteBtn);
            itemCard.appendChild(actionsEl);

            historyItems.appendChild(itemCard);
        });
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            closeManualModal();
            closeEditModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initCalendarSelects(); // 👈 先初始化下拉選單選項
        renderCalendar(currentYear, currentMonth);
        updateSelectedDay(selectedDate);
    });
</script>

<?php include('footer.php'); ?>