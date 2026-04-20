<?php
session_start();
include('db.php');
include('header.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$tray_items = [];
$total_cal = 0; 
$total_protein = 0; 
$goal_cal = 2000;

// ==========================================
// 1. 會員：從資料庫撈取 (只抓這個人的)
// ==========================================
if (isset($_SESSION['u_id'])) {
    $u_id = $_SESSION['u_id'];
    try {
        // 💡 加上 WHERE t.u_id = $u_id，避免抓到別人的紀錄
        $sql = "SELECT t.id AS tray_id, i.item_name AS name, r.name AS restaurant, i.calories, i.protein 
                FROM tray t 
                JOIN items i ON t.item_id = i.item_id
                JOIN categories c ON i.c_id = c.c_id
                JOIN restaurants r ON c.r_id = r.r_id
                WHERE t.u_id = $u_id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tray_items[] = $row;
                $total_cal += $row['calories'] ?? 0;
                $total_protein += $row['protein'] ?? 0;
            }
        }
    } catch (mysqli_sql_exception $e) {}
} 
// ==========================================
// 2. 訪客：從 Session 撈取暫存托盤
// ==========================================
else if (isset($_SESSION['guest_tray'])) {
    foreach ($_SESSION['guest_tray'] as $index => $g_item) {
        // 兼容處理：無論你存入的是陣列還是單純的 ID
        $i_id = is_array($g_item) ? intval($g_item['item_id']) : intval($g_item);
        
        try {
            $sql = "SELECT i.item_name AS name, r.name AS restaurant, i.calories, i.protein 
                    FROM items i
                    JOIN categories c ON i.c_id = c.c_id
                    JOIN restaurants r ON c.r_id = r.r_id
                    WHERE i.item_id = $i_id";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $row['tray_id'] = 'guest_' . $index; // 訪客專用的假 ID，用於刪除
                $tray_items[] = $row;
                $total_cal += $row['calories'] ?? 0;
                $total_protein += $row['protein'] ?? 0;
            }
        } catch (mysqli_sql_exception $e) {}
    }
}

$item_count = count($tray_items);
?>

<style>
    /* 💡 完全恢復你原本乾淨的 CSS */
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
    
    .summary-section { padding: 0 20px 90px; } /* 加了一點底部空間避免被 footer 擋住 */
    .summary-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; }
    .summary-row h2 { font-size: 16px; color: #333; margin: 0; }
    .total-cal { font-size: 32px; font-weight: bold; color: var(--primary-orange, #FF8C42); }
    .progress-box { background: #e0e0e0; height: 10px; border-radius: 5px; overflow: hidden; margin-bottom: 8px; }
    .progress-fill { background: #4CAF50; height: 100%; transition: width 0.5s ease; }
    .protein-card { background: #E8F5E9; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
    .protein-card span { color: #2E7D32; font-weight: bold; }
    
    .guest-notice { background: #FFF3E0; color: #E65100; font-size: 12px; text-align: center; padding: 10px; font-weight: bold; }
</style>

<div class="header">
    <h1>KaFu 托盤</h1>
    <p>輔大健康飲食追蹤</p>
</div>

<div class="tray-container">
    <?php if ($item_count > 0): ?>
        <?php foreach ($tray_items as $row): ?>
            <div class="item-card">
                <div class="item-info">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p>📍 <?php echo htmlspecialchars($row['restaurant']); ?></p>
                </div>
                <div style="display: flex; align-items: center;">
                    <div class="item-stats">
                        <div class="cal-val"><?php echo $row['calories']; ?> kcal</div>
                        <div class="pro-val">蛋白質 <?php echo $row['protein']; ?>g</div>
                    </div>
                    <button class="btn-remove" onclick="deleteItem('<?php echo $row['tray_id']; ?>')">×</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #ccc;">托盤是空的，快去加入美食吧！</div>
    <?php endif; ?>
</div>

<div class="summary-section">
    <div class="summary-row">
        <h2>全日總熱量</h2>
        <span class="total-cal"><?php echo $total_cal; ?> <small style="font-size:14px; color:#333">kcal</small></span>
    </div>
    <?php $percent = ($total_cal > 0) ? min(($total_cal / $goal_cal) * 100, 100) : 0; ?>
    <div class="progress-box"><div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
    <div style="text-align: center; font-size: 12px; color: #999;">目標：<?php echo $goal_cal; ?> kcal</div>

    <div class="protein-card">
        <span>全日總蛋白質</span><span style="font-size: 20px;"><?php echo $total_protein; ?> g</span>
    </div>
    <button onclick="clearTray()" style="width: 100%; background: none; border: none; color: #ff4d4d; margin-top: 20px; cursor: pointer;">🗑 清空托盤</button>
</div>

<script>
// 接收參數的引號已經在 HTML 裡面處理好了
function deleteItem(id) { if(confirm('確定要移除嗎？')) window.location.href = 'delete_item.php?id=' + id; }
function clearTray() { if(confirm('確定要清空嗎？')) window.location.href = 'clear_tray.php'; }
</script>

<?php include('footer.php'); ?>