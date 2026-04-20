<?php
session_start();
include('db.php');
include('header.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$r_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 0;

if ($r_id === 0) {
    echo "<div style='padding:50px; text-align:center;'>找不到指定的餐廳。</div>";
    include('footer.php');
    exit();
}

$res_sql = "SELECT name, location FROM restaurants WHERE r_id = $r_id";
$res_result = $conn->query($res_sql);
$res_name = "餐廳資訊";
$res_loc = "未知位置";

if ($res_result && $res_result->num_rows > 0) {
    $res_data = $res_result->fetch_assoc();
    $res_name = $res_data['name'];
    $res_loc = $res_data['location'];
}

$sql = "SELECT i.item_id, i.item_name, i.calories, i.protein 
        FROM items i
        JOIN categories c ON i.c_id = c.c_id
        WHERE c.r_id = $r_id";
$result = $conn->query($sql);
?>

<style>
    .header-section { background-color: var(--fujen-blue, #002B5B); color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }
    .header-title h1 { margin: 0; font-size: 24px; }
    .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }
    .menu-container { padding: 20px; }
    .item-card { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .item-info h4 { margin: 0; font-size: 16px; color: var(--fujen-blue, #002B5B); }
    .nutrition { font-size: 13px; margin-top: 5px; color: #666; }
    .pro-tag { color: var(--primary-orange, #FF8C42); font-weight: bold; margin-left: 10px; }
    
    /* 💡 改成按鈕樣式，移除 href */
    .add-btn { background: var(--fujen-blue, #002B5B); color: white; width: 34px; height: 34px; display: flex; justify-content: center; align-items: center; border-radius: 50%; border: none; font-size: 20px; font-weight: bold; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,43,91,0.2); cursor: pointer; }
    .add-btn:active { transform: scale(0.95); }

    /* ================= 彈出視窗專屬樣式 ================= */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5); z-index: 10000; 
        justify-content: center; align-items: center; padding: 20px;
    }
    .modal-box {
        background: white; width: 100%; max-width: 320px; border-radius: 12px; overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: slideUp 0.3s ease;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    
    .modal-header {
        background-color: var(--fujen-blue, #002B5B); color: white; padding: 20px;
        display: flex; justify-content: space-between; align-items: flex-start;
    }
    .modal-header h2 { margin: 0; font-size: 20px; }
    .modal-header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; font-weight: normal; }
    .close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; }
    
    .modal-body { padding: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: bold; }
    
    .date-input-wrapper { position: relative; }
    .date-icon { position: absolute; left: 12px; top: 12px; font-size: 16px; color: #555; pointer-events: none; }
    .date-input { width: 100%; padding: 12px 12px 12px 35px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
    .date-hint { display: block; color: #888; font-size: 11px; margin-top: 5px; }
    
    .meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .meal-option input { display: none; }
    .meal-option span { 
        display: block; text-align: center; padding: 12px; background: #f8f9fa; 
        border-radius: 8px; font-size: 14px; color: #555; cursor: pointer; transition: 0.2s;
    }
    .meal-option input:checked + span { 
        background: #f0f5fa; color: var(--fujen-blue, #002B5B); font-weight: bold; 
    }
    
    .submit-tray-btn {
        width: 100%; background: #7a93ac; color: white; border: none; padding: 15px; 
        border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px;
    }
    .submit-tray-btn:active { transform: scale(0.98); }
</style>

<div class="header-section">
    <a href="index.php" class="back-btn">❮ 返回店家列表</a>
    <div class="header-title">
        <h1><?php echo htmlspecialchars($res_name); ?></h1>
        <p>📍 <?php echo htmlspecialchars($res_loc); ?> • 餐點列表</p>
    </div>
</div>

<div class="menu-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="item-card">
                <div class="item-info">
                    <h4><?php echo htmlspecialchars($row['item_name']); ?></h4>
                    <div class="nutrition">
                        <span>🔥 <?php echo ($row['calories'] !== null) ? $row['calories'] : '---'; ?> kcal</span>
                        <span class="pro-tag">💪 蛋白質 <?php echo isset($row['protein']) ? $row['protein'] : '0'; ?>g</span>
                    </div>
                </div>
                
                <?php 
                    $show_add_btn = true; // 預設訪客跟學生都看得到
                    if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)) {
                        $show_add_btn = false; // 店家和管理員隱藏
                    }
                ?>
                
                <?php if ($show_add_btn): ?>
                    <button class="add-btn" onclick="openTrayModal(<?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars($row['item_name']); ?>')">+</button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #999;">
            <div style="font-size: 30px; margin-bottom: 10px;">🥗</div>
            目前這間餐廳還沒有上架餐點喔！
        </div>
    <?php endif; ?>
</div>

<div id="trayModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>加入托盤</h2>
                <p id="modalItemName">餐點名稱</p>
            </div>
            <button class="close-btn" onclick="closeTrayModal()">×</button>
        </div>
        
        <form action="add_to_tray.php" method="POST">
            <input type="hidden" name="item_id" id="modalItemId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>用餐日期</label>
                    <div class="date-input-wrapper">
                        <span class="date-icon">📅</span>
                        <input type="date" name="eat_date" class="date-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <span class="date-hint">可選擇今天或過去的日期</span>
                </div>
                
                <div class="form-group">
                    <label>用餐時段</label>
                    <div class="meal-grid">
                        <label class="meal-option">
                            <input type="radio" name="meal_time" value="早餐" required>
                            <span>早餐</span>
                        </label>
                        <label class="meal-option">
                            <input type="radio" name="meal_time" value="午餐">
                            <span>午餐</span>
                        </label>
                        <label class="meal-option">
                            <input type="radio" name="meal_time" value="晚餐">
                            <span>晚餐</span>
                        </label>
                        <label class="meal-option">
                            <input type="radio" name="meal_time" value="點心">
                            <span>點心</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="submit-tray-btn">確認加入</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTrayModal(itemId, itemName) {
        document.getElementById('modalItemId').value = itemId;
        document.getElementById('modalItemName').innerText = itemName;
        document.getElementById('trayModal').style.display = 'flex';
    }

    function closeTrayModal() {
        document.getElementById('trayModal').style.display = 'none';
    }

    // 點擊黑色半透明背景時，自動關閉視窗
    document.getElementById('trayModal').addEventListener('click', function(e) {
        if (e.target === this) closeTrayModal();
    });
</script>

<?php include('footer.php'); ?>