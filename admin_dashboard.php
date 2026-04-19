<?php
session_start();
include('db.php');
include('header.php');

// 權限檢查：只有管理員 (role_id 1) 可以訪問
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo "<script>alert('無權限訪問！'); window.location.href='login.php';</script>";
    exit();
}

// 開啟錯誤回報
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================
// 1. 撈取「待審核評價」(status = 0)
// ==========================================
try {
    $sql_reviews = "SELECT c.*, i.item_name, r.name AS res_name, r.location, a.name AS reviewer_name 
                    FROM comments c
                    JOIN items i ON c.item_id = i.item_id
                    JOIN categories cat ON i.c_id = cat.c_id
                    JOIN restaurants r ON cat.r_id = r.r_id
                    LEFT JOIN accounts a ON c.u_id = a.u_id
                    WHERE c.status = 0
                    ORDER BY c.created_at ASC";
    $reviews_result = $conn->query($sql_reviews);
    $pending_count = $reviews_result->num_rows;
} catch (mysqli_sql_exception $e) {
    $pending_count = 0;
}

// ==========================================
// 2. 撈取「菜單維護」所需的所有資料
// ==========================================
$restaurants = [];
try {
    $res_query = $conn->query("SELECT r_id, name, location FROM restaurants");
    if ($res_query) {
        while($r = $res_query->fetch_assoc()) {
            $restaurants[] = $r;
        }
    }
} catch (mysqli_sql_exception $e) {}

$items = [];
// 💡 防呆機制：如果 categories 沒有 name 欄位，就不會崩潰
try {
    // 第一次嘗試：假設分類名稱的欄位叫做 name
    $item_query = $conn->query("
        SELECT i.*, c.name AS c_name, c.r_id 
        FROM items i 
        JOIN categories c ON i.c_id = c.c_id
    ");
    if ($item_query) {
        while($i = $item_query->fetch_assoc()) {
            $items[] = $i;
        }
    }
} catch (mysqli_sql_exception $e) {
    // 💥 第二次嘗試：如果報錯，代表沒有 name 欄位，我們就只抓資料不抓分類名字
    $item_query = $conn->query("
        SELECT i.*, c.r_id 
        FROM items i 
        JOIN categories c ON i.c_id = c.c_id
    ");
    if ($item_query) {
        while($i = $item_query->fetch_assoc()) {
            $i['c_name'] = '未分類'; // 自動填入預設文字避免 JS 錯誤
            $items[] = $i;
        }
    }
}
?>

<style>
    body { background-color: #f4f7f9; }

    /* 頂部 Header */
    .admin-header {
        background-color: var(--fujen-blue, #002B5B);
        color: white; padding: 30px 20px 20px;
    }
    .admin-header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
    .admin-header p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }

    /* 標籤頁 (Tabs) */
    .tab-container {
        display: flex; background: white; border-bottom: 1px solid #ddd;
        position: sticky; top: 0; z-index: 100;
    }
    .tab-btn {
        flex: 1; text-align: center; padding: 15px 0; font-size: 15px;
        cursor: pointer; color: #666; font-weight: bold; transition: 0.2s;
    }
    .tab-btn.active { color: var(--fujen-blue, #002B5B); border-bottom: 3px solid var(--fujen-blue, #002B5B); }
    .badge {
        background-color: var(--primary-orange, #FF8C42); color: white;
        font-size: 12px; padding: 2px 8px; border-radius: 12px; margin-left: 5px;
    }

    /* 內容區塊 */
    .content-section { padding: 20px; display: none; }
    .content-section.active { display: block; }
    .section-title { font-size: 16px; font-weight: bold; color: #333; margin: 0 0 15px; }

    /* 評價卡片 */
    .review-card {
        background: white; border-radius: 12px; padding: 15px;
        margin-bottom: 15px; border: 1px solid #eee; box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
    .reviewer-name { font-size: 15px; font-weight: bold; color: #333; }
    .review-time { font-size: 11px; color: #999; margin-top: 2px; }
    .review-stars { color: #FFC107; font-size: 14px; }
    
    .item-tag { background: #f8f9fa; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
    .item-tag-name { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 4px; }
    .item-tag-loc { font-size: 12px; color: #888; }
    
    .review-text { font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 15px; }
    
    .action-buttons { display: flex; gap: 10px; }
    .btn-approve, .btn-reject { flex: 1; padding: 12px; border-radius: 8px; border: none; font-weight: bold; font-size: 14px; cursor: pointer; color: white; display: flex; justify-content: center; align-items: center; gap: 5px; }
    .btn-approve { background-color: #4CAF50; }
    .btn-reject { background-color: #F44336; }

    /* 菜單維護 - 列表樣式 */
    .menu-card {
        background: white; border-radius: 12px; border: 1px solid #eee;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03); overflow: hidden;
    }
    .breadcrumb { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #666; font-weight: bold; display: flex; align-items: center; gap: 8px; }
    .breadcrumb-back { cursor: pointer; color: var(--fujen-blue, #002B5B); font-size: 18px; line-height: 1; }
    .breadcrumb span { cursor: pointer; }
    .breadcrumb span:hover { color: var(--fujen-blue, #002B5B); }

    .list-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; transition: 0.2s;
    }
    .list-item:hover { background: #fafbfc; }
    .list-item:last-child { border-bottom: none; }
    
    .list-icon { width: 40px; height: 40px; background: var(--fujen-blue, #002B5B); color: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-weight: bold; margin-right: 15px; font-size: 16px; }
    .list-info h5 { margin: 0; font-size: 15px; color: #333; }
    .list-info p { margin: 4px 0 0; font-size: 12px; color: #888; }
    .arrow { color: #ccc; font-weight: bold; }

    /* 餐點表格樣式 */
    .table-container { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
    th { padding: 12px 15px; color: #666; border-bottom: 1px solid #eee; white-space: nowrap; }
    td { padding: 15px; border-bottom: 1px solid #f8f9fa; color: #333; vertical-align: middle; }
    .td-val { color: var(--primary-orange, #FF8C42); font-weight: bold; }
    .td-pro { color: #4CAF50; font-weight: bold; }
    .action-icon { color: var(--fujen-blue, #002B5B); font-size: 16px; margin-right: 10px; cursor: pointer; text-decoration: none;}
    .action-icon.delete { color: #F44336; }

    /* 底部登出區塊 */
    .logout-section { padding: 20px; margin-bottom: 80px; }
    .logout-btn {
        background: white; display: flex; align-items: center; justify-content: center;
        padding: 16px; border-radius: 12px; color: #F44336; text-decoration: none;
        font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.05); gap: 10px; border: 1px solid #FFCDD2;
    }
    .logout-btn:active { background: #FFF5F5; transform: scale(0.98); }
</style>

<div class="admin-header">
    <h1>管理員工作台</h1>
    <p>系統管理與審核</p>
</div>

<div class="tab-container">
    <div class="tab-btn active" onclick="switchTab('reviews')">審核評價 <span class="badge"><?php echo $pending_count; ?></span></div>
    <div class="tab-btn" onclick="switchTab('menu')">菜單維護</div>
</div>

<div id="tab-reviews" class="content-section active">
    
    <?php if ($pending_count > 0): ?>
        <h3 class="section-title">待審核評價 (<?php echo $pending_count; ?>)</h3>
        <?php while($rev = $reviews_result->fetch_assoc()): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <div class="reviewer-name"><?php echo htmlspecialchars($rev['reviewer_name'] ?? '匿名顧客'); ?></div>
                        <div class="review-time"><?php echo date('Y-m-d H:i', strtotime($rev['created_at'])); ?></div>
                    </div>
                    <div class="review-stars"><?php echo str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']); ?></div>
                </div>
                
                <div class="item-tag">
                    <div class="item-tag-name"><?php echo htmlspecialchars($rev['item_name']); ?></div>
                    <div class="item-tag-loc"><?php echo htmlspecialchars($rev['res_name'] ?? '') . ' • ' . htmlspecialchars($rev['location'] ?? ''); ?></div>
                </div>
                
                <div class="review-text"><?php echo nl2br(htmlspecialchars($rev['content'])); ?></div>
                
                <div class="action-buttons">
                    <button class="btn-approve" onclick="alert('審核通過！將更新 status=1')">✓ 通過</button>
                    <button class="btn-reject" onclick="if(confirm('確定要拒絕並刪除此評價嗎？')) alert('已拒絕！將刪除此記錄')">✕ 拒絕</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 50px 0;">
            <div style="font-size: 40px; margin-bottom: 10px;">🎉</div>
            <p style="color:#999; font-weight:bold;">目前沒有待審核的評價！</p>
        </div>
    <?php endif; ?>
</div>

<div id="tab-menu" class="content-section">
    <div class="menu-card">
        <div id="breadcrumb" class="breadcrumb">
            </div>
        <div id="menu-list-container">
            </div>
    </div>
</div>

<div class="logout-section">
    <a href="logout.php" class="logout-btn">登出</a>
</div>

<script>
    // ==========================================
    // 1. Tab 切換邏輯
    // ==========================================
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }

    // ==========================================
    // 2. 菜單維護 - JS 動態下鑽邏輯
    // ==========================================
    const restaurants = <?php echo json_encode($restaurants); ?>;
    const items = <?php echo json_encode($items); ?>;
    
    // 取得所有不重複的地點 (例如: 心園, 理園, 輔園)
    const locations = [...new Set(restaurants.map(r => r.location))].filter(l => l);

    const breadcrumb = document.getElementById('breadcrumb');
    const listContainer = document.getElementById('menu-list-container');

    let currentView = 'locations';
    let selectedLoc = '';

    // 第一層：渲染地點
    function renderLocations() {
        currentView = 'locations';
        breadcrumb.innerHTML = `餐廳`;
        
        let html = '';
        locations.forEach(loc => {
            let locResIds = restaurants.filter(r => r.location === loc).map(r => r.r_id);
            let itemCount = items.filter(i => locResIds.includes(i.r_id)).length;
            
            html += `
                <div class="list-item" onclick="renderRestaurants('${loc}')">
                    <div style="display:flex; align-items:center;">
                        <div class="list-icon">${loc.substring(0,1)}</div>
                        <div class="list-info"><h5>${loc}</h5><p>${itemCount} 項餐點</p></div>
                    </div>
                    <div class="arrow">❯</div>
                </div>
            `;
        });
        listContainer.innerHTML = html;
    }

    // 第二層：渲染餐廳
    function renderRestaurants(loc) {
        currentView = 'restaurants';
        selectedLoc = loc;
        
        breadcrumb.innerHTML = `
            <span class="breadcrumb-back" onclick="renderLocations()">←</span>
            <span onclick="renderLocations()">餐廳</span> ❯ <span style="color:#333;">${loc}</span>
        `;
        
        let filteredRes = restaurants.filter(r => r.location === loc);
        let html = '';
        
        filteredRes.forEach(res => {
            let itemCount = items.filter(i => i.r_id == res.r_id).length;
            html += `
                <div class="list-item" onclick="renderItems(${res.r_id}, '${res.name}')">
                    <div class="list-info"><h5>${res.name}</h5><p>${itemCount} 項餐點</p></div>
                    <div class="arrow">❯</div>
                </div>
            `;
        });
        listContainer.innerHTML = html;
    }

    // 第三層：渲染餐點表格
    function renderItems(r_id, resName) {
        currentView = 'items';
        
        breadcrumb.innerHTML = `
            <span class="breadcrumb-back" onclick="renderRestaurants('${selectedLoc}')">←</span>
            <span onclick="renderLocations()">餐廳</span> ❯ 
            <span onclick="renderRestaurants('${selectedLoc}')">${selectedLoc}</span> ❯ 
            <span style="color:#002B5B;">${resName}</span>
        `;
        
        let filteredItems = items.filter(i => i.r_id == r_id);
        
        if (filteredItems.length === 0) {
            listContainer.innerHTML = `<div style="padding: 30px; text-align:center; color:#999;">此餐廳尚無餐點資料。</div>`;
            return;
        }

        let html = `
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>餐點</th><th>類別</th><th>熱量</th><th>蛋白質</th><th>價格</th><th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        filteredItems.forEach(item => {
            let cat = item.c_name ? item.c_name : '未分類';
            let cal = item.calories ? item.calories : '--';
            let pro = item.protein ? item.protein : '--';
            let price = item.price ? item.price : '--';
            
            html += `
                <tr>
                    <td><strong>${item.item_name}</strong></td>
                    <td style="color:#888;">${cat}</td>
                    <td class="td-val">${cal} <span style="font-size:11px; font-weight:normal; color:#999;">kcal</span></td>
                    <td class="td-pro">${pro} <span style="font-size:11px; font-weight:normal; color:#999;">g</span></td>
                    <td><strong>$${price}</strong></td>
                    <td>
                        <span class="action-icon" onclick="alert('準備編輯：${item.item_name}')">✏️</span>
                        <span class="action-icon delete" onclick="if(confirm('確定刪除 ${item.item_name}？')) alert('已刪除')">🗑️</span>
                    </td>
                </tr>
            `;
        });
        
        html += `</tbody></table></div>`;
        listContainer.innerHTML = html;
    }

    // 載入時預設執行
    window.onload = function() {
        renderLocations();
    };
</script>

<?php include('footer.php'); ?>