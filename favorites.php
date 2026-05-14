<?php
session_start();
include('db.php');
include('header.php');

// 檢查是否登入
if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('請先登入後再查看收藏！'); window.location.href='login.php';</script>";
    exit;
}

$u_id = $_SESSION['u_id'];

// 查詢該使用者的收藏餐點，並關聯餐點(items)與餐廳(restaurants)資訊
$sql = "SELECT i.*, r.name AS res_name, r.location, r.r_id 
        FROM favorites f
        JOIN items i ON f.item_id = i.item_id
        JOIN categories c ON i.c_id = c.c_id
        JOIN restaurants r ON c.r_id = r.r_id
        WHERE f.u_id = $u_id
        ORDER BY f.created_at DESC";

$result = $conn->query($sql);
?>

<style>
    .fav-header { padding: 20px; text-align: center; color: var(--fujen-blue, #002B5B); }
    .fav-list { padding: 0 20px 100px; }
    /* 延用 index.php 的卡片樣式 */
    .res-card { background: white; border-radius: 15px; padding: 15px; display: flex; align-items: center; text-decoration: none; color: inherit; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .item-card-info { flex: 1; display: flex; justify-content: space-between; align-items: center; }
    .item-name { font-size: 16px; font-weight: bold; color: var(--fujen-blue, #002B5B); margin: 0 0 4px; }
    .item-meta { font-size: 12px; color: #888; margin: 0 0 6px; }
    .dest-icon { width: 14px; vertical-align: middle; margin-right: 3px; }
    .item-nutrition-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .item-price { font-weight: bold; color: #E53935; font-size: 14px; }
    .fire-icon { width: 12px; vertical-align: middle; margin-right: 2px; }
    .item-macros { display: flex; gap: 6px; font-size: 12px; font-weight: bold; color: var(--primary-orange, #FF8C42); border-left: 1px solid #ddd; padding-left: 8px; }
    .back-nav { padding: 15px 20px; }
    .back-btn { text-decoration: none; color: #002B5B; font-weight: bold; font-size: 14px; }
</style>

<div class="fav-header-section">
    <div class="back-nav">
        <a href="index.php" class="back-btn">❮ 返回首頁</a>
    </div>
    
    <div style="text-align: center; padding: 10px 0;">
        <h1 class="fav-title">我的收藏餐點</h1>
    </div>
</div>

<div class="fav-list-container">
    </div>

<div class="fav-list">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" class="res-card">
                <div class="item-card-info">
                    <div class="item-text-content">
                        <h3 class="item-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p class="item-meta">
                            <?php echo htmlspecialchars($row['res_name']); ?> • 
                            <img src="icon/destination_icon.png" class="dest-icon"> <?php echo htmlspecialchars($row['location']); ?>
                        </p>
                        
                        <div class="item-nutrition-row">
                            <span class="item-price">$<?php echo floatval($row['price']); ?></span>
                            <span style="font-size: 12px; color: #888;">
                                <img src="icon/fire_icon.png" class="fire-icon"> <?php echo $row['calories'] ?: '---'; ?> kcal
                            </span>
                            
                            <div class="item-macros">
                                <span>蛋白質 <?php echo floatval($row['protein'] ?: 0); ?>g</span>
                                <span>脂肪 <?php echo floatval($row['fat'] ?: 0); ?>g</span>
                                <span>碳水 <?php echo floatval($row['carbs'] ?: 0); ?>g</span>
                            </div>
                        </div>
                    </div>
                    <div style="color: #ccc; font-size: 12px; margin-left: 10px;">❯</div>
                </div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding:50px; color:#999;">
            <div style="font-size:40px; margin-bottom:10px;">🤍</div>
            目前還沒有收藏任何餐點喔！
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>