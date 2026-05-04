<?php
session_start();
include('db.php');
include('header.php');

if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)) {
    echo "<script>alert('店家與管理員無法使用托盤功能！'); window.location.href='index.php';</script>";
    exit();
}

$tray_items = [];
// 💡 新增：各種總和變數
$total_calories = 0; 
$total_protein = 0;  
$total_price = 0;
$total_fat = 0;
$total_carbs = 0;

if (!empty($_SESSION['tray'])) {
    foreach ($_SESSION['tray'] as $index => $item) {
        $i_id = intval($item['item_id']);
        $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;

        // 💡 修正 SQL：多抓取 price, fat, carbs
        $sql = "SELECT i.name, r.name AS restaurant, i.price, i.calories, i.protein, i.fat, i.carbs 
                FROM items i
                JOIN categories c ON i.c_id = c.c_id
                JOIN restaurants r ON c.r_id = r.r_id
                WHERE i.item_id = $i_id";
        $res = $conn->query($sql);
        
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            
            // 將數值乘上數量
            $item_total_cal = intval($row['calories']) * $qty;
            $item_total_pro = floatval($row['protein']) * $qty;
            $item_total_price = floatval($row['price']) * $qty;
            $item_total_fat = floatval($row['fat']) * $qty;
            $item_total_carbs = floatval($row['carbs']) * $qty;

            // 累加到總計
            $total_calories += $item_total_cal;
            $total_protein += $item_total_pro;
            $total_price += $item_total_price;
            $total_fat += $item_total_fat;
            $total_carbs += $item_total_carbs;
            
            $row['session_index'] = $index; 
            $row['meal_time'] = $item['meal_time'];
            $row['quantity'] = $qty; 
            $row['display_cal'] = $item_total_cal; 
            $row['display_pro'] = $item_total_pro; 
            
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
    .tray-container { 
        background: white; 
        margin: 15px 15px 20px; 
        border-radius: 15px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        overflow: hidden; 
        position: relative; 
        z-index: 10; 
    }
    .guest-notice { 
        background: #FFF3E0; 
        color: #E65100; 
        font-size: 13px; 
        text-align: center; 
        padding: 12px; 
        font-weight: bold;
        border-bottom: 1px solid #FFE0B2; 
    }
    .item-card { padding: 18px; border-bottom: 1px solid #f2f2f2; display: flex; justify-content: space-between; align-items: center; }
    .item-info h3 { margin: 0; font-size: 17px; color: #333; }
    .item-info p { margin: 4px 0 0; font-size: 12px; color: #999; }
    .item-stats { text-align: right; margin-right: 15px; }
    .cal-val { color: var(--primary-orange, #FF8C42); font-weight: bold; font-size: 16px; }
    .pro-val { font-size: 11px; color: #666; }
    .btn-remove { background: #f8f8f8; border: none; width: 28px; height: 28px; border-radius: 50%; color: #ccc; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; }
    .qty-tag { background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 4px; font-size: 12px; margin-left: 5px; }

    /* 💡 全新設計的結算總計區域 */
    .tray-summary { background: #fdfdfd; padding: 20px 18px; border-top: 2px dashed #eee; display: flex; flex-direction: column; gap: 12px;}
    
    /* 總計標題 */
    .summary-label { font-weight: bold; color: #444; font-size: 15px; border-bottom: 1px solid #eaeaea; padding-bottom: 8px;}
    
    /* 第一排：價格與熱量 */
    .summary-main-row { display: flex; justify-content: space-between; align-items: baseline; }
    .total-price { font-size: 20px; font-weight: bold; color: #E53935; } /* 價格設為紅色 */
    .total-cal { color: var(--primary-orange, #FF8C42); font-size: 32px; font-weight: 900; line-height: 1; } /* 熱量最大 */
    .total-cal small { font-size: 14px; font-weight: normal; margin-left: 3px; }
    
    /* 第二排：三大營養素 */
    .summary-macro-row { display: flex; justify-content: space-between; background: #f5f5f5; padding: 10px 15px; border-radius: 10px; }
    .macro-item { text-align: center; }
    .macro-label { display: block; font-size: 11px; color: #888; margin-bottom: 2px; }
    .macro-val { font-size: 14px; font-weight: bold; color: #333; }
    .macro-pro { color: #1976d2; } /* 蛋白質藍色 */
    .macro-fat { color: #fbc02d; } /* 脂肪黃色 */
    .macro-carbs { color: #388e3c; } /* 碳水綠色 */

    .action-section { padding: 0 20px 100px; } 
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

        <!-- 💡 重新排版的總計區域 -->
        <div class="tray-summary">
            <div class="summary-label">預計攝取與花費總計</div>
            
            <div class="summary-main-row">
                <div class="total-price">$<?php echo floatval($total_price); ?></div>
                <div class="total-cal">🔥 <?php echo $total_calories; ?><small>kcal</small></div>
            </div>
            
            <div class="summary-macro-row">
                <div class="macro-item macro-pro">
                    <span class="macro-label">蛋白質</span>
                    <span class="macro-val"><?php echo number_format($total_protein, 1); ?> g</span>
                </div>
                <div class="macro-item macro-fat">
                    <span class="macro-label">脂肪</span>
                    <span class="macro-val"><?php echo number_format($total_fat, 1); ?> g</span>
                </div>
                <div class="macro-item macro-carbs">
                    <span class="macro-label">碳水</span>
                    <span class="macro-val"><?php echo number_format($total_carbs, 1); ?> g</span>
                </div>
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
                <button type="submit" class="btn-settle">結算寫入歷史紀錄</button>
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