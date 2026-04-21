<?php
session_start();
include('db.php');
include('header.php');

if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)) {
    echo "<script>alert('店家與管理員無法使用托盤功能！'); window.location.href='index.php';</script>";
    exit();
}

$tray_items = [];
$total_calories = 0; 
$total_protein = 0;  

if (!empty($_SESSION['tray'])) {
    foreach ($_SESSION['tray'] as $index => $item) {
        $i_id = intval($item['item_id']);
        // 💡 取得該筆記錄的數量，若無則預設為 1
        $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;

        $sql = "SELECT i.name, r.name AS restaurant, i.calories, i.protein 
                FROM items i
                JOIN categories c ON i.c_id = c.c_id
                JOIN restaurants r ON c.r_id = r.r_id
                WHERE i.item_id = $i_id";
        $res = $conn->query($sql);
        
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            
            // 💡 關鍵：將營養值乘上數量
            $item_total_cal = intval($row['calories']) * $qty;
            $item_total_pro = floatval($row['protein']) * $qty;

            // 累加到總計
            $total_calories += $item_total_cal;
            $total_protein += $item_total_pro;
            
            $row['session_index'] = $index; 
            $row['meal_time'] = $item['meal_time'];
            $row['quantity'] = $qty; // 存入陣列供下方顯示使用
            $row['display_cal'] = $item_total_cal; // 存入計算後的熱量
            $row['display_pro'] = $item_total_pro; // 存入計算後的蛋白質
            
            $tray_items[] = $row;
        }
    }
}
$item_count = count($tray_items);
?>

<style>
    /* ... 保留你原本的 style ... */
    .header { background-color: var(--fujen-blue, #002B5B); color: white; padding: 25px 20px; }
    .header h1 { margin: 0; font-size: 26px; }
    .header p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; }
    .tray-container { 
    background: white; 
    /* 💡 將原本的 -15px 改為 15px (正值)，讓它跟上面的提示文字保持距離 */
    margin: 15px 15px 20px; 
    border-radius: 15px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
    overflow: hidden; 
    position: relative; 
    z-index: 10; 
}/* 💡 建議額外加上這個，確保橘色提示文字也有足夠的間距 */
.guest-notice { 
    background: #FFF3E0; 
    color: #E65100; 
    font-size: 13px; 
    text-align: center; 
    padding: 12px; 
    font-weight: bold;
    border-bottom: 1px solid #FFE0B2; /* 加一條細線區隔更美觀 */
}
    .item-card { padding: 18px; border-bottom: 1px solid #f2f2f2; display: flex; justify-content: space-between; align-items: center; }
    .item-info h3 { margin: 0; font-size: 17px; color: #333; }
    .item-info p { margin: 4px 0 0; font-size: 12px; color: #999; }
    .item-stats { text-align: right; margin-right: 15px; }
    .cal-val { color: var(--primary-orange, #FF8C42); font-weight: bold; font-size: 16px; }
    .pro-val { font-size: 11px; color: #666; }
    .btn-remove { background: #f8f8f8; border: none; width: 28px; height: 28px; border-radius: 50%; color: #ccc; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; }
    .tray-summary { background: #fdfdfd; padding: 20px 18px; border-top: 2px dashed #eee; display: flex; justify-content: space-between; align-items: center; }
    .summary-label { font-weight: bold; color: #444; font-size: 15px; }
    .summary-values { text-align: right; }
    .total-cal { color: var(--primary-orange, #FF8C42); font-size: 22px; font-weight: 800; line-height: 1.2; }
    .total-cal small { font-size: 12px; margin-left: 2px; }
    .total-pro { color: #888; font-size: 12px; margin-top: 2px; }
    .action-section { padding: 0 20px 100px; } 
    .guest-notice { background: #FFF3E0; color: #E65100; font-size: 12px; text-align: center; padding: 10px; font-weight: bold; }
    .btn-settle { background: #4CAF50; color: white; border: none; width: 100%; padding: 15px; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(76,175,80,0.3); }
    .btn-settle:active { transform: scale(0.98); }
    /* 💡 新增：數量的藍色小標籤 */
    .qty-tag { background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 4px; font-size: 12px; margin-left: 5px; }
</style>

<div class="header">
    <h1>待結算托盤</h1>
    <p>確認無誤後請按下結算</p>
</div>

<?php if (!isset($_SESSION['u_id']) && $item_count > 0): ?>
<div class="guest-notice">
    您目前為訪客身分，餐點僅為暫存。<a href="login.php" style="color:#E65100; text-decoration:underline;">登入以解鎖結算與歷史紀錄</a>
</div>
<?php endif; ?>

<div class="tray-container">
    <?php if ($item_count > 0): ?>
        <?php foreach ($tray_items as $row): ?>
            <div class="item-card">
                <div class="item-info">
                    <h3>
                        <?php echo htmlspecialchars($row['name']); ?>
                        <span class="qty-tag">x<?php echo $row['quantity']; ?></span>
                    </h3>
                    <p>📍 <?php echo htmlspecialchars($row['restaurant']); ?> <?php echo $row['meal_time'] != '全天' ? ' • '.$row['meal_time'] : ''; ?></p>
                </div>
                <div style="display: flex; align-items: center;">
                    <div class="item-stats">
                        <div class="cal-val"><?php echo $row['display_cal']; ?> kcal</div>
                        <div class="pro-val">蛋白質 <?php echo number_format($row['display_pro'], 1); ?>g</div>
                    </div>
                    <button class="btn-remove" onclick="deleteItem(<?php echo $row['session_index']; ?>)">×</button>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="tray-summary">
            <div class="summary-label">預計攝取總計</div>
            <div class="summary-values">
                <div class="total-cal"><?php echo $total_calories; ?><small>kcal</small></div>
                <div class="total-pro">蛋白質共 <?php echo number_format($total_protein, 1); ?>g</div>
            </div>
        </div>

    <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #ccc;">托盤是空的，快去加入美食吧！</div>
    <?php endif; ?>
</div>

<div class="action-section">
    <?php if ($item_count > 0): ?>
        <?php if (isset($_SESSION['u_id'])): ?>
            <form action="settle_tray.php" method="POST">
                <button type="submit" class="btn-settle">✔️ 結算寫入歷史紀錄</button>
            </form>
        <?php endif; ?>
        <button onclick="clearTray()" style="width: 100%; background: none; border: none; color: #ff4d4d; margin-top: 15px; cursor: pointer; font-weight:bold;">🗑 清空托盤</button>
    <?php endif; ?>
</div>

<script>
function deleteItem(index) { window.location.href = 'delete_item.php?id=' + index; }
function clearTray() { if(confirm('確定要清空嗎？')) window.location.href = 'clear_tray.php'; }
</script>

<?php include('footer.php'); ?>