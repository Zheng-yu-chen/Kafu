<?php
session_start();
include('db.php');
include('header.php'); 

if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('請先登入'); window.location.href='login.php';</script>";
    exit();
}

$u_id = $_SESSION['u_id'];

// =================================================================
// 🎯 後端邏輯：使用者在確認視窗按下「確定送出」時
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_record') {
    $food_name = trim($_POST['food_name'] ?? '');
    $calories  = (int)($_POST['calories'] ?? 0);
    $protein   = (float)($_POST['protein'] ?? 0.0);
    $fat       = (float)($_POST['fat'] ?? 0.0);
    $carbs     = (float)($_POST['carbs'] ?? 0.0);
    $price     = (float)($_POST['price'] ?? 0.0);
    $daily_meal = (int)($_POST['daily_meal'] ?? 2); 
    
    // 接收前端傳過來的自訂日期
    $input_date = $_POST['eat_date'] ?? date('Y-m-d');
    $recorded_at = $input_date . ' ' . date('H:i:s');

    if (!empty($food_name)) {
        $sql = "INSERT INTO consumptionlogs 
                (u_id, item_id, manual_item_name, total_calories, price, total_protein, total_fat, total_carbs, daily_meal, recorded_at) 
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiddddis", $u_id, $food_name, $calories, $price, $protein, $fat, $carbs, $daily_meal, $recorded_at);

        if ($stmt->execute()) {
            echo "<script>window.location.href='history.php';</script>";
            exit();
        } else {
            echo "<script>alert('系統寫入錯誤，請稍後再試');</script>";
        }
    }
}

// =================================================================
// 📊 撈出過去吃過的所有餐點
// =================================================================
$sql_history = "SELECT l.*, i.name as item_name, 
                CASE WHEN l.item_id IS NULL THEN l.price ELSE (CASE WHEN l.price > 0 THEN l.price ELSE i.price END) END as final_price,
                CASE WHEN l.item_id IS NULL THEN l.total_protein ELSE COALESCE(NULLIF(l.total_protein, 0), i.protein) END as final_protein,
                CASE WHEN l.item_id IS NULL THEN l.total_fat ELSE COALESCE(NULLIF(l.total_fat, 0), i.fat) END as final_fat,
                CASE WHEN l.item_id IS NULL THEN l.total_carbs ELSE COALESCE(NULLIF(l.total_carbs, 0), i.carbs) END as final_carbs
                FROM consumptionlogs l 
                LEFT JOIN items i ON l.item_id = i.item_id 
                WHERE l.u_id = ? 
                ORDER BY l.recorded_at DESC";
$stmt_hist = $conn->prepare($sql_history);
$stmt_hist->bind_param("i", $u_id);
$stmt_hist->execute();
$res_hist = $stmt_hist->get_result();

$unique_history_items = [];
while ($row = $res_hist->fetch_assoc()) {
    $display_name = $row['item_id'] ? $row['item_name'] : $row['manual_item_name'];
    
    if (!empty($display_name)) {
        $cal = (int)$row['total_calories'];
        $prc = (float)$row['final_price'];
        
        // 🎯 核心改進：唯一金鑰結合「名稱_價格_熱量」，允許相同品項、不同數值並存
        $composite_key = $display_name . '_' . $prc . '_' . $cal;
        
        if (!isset($unique_history_items[$composite_key])) {
            $unique_history_items[$composite_key] = [
                'name' => $display_name,
                'calories' => $cal,
                'protein' => (float)$row['final_protein'],
                'fat' => (float)$row['final_fat'],
                'carbs' => (float)$row['final_carbs'],
                'price' => $prc
            ];
        }
    }
}
?>

<style>
    body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; padding-bottom: 80px; }
    .header-section { background-color: #002B5B; color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }
    .header-title h2 { margin: 0; font-size: 24px; color: white; }
    
    .main-container { padding: 20px; max-width: 500px; margin: auto; }
    .search-card { background: white; border-radius: 18px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: bold; color: #002B5B; font-size: 14px; }
    .form-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; font-size: 14px; }
    
    .history-list { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; }
    .history-row-item { display: flex; justify-content: space-between; align-items: center; padding: 14px; background: #f9f9f9; border-radius: 14px; border: 1px solid #eee; }
    .history-row-info { display: flex; flex-direction: column; gap: 4px; }
    .history-row-name { font-weight: bold; color: #002B5B; font-size: 16px; }
    .history-row-meta { font-size: 12px; color: #666; line-height: 1.4; }
    .btn-row-add { background: #FF8C42; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; transition: 0.2s; }
    .btn-row-add:hover { background: #F57C00; }

    .confirm-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; padding: 20px; }
    .confirm-box { background: white; width: 100%; max-width: 360px; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 25px rgba(0,0,0,0.15); animation: zoomIn 0.2s ease-out; }
    .confirm-header { background-color: #002B5B; color: white; padding: 16px 20px; font-weight: bold; font-size: 18px; text-align: center; }
    .confirm-body { padding: 20px; font-size: 15px; color: #333; line-height: 1.6; }
    .confirm-data-row { display: flex; margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 8px; }
    .confirm-label { width: 90px; color: #777; font-weight: bold; }
    .confirm-value { flex: 1; color: #222; font-weight: bold; }
    .confirm-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 0 20px 20px; }
    .btn-confirm-cancel { background: #e0e0e0; color: #333; border: none; padding: 12px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; text-align: center; }
    .btn-confirm-submit { background: #FF8C42; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; text-align: center; }
    .btn-confirm-submit:hover { background: #F57C00; }
    
    @keyframes zoomIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<div class="header-section">
    <a href="history.php" class="back-btn">❮ 返回飲食歷史</a>
    <div class="header-title">
        <h2>依照紀錄新增</h2>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 13px;">挑選日期、時段，並點擊右側按鈕快速補登</p>
    </div>
</div>

<div class="main-container">
    <div class="search-card">
        <div class="form-group" style="margin-bottom: 15px;">
            <input type="text" id="historySearchInput" class="form-input" placeholder="🔍 搜尋歷史餐點...">
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="global_date">選擇補登日期：</label>
            <input type="date" id="global_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group" style="border-bottom: 1px dashed #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <label>選擇補登時段：</label>
            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 6px; font-size: 13px;">
                <label style="font-weight:normal;"><input type="radio" name="global_meal" value="1"> 早餐</label>
                <label style="font-weight:normal;"><input type="radio" name="global_meal" value="2" checked> 午餐</label>
                <label style="font-weight:normal;"><input type="radio" name="global_meal" value="3"> 晚餐</label>
                <label style="font-weight:normal;"><input type="radio" name="global_meal" value="4"> 點心</label>
            </div>
        </div>

        <form action="historyauto.php" method="POST" id="hiddenSubmitForm">
            <input type="hidden" name="action" value="add_record">
            <input type="hidden" name="food_name" id="post_food_name">
            <input type="hidden" name="calories" id="post_calories">
            <input type="hidden" name="protein" id="post_protein">
            <input type="hidden" name="fat" id="post_fat">
            <input type="hidden" name="carbs" id="post_carbs">
            <input type="hidden" name="price" id="post_price">
            <input type="hidden" name="daily_meal" id="post_daily_meal">
            <input type="hidden" name="eat_date" id="post_eat_date"> 
        </form>

        <div class="history-list" id="historyListContainer">
            <?php if (empty($unique_history_items)): ?>
                <p style="color:#888; text-align:center; margin: 20px 0;">尚無任何歷史足跡。</p>
            <?php else: ?>
                <?php foreach ($unique_history_items as $item): ?>
                    <div class="history-row-item" data-search-name="<?php echo htmlspecialchars(mb_strtolower($item['name'], 'UTF-8')); ?>">
                        <div class="history-row-info">
                            <div class="history-row-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="history-row-meta">
                                價錢: $<?php echo round($item['price']); ?> | 熱量: <?php echo $item['calories']; ?> kcal<br>
                                蛋: <?php echo number_format($item['protein'], 1); ?>g | 脂: <?php echo number_format($item['fat'], 1); ?>g | 碳: <?php echo number_format($item['carbs'], 1); ?>g
                            </div>
                        </div>
                        <button type="button" class="btn-row-add" onclick="openConfirmModal(
                            '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>',
                            <?php echo $item['calories']; ?>,
                            <?php echo $item['protein']; ?>,
                            <?php echo $item['fat']; ?>,
                            <?php echo $item['carbs']; ?>,
                            <?php echo $item['price']; ?>
                        )">新增</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="confirmModalOverlay" class="confirm-overlay">
    <div class="confirm-box">
        <div class="confirm-header">請確認以下紀錄：</div>
        <div class="confirm-body">
            
            <div class="confirm-data-row">
                <div class="confirm-label">日期</div>
                <div class="confirm-value" id="view_date" style="color: #002B5B;"></div>
            </div>
            <div class="confirm-data-row">
                <div class="confirm-label">用餐時段</div>
                <div class="confirm-value" id="view_meal" style="color: #FF8C42;"></div>
            </div>
            <div class="confirm-data-row">
                <div class="confirm-label">食物名稱</div>
                <div class="confirm-value" id="view_name"></div>
            </div>
            <div class="confirm-data-row">
                <div class="confirm-label">預估熱量</div>
                <div class="confirm-value" id="view_calories" style="color: #E53935;"></div>
            </div>
            <div class="confirm-data-row">
                <div class="confirm-label">餐點價格</div>
                <div class="confirm-value" id="view_price"></div>
            </div>
            <div class="confirm-data-row" style="border: none;">
                <div class="confirm-label">三大營養素</div>
                <div class="confirm-value" id="view_macros" style="font-size: 13px; color: #4CAF50;"></div>
            </div>
        </div>
        <div class="confirm-footer">
            <button type="button" class="btn-confirm-cancel" onclick="closeConfirmModal()">返回修改</button>
            <button type="button" class="btn-confirm-submit" onclick="submitFinalForm()">確定新增</button>
        </div>
    </div>
</div>

<script>
    // 智慧模糊搜尋
    document.getElementById('historySearchInput').addEventListener('input', function() {
        const keyword = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('.history-row-item');
        
        rows.forEach(row => {
            const searchTarget = row.getAttribute('data-search-name');
            if (searchTarget.includes(keyword)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    });

    function openConfirmModal(name, cal, pro, fat, carbs, price) {
        const selectedDate = document.getElementById('global_date').value;
        const selectedMealVal = document.querySelector('input[name="global_meal"]:checked').value;
        
        const mealMap = { '1': '🌅 早餐', '2': '☀️ 午餐', '3': '🌙 晚餐', '4': '🧁 點心' };
        const mealText = mealMap[selectedMealVal] || '其他';

        const dateParts = selectedDate.split('-');
        const dateText = dateParts.length === 3 ? `${dateParts[0]}年${dateParts[1]}月${dateParts[2]}日` : selectedDate;

        document.getElementById('view_date').textContent = dateText;
        document.getElementById('view_meal').textContent = mealText;
        document.getElementById('view_name').textContent = name;
        document.getElementById('view_calories').textContent = cal + ' kcal';
        document.getElementById('view_price').textContent = '$' + Math.round(price);
        document.getElementById('view_macros').textContent = `蛋 ${pro.toFixed(1)}g | 脂 ${fat.toFixed(1)}g | 碳 ${carbs.toFixed(1)}g`;

        document.getElementById('post_food_name').value = name;
        document.getElementById('post_calories').value = cal;
        document.getElementById('post_protein').value = pro;
        document.getElementById('post_fat').value = fat;
        document.getElementById('post_carbs').value = carbs;
        document.getElementById('post_price').value = price;
        document.getElementById('post_daily_meal').value = selectedMealVal;
        document.getElementById('post_eat_date').value = selectedDate;

        document.getElementById('confirmModalOverlay').style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('confirmModalOverlay').style.display = 'none';
    }

    function submitFinalForm() {
        document.getElementById('hiddenSubmitForm').submit();
    }

    window.onclick = function(event) {
        const overlay = document.getElementById('confirmModalOverlay');
        if (event.target === overlay) {
            closeConfirmModal();
        }
    }
</script>

<?php include('footer.php'); ?>