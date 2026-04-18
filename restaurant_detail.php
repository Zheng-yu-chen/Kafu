<?php
// 1. 確保引入連線檔案，並檢查檔案路徑是否正確
if (!file_exists('db.php')) {
    die("錯誤：找不到 db.php 檔案，請確認檔案放在同一個資料夾下。");
}
include('db.php'); 

// 2. 檢查 $conn 變數是否存在 (防止 Undefined variable 報錯)
if (!isset($conn)) {
    die("錯誤：在 db.php 中找不到 \$conn 連線變數。請檢查 db.php 裡的變數名稱是否為 \$conn。");
}

// 3. 取得網址上的餐廳編號，並確保它是數字
$r_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 0;

if ($r_id === 0) {
    die("未指定的餐廳編號。");
}

// 4. SQL 指令：透過 Categories 表串接 Items 表
// 這裡對應你最後給的 Schema：Items(c_id) -> Categories(c_id, r_id)
$sql = "SELECT i.item_id, i.item_name, i.calories, i.price 
        FROM Items i
        JOIN Categories c ON i.c_id = c.c_id
        WHERE c.r_id = $r_id";

$result = $conn->query($sql);

// 5. 開始渲染畫面
if ($result && $result->num_rows > 0):
    while($row = $result->fetch_assoc()): ?>
        <div class="item-card" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 12px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div class="item-info">
                <h4 style="margin: 0; font-size: 18px; color: #002B5B;">
                    <?php echo htmlspecialchars($row['item_name']); ?>
                </h4>
                <div class="nutrition" style="font-size: 14px; margin-top: 5px; color: #666;">
                    <span>🔥 <?php echo ($row['calories'] !== null) ? $row['calories'] : '---'; ?> kcal</span>
                    <span style="margin-left:15px; color: #FF8C42; font-weight: bold;">$<?php echo $row['price']; ?></span>
                </div>
            </div>
            
            <a href="add_to_tray.php?item_id=<?php echo $row['item_id']; ?>" 
               class="add-btn" 
               style="background: #002B5B; color: white; width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; border-radius: 50%; text-decoration: none; font-size: 20px;">+</a>
        </div>
    <?php endwhile; 
else: ?>
    <div style="text-align: center; padding: 50px; color: #999;">
        目前這間餐廳還沒有上架餐點喔！🥗
    </div>
<?php endif; ?>