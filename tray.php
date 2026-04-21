<?php
session_start();
include('db.php');
include('header.php');

if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)) {
    echo "<script>alert('店家與管理員無法使用托盤功能！'); window.location.href='index.php';</script>";
    exit();
}

$tray_items = [];

// ==========================================
// 直接從 Session 讀取暫存托盤，去資料庫查詳細資訊
// ==========================================
if (!empty($_SESSION['tray'])) {
    foreach ($_SESSION['tray'] as $index => $item) {
        $i_id = intval($item['item_id']);
        $sql = "SELECT i.name, r.name AS restaurant, i.calories, i.protein 
                FROM items i
                JOIN categories c ON i.c_id = c.c_id
                JOIN restaurants r ON c.r_id = r.r_id
                WHERE i.item_id = $i_id";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $row['session_index'] = $index; // 記錄在陣列裡的位置，方便刪除
            $row['meal_time'] = $item['meal_time'];
            $tray_items[] = $row;
        }
    }
}
$item_count = count($tray_items);
?>

<style>
    .header { background-color: var(--fujen-blue, #002B5B); color: white; padding: 25px 20px; }
    .header h1 { margin: 0; font-size: 26px; }
    .header p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; }
    .tray-container { background: white; margin: -15px 15px 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; position: relative; z-index: 10; }
    .item-card { padding: 18px; border-bottom: 1px solid #f2f2f2; display: flex; justify-content: space-between; align-items: center; }
    .item-info h3 { margin: 0; font-size: 17px; color: #333; }
    .item-info p { margin: 4px 0 0; font-size: 12px; color: #999; }
    .item-stats { text-align: right; margin-right: 15px; }
    .cal-val { color: var(--primary-orange, #FF8C42); font-weight: bold; font-size: 16px; }
    .pro-val { font-size: 11px; color: #666; }
    .btn-remove { background: #eee; border: none; width: 26px; height: 26px; border-radius: 50%; color: #999; cursor: pointer; font-size: 14px; }
    .action-section { padding: 0 20px 100px; } 
    .guest-notice { background: #FFF3E0; color: #E65100; font-size: 12px; text-align: center; padding: 10px; font-weight: bold; }
    .btn-settle { background: #4CAF50; color: white; border: none; width: 100%; padding: 15px; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(76,175,80,0.3); }
    .btn-settle:active { transform: scale(0.98); }
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
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p>📍 <?php echo htmlspecialchars($row['restaurant']); ?> <?php echo $row['meal_time'] != '全天' ? ' • '.$row['meal_time'] : ''; ?></p>
                </div>
                <div style="display: flex; align-items: center;">
                    <div class="item-stats">
                        <div class="cal-val"><?php echo $row['calories']; ?> kcal</div>
                        <div class="pro-val">蛋白質 <?php echo $row['protein']; ?>g</div>
                    </div>
                    <button class="btn-remove" onclick="deleteItem(<?php echo $row['session_index']; ?>)">×</button>
                </div>
            </div>
        <?php endforeach; ?>
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