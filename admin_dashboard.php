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
    $sql_reviews = "SELECT c.*, i.name AS item_name, r.name AS res_name, r.location, a.name AS reviewer_name 
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
try {
    $item_query = $conn->query("
        SELECT 
            i.item_id,
            i.c_id,
            i.name AS item_name,
            i.price,
            i.calories,
            i.protein,
            c.cat_name AS c_name,
            c.r_id 
        FROM items i 
        JOIN categories c ON i.c_id = c.c_id
    ");
    if ($item_query) {
        while($i = $item_query->fetch_assoc()) {
            $items[] = $i;
        }
    }
} catch (mysqli_sql_exception $e) {}
?>

<style>
    body { background-color: #f4f7f9; font-family: sans-serif; }
    .admin-header { background-color: #002B5B; color: white; padding: 30px 20px 20px; }
    .admin-header h1 { margin: 0; font-size: 22px; }
    
    .tab-container { display: flex; background: white; border-bottom: 1px solid #ddd; position: sticky; top: 0; z-index: 100; }
    .tab-btn { flex: 1; text-align: center; padding: 15px 0; cursor: pointer; color: #666; font-weight: bold; }
    .tab-btn.active { color: #002B5B; border-bottom: 3px solid #002B5B; }
    .badge { background-color: #FF8C42; color: white; font-size: 12px; padding: 2px 8px; border-radius: 12px; margin-left: 5px; }

    .content-section { padding: 20px; display: none; }
    .content-section.active { display: block; }

    /* 菜單卡片與表格 */
    .menu-card { background: white; border-radius: 12px; border: 1px solid #eee; overflow: hidden; }
    .breadcrumb { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; display: flex; align-items: center; gap: 8px; font-weight: bold; }
    .breadcrumb-back { cursor: pointer; color: #002B5B; font-size: 18px; }
    
    .list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
    .list-icon { width: 40px; height: 40px; background: #002B5B; color: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; margin-right: 15px; }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { padding: 12px; border-bottom: 1px solid #eee; color: #666; text-align: left; }
    td { padding: 12px; border-bottom: 1px solid #f8f9fa; }
    .td-val { color: #FF8C42; font-weight: bold; }
    .td-pro { color: #4CAF50; font-weight: bold; }
    .action-icon { font-size: 16px; margin-right: 10px; cursor: pointer; }

    /* 編輯彈窗 Modal */
    .modal {
        display: none; position: fixed; z-index: 1000; left: 0; top: 0;
        width: 100%; height: 100%; background: rgba(0,0,0,0.5);
        align-items: center; justify-content: center;
    }
    .modal-content {
        background: white; padding: 20px; border-radius: 12px;
        width: 90%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-size: 12px; color: #666; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
    .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
    .btn-save { flex: 2; background: #002B5B; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; }
    .btn-cancel { flex: 1; background: #eee; border: none; padding: 12px; border-radius: 8px; cursor: pointer; }

    .action-icons { display: flex; flex-direction: column; gap: 5px; }
    .btn-edit { background: #002B5B; color: white; border: none; padding: 3px 8px; border-radius: 5px; cursor: pointer; font-size: 12px; }
    .btn-delete { background: #F44336; color: white; border: none; padding: 3px 8px; border-radius: 5px; cursor: pointer; font-size: 12px; }

    /* 💡 統一的登出按鈕樣式 */
    .logout-section { text-align: center; margin: 30px 0 100px; }
    .logout-btn {
        display: inline-block;
        background-color: white;
        color: #F44336;
        border: 1.5px solid #FFCDD2;
        padding: 10px 40px;
        border-radius: 25px;
        text-decoration: none;
        font-size: 15px;
        font-weight: bold;
        transition: 0.2s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .logout-btn:active { background-color: #FFF5F5; transform: scale(0.95); }
</style>

<div class="admin-header">
    <h1>管理員工作台</h1>
    <p>KaFu 系統管理與審核</p>
</div>

<div class="tab-container">
    <div class="tab-btn active" onclick="switchTab('reviews')">審核評價 <span class="badge"><?php echo $pending_count; ?></span></div>
    <div class="tab-btn" onclick="switchTab('menu')">菜單維護</div>
</div>

<div id="tab-reviews" class="content-section active">
    <?php if ($pending_count > 0): ?>
        <?php while($rev = $reviews_result->fetch_assoc()): ?>
            <div style="background:white; padding:15px; border-radius:12px; margin-bottom:15px; border:1px solid #eee;">
                <strong><?php echo htmlspecialchars($rev['reviewer_name'] ?? '匿名'); ?></strong> 
                <span style="color:#999; font-size:11px;"><?php echo $rev['created_at']; ?></span>
                <p style="margin:10px 0;"><?php echo nl2br(htmlspecialchars($rev['content'])); ?></p>
                <div style="display:flex; gap:10px;">
                    <button onclick="reviewAction(<?php echo $rev['com_id']; ?>, 'approve')" style="background:#4CAF50; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">通過</button>
                    <button onclick="reviewAction(<?php echo $rev['com_id']; ?>, 'reject')" style="background:#F44336; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">拒絕</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color:#999; margin-top:50px;">目前沒有待處理評價</p>
    <?php endif; ?>
</div>

<div id="tab-menu" class="content-section">
    <div class="menu-card">
        <div id="breadcrumb" class="breadcrumb"></div>
        <div id="menu-list-container"></div>
    </div>
</div>

<div class="logout-section">
    <a href="logout.php" class="logout-btn">登出</a>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">編輯餐點資料</h3>
        <input type="hidden" id="edit-id">
        <div class="form-group">
            <label>餐點名稱</label>
            <input type="text" id="edit-name">
        </div>
        <div class="form-group">
            <label>價格 ($)</label>
            <input type="number" id="edit-price">
        </div>
        <div class="form-group">
            <label>熱量 (kcal)</label>
            <input type="number" id="edit-calories">
        </div>
        <div class="form-group">
            <label>蛋白質 (g)</label>
            <input type="number" step="0.1" id="edit-protein">
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">取消</button>
            <button class="btn-save" onclick="saveEdit()">確定修改</button>
        </div>
    </div>
</div>

<script>
    function reviewAction(id, action) {
        if (action === 'reject' && !confirm('確定要拒絕並刪除這則評價嗎？')) return;

        const formData = new FormData();
        formData.append('action', action);
        formData.append('com_id', id);

        fetch('manage_review_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(action === 'approve' ? '評價已通過審核！' : '評價已刪除。');
                location.reload(); 
            } else {
                alert('操作失敗：' + data.message);
            }
        })
        .catch(err => console.error('Error:', err));
    }

    const restaurants = <?php echo json_encode($restaurants); ?>;
    const items = <?php echo json_encode($items); ?>;
    const locations = [...new Set(restaurants.map(r => r.location))].filter(l => l);

    const breadcrumb = document.getElementById('breadcrumb');
    const listContainer = document.getElementById('menu-list-container');
    let selectedLoc = '';

    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }

    function renderLocations() {
        breadcrumb.innerHTML = `餐廳`;
        let html = '';
        locations.forEach(loc => {
            html += `
                <div class="list-item" onclick="renderRestaurants('${loc}')">
                    <div style="display:flex; align-items:center;">
                        <div class="list-icon">${loc[0]}</div>
                        <h5>${loc}</h5>
                    </div>
                    <span>❯</span>
                </div>`;
        });
        listContainer.innerHTML = html;
    }

    function renderRestaurants(loc) {
        selectedLoc = loc;
        breadcrumb.innerHTML = `<span class="breadcrumb-back" onclick="renderLocations()">←</span> <span onclick="renderLocations()" style="cursor:pointer;">餐廳</span> ❯ ${loc}`;
        let html = '';
        restaurants.filter(r => r.location === loc).forEach(res => {
            html += `
                <div class="list-item" onclick="renderItems(${res.r_id}, '${res.name}')">
                    <h5>${res.name}</h5>
                    <span>❯</span>
                </div>`;
        });
        listContainer.innerHTML = html;
    }

    function renderItems(r_id, resName) {
        breadcrumb.innerHTML = `<span class="breadcrumb-back" onclick="renderRestaurants('${selectedLoc}')">←</span> 餐廳 ❯ ${selectedLoc} ❯ ${resName}`;
        let filteredItems = items.filter(i => i.r_id == r_id);
        let html = `<table><thead><tr><th>餐點</th><th>熱量</th><th>蛋白</th><th>價格</th><th>操作</th></tr></thead><tbody>`;
        
        filteredItems.forEach(item => {
            html += `
                <tr>
                    <td><strong>${item.item_name}</strong></td>
                    <td class="td-val">${item.calories}</td>
                    <td class="td-pro">${item.protein}g</td>
                    <td>$${item.price}</td>
                    <td>
                        <div class="action-icons">
                            <button class="btn-edit" onclick='openEditModal(${JSON.stringify(item)})'>編輯</button>
                            <button class="btn-delete" onclick="deleteItem(${item.item_id}, '${item.item_name}')">刪除</button>
                        </div>
                    </td>
                </tr>`;
        });
        html += `</tbody></table>`;
        listContainer.innerHTML = html;
    }

    function openEditModal(item) {
        document.getElementById('edit-id').value = item.item_id;
        document.getElementById('edit-name').value = item.item_name;
        document.getElementById('edit-price').value = item.price;
        document.getElementById('edit-calories').value = item.calories;
        document.getElementById('edit-protein').value = item.protein;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeModal() { document.getElementById('editModal').style.display = 'none'; }

    function saveEdit() {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('item_id', document.getElementById('edit-id').value);
        formData.append('name', document.getElementById('edit-name').value);
        formData.append('price', document.getElementById('edit-price').value);
        formData.append('calories', document.getElementById('edit-calories').value);
        formData.append('protein', document.getElementById('edit-protein').value);

        fetch('manage_menu_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) { alert('更新成功！'); location.reload(); }
            else alert('錯誤：' + data.message);
        });
    }

    function deleteItem(id, name) {
        if(!confirm(`確定刪除「${name}」？`)) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('item_id', id);

        fetch('manage_menu_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) { alert('已刪除'); location.reload(); }
        });
    }

    window.onload = renderLocations;
</script>

<?php include('footer.php'); ?>