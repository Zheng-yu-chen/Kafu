<?php
include('db.php');

/**
 * 1. 後端對接區 (SQL 邏輯)
 * 這裡已經寫好了關聯查詢，後端只要建立 'tray' 表與 'items' 表即可
 */
$sql = "SELECT t.id AS tray_id, i.name, r_id, i.calories, i.protein 
        FROM tray t 
        JOIN items i ON t.item_id = i.i_id"; 

$result = $conn->query($sql);

// 初始化數值
$total_cal = 0;
$total_protein = 0;
$goal_cal = 2000;
$item_count = ($result) ? $result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaFu 托盤</title>
    <style>
        :root {
            --fujen-blue: #002B5B;
            --primary-orange: #FF8C42;
            --bg-light: #f8f9fa;
        }

        /* --- 排版設計 (CSS) --- */
        body { background-color: var(--bg-light); margin: 0; padding-bottom: 100px; font-family: sans-serif; }
        
        /* 標頭藍色區塊 */
        .header { background-color: var(--fujen-blue); color: white; padding: 25px 20px; }
        .header h1 { margin: 0; font-size: 26px; }
        .header p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; }

        /* 餐點清單容器 */
        .tray-container { background: white; margin: -15px 15px 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; }
        
        /* 單筆餐點樣式 */
        .item-card { padding: 18px; border-bottom: 1px solid #f2f2f2; display: flex; justify-content: space-between; align-items: center; }
        .item-info h3 { margin: 0; font-size: 17px; color: #333; }
        .item-info p { margin: 4px 0 0; font-size: 12px; color: #999; }
        
        .item-stats { text-align: right; margin-right: 15px; }
        .cal-val { color: var(--primary-orange); font-weight: bold; font-size: 16px; }
        .pro-val { font-size: 11px; color: #666; }

        /* 移除按鈕 */
        .btn-remove { background: #eee; border: none; width: 26px; height: 26px; border-radius: 50%; color: #999; cursor: pointer; font-size: 14px; }

        /* 熱量統計區 */
        .summary-section { padding: 0 20px; }
        .summary-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; }
        .summary-row h2 { font-size: 16px; color: #333; margin: 0; }
        .total-cal { font-size: 32px; font-weight: bold; color: var(--primary-orange); }

        /* 進度條 */
        .progress-box { background: #e0e0e0; height: 10px; border-radius: 5px; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { background: #4CAF50; height: 100%; transition: width 0.5s ease; }
        
        /* 蛋白質方塊 */
        .protein-card { background: #E8F5E9; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
        .protein-card span { color: #2E7D32; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>KaFu 托盤</h1>
    <p>輔大健康飲食追蹤</p>
</div>

<div class="tray-container">
    <?php 
    /**
     * 2. 循環顯示功能
     * 此處「邊跑邊算」總量，後端接上資料庫後自動運作
     */
    if ($item_count > 0):
        while($row = $result->fetch_assoc()):
            $total_cal += $row['calories'];
            $total_protein += $row['protein'];
    ?>
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
                <button class="btn-remove" onclick="deleteItem(<?php echo $row['tray_id']; ?>)">×</button>
            </div>
        </div>
    <?php 
        endwhile;
    else:
    ?>
        <div style="padding: 40px; text-align: center; color: #ccc;">托盤是空的，快去加入美食吧！</div>
    <?php endif; ?>
</div>

<div class="summary-section">
    <div class="summary-row">
        <h2>全日總熱量</h2>
        <span class="total-cal"><?php echo $total_cal; ?> <small style="font-size:14px; color:#333">kcal</small></span>
    </div>

    <?php $percent = ($total_cal > 0) ? min(($total_cal / $goal_cal) * 100, 100) : 0; ?>
    <div class="progress-box">
        <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
    </div>
    <div style="text-align: center; font-size: 12px; color: #999;">目標：<?php echo $goal_cal; ?> kcal</div>

    <div class="protein-card">
        <span>全日總蛋白質</span>
        <span style="font-size: 20px;"><?php echo $total_protein; ?> g</span>
    </div>

    <button onclick="clearTray()" style="width: 100%; background: none; border: none; color: #ff4d4d; margin-top: 20px; cursor: pointer;">🗑 清空托盤</button>
</div>

<nav style="position: fixed; bottom: 0; width: 100%; background: white; display: flex; padding: 12px 0; border-top: 1px solid #eee;">
    <a href="index.php" style="flex:1; text-align:center; text-decoration:none; color:#bbb; font-size:12px;">🏠<br>店家</a>
    <a href="tray.php" style="flex:1; text-align:center; text-decoration:none; color:var(--fujen-blue); font-size:12px; font-weight:bold;">📋<br>托盤</a>
    <a href="#" style="flex:1; text-align:center; text-decoration:none; color:#bbb; font-size:12px;">💬<br>評價</a>
    <a href="#" style="flex:1; text-align:center; text-decoration:none; color:#bbb; font-size:12px;">👤<br>我的</a>
</nav>

<script>
/**
 * 3. 交互功能區
 * 這些 function 已經把「掛鉤」留給後端了
 */
function deleteItem(id) {
    if(confirm('確定要移除這項餐點嗎？')) {
        // 後端對接點：導向執行刪除的 PHP
        window.location.href = 'delete_item.php?id=' + id;
    }
}

function clearTray() {
    if(confirm('確定要清空所有餐點嗎？')) {
        // 後端對接點：導向清空功能
        window.location.href = 'clear_tray.php';
    }
}
</script>
<?php include('footer.php'); ?>
</body>
</html>