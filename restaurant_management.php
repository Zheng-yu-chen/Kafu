<?php
session_start();
include('db.php');
include('header.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$r_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 0;

// 🔒 安全驗證：如果未登入，或者角色不是系統管理員(1)及店家(2)，直接拒絕
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    echo "<div style='padding:50px; text-align:center; color:red; font-weight:bold;'>您沒有權限訪問此管理後台。</div>";
    include('footer.php');
    exit();
}

// 🎯 核心防安全漏洞機制：
// 如果是店家(role_id == 2)，檢查 Session 裡的 r_id 與目前網址的 r_id 是否相符！
$is_system_admin = ($_SESSION['role_id'] == 1); // 最高管理員
$is_shop_owner = ($_SESSION['role_id'] == 2 && isset($_SESSION['r_id']) && $_SESSION['r_id'] == $r_id);

if (!$is_system_admin && !$is_shop_owner) {
    echo "<div style='padding:50px; text-align:center; color:red; font-weight:bold;'>❌ 權限錯誤：您只能編輯您自己名下的餐廳餐點！</div>";
    include('footer.php');
    exit();
}

if ($r_id === 0) {
    echo "<div style='padding:50px; text-align:center;'>找不到指定的餐廳。</div>";
    include('footer.php');
    exit();
}

// 🟢 新增：撈取此餐廳的「所有餐點分類」，供新增餐點的下拉選單使用
$cat_sql = "SELECT c_id, cat_name FROM categories WHERE r_id = $r_id ORDER BY c_id ASC";
$cat_result = $conn->query($cat_sql);
$categories = [];
if ($cat_result && $cat_result->num_rows > 0) {
    while($c = $cat_result->fetch_assoc()) {
        $categories[] = $c;
    }
}

$res_sql = "SELECT r.name, r.location FROM restaurants r WHERE r.r_id = $r_id";
$res_result = $conn->query($res_sql);
$res_name = "餐廳資訊";
$res_loc = "未知位置";
$res_place = ""; 

if ($res_result && $res_result->num_rows > 0) {
    $res_data = $res_result->fetch_assoc();
    $res_name = $res_data['name'];
    $res_loc = $res_data['location'];
}

$origin_sql = "SELECT meat_type, country FROM meatorigins WHERE r_id = $r_id";
$origin_result = $conn->query($origin_sql);
$origin_list = [];

if ($origin_result && $origin_result->num_rows > 0) {
    while ($ori_data = $origin_result->fetch_assoc()) {
        $origin_list[] = $ori_data['meat_type'] . " : " . $ori_data['country'];
    }
    $res_place = implode(' / ', $origin_list); 
}

// 撈出目前所有在上架狀態(item_status = 1)的餐點
$sql = "SELECT i.item_id, i.name, i.price, i.calories, i.protein, i.fat, i.carbs, i.is_vegetarian
        FROM items i
        JOIN categories c ON i.c_id = c.c_id
        WHERE c.r_id = $r_id 
        AND i.item_status = 1 
        ORDER BY i.c_id ASC, i.item_id ASC";

$result = $conn->query($sql);
?>

<style>
    /* 🎨 全域視覺基準：完美沿用前台的輔大藍與活力橘 */
        :root {
            --fujen-blue: #002B5B;
            --primary-orange: #FF8C42;
            --btn-orange: #E6762D;
            --btn-orange-hover: #FF8336;
            --shadow-orange: #B35C22;
        }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #333; }
    /* 🔝 頂部區塊：與前台一致，使用輔大藍色調 */
    .header-section { background-color: var(--fujen-blue); color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }
    .header-title h1 { margin: 0; font-size: 24px; }
    .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }
    .menu-container { padding: 20px; }   
    /* ➕ 新增餐點按鈕樣式 */
    .add-item-trigger { position: absolute; right: 20px; bottom: 20px; background-color: var(--btn-orange); color: white; border: none; padding: 10px 16px; border-radius: 20px; font-weight: bold; font-size: 14px; cursor: pointer; box-shadow: 0 4px 0 var(--shadow-orange); transition: 0.1s; }
    .add-item-trigger:active { transform: translateY(2px); box-shadow: 0 2px 0 var(--shadow-orange); }
    .item-card { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    
    .nutrition { font-size: 13px; margin-top: 8px; color: #666; display: flex; flex-direction: column; gap: 5px; }
    .nutrition-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    
    .price-tag { color: #E53935; font-weight: bold; font-size: 14px; }
    .pro-tag { color: #FF8C42; font-weight: bold; }
    .fire-icon { width: 14px; height: 14px; object-fit: contain; vertical-align: middle; margin-right: 2px; margin-bottom: 2px; }
    .dest-icon { width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px; margin-bottom: 3px; opacity: 0.9; }
    
    .action-icons { display: flex; flex-direction: column; gap: 5px; }
    .btn-edit { background: #002B5B; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold; }
    .btn-delete { background: #F44336; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold; }

    /* 🟩 彈出視窗 (Modal) 樣式：完全對齊你的設計規格 */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; justify-content: center; align-items: center; padding: 20px; }
        .modal-box { background: white; width: 100%; max-width: 340px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { background-color: var(--fujen-blue); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .modal-header h2 { margin: 0; font-size: 20px; }
        .modal-header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; font-weight: normal; }
        .close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; }
        
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: bold; }
        .required::after { content: " *"; color: #F44336; }
        
        .clean-input { padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; width: 100%; box-sizing: border-box; }
        .meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .submit-tray-btn { width: 100%; background-color: var(--btn-orange); color: white; padding: 15px; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; transition: background 0.3s, transform 0.1s, box-shadow 0.1s; box-shadow: 0 4px 0 var(--shadow-orange); }
        .submit-tray-btn:hover { background-color: var(--btn-orange-hover); }
        .submit-tray-btn:active { transform: translateY(2px); box-shadow: 0 2px 0 var(--shadow-orange); }
</style>

<div class="header-section">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <span class="back-btn" style="background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">🛠️ 店家管理後台</span>
        <!-- 🟢 新增：頂部「+ 新增餐點」快捷按鈕 -->
        <button onclick="openAddModal()" style="background: #FF8C42; color: white; border: none; padding: 6px 14px; border-radius: 6px; font-weight: bold; font-size: 13px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            + 新增餐點
        </button>
    </div>
    <div class="header-title" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; margin-top: 5px;">
        <h1 style="margin: 0; font-size: 24px; color: #ffffff;"><?php echo htmlspecialchars($res_name); ?> — 菜單維護</h1>
        <p style="margin: 0; display: flex; align-items: center; gap: 4px;">
            <img src="icon/destination_icon.png" alt="地點" class="dest-icon">
            <?php echo htmlspecialchars($res_loc); ?>
        </p>
    </div>
</div>

<div class="menu-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="item-card" 
                 data-item-id="<?php echo $row['item_id']; ?>" 
                 data-item-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>" 
                 data-price="<?php echo floatval($row['price']); ?>" 
                 data-calories="<?php echo htmlspecialchars($row['calories']); ?>" 
                 data-protein="<?php echo htmlspecialchars($row['protein']); ?>" 
                 data-fat="<?php echo htmlspecialchars($row['fat']); ?>" 
                 data-carbs="<?php echo htmlspecialchars($row['carbs']); ?>">
                
                <div class="item-info" style="flex: 1;">
                    <h4 style="margin: 0; font-size: 16px; color: #002B5B;"><?php echo htmlspecialchars($row['name']); ?></h4>
                    <div class="nutrition">
                        <div class="nutrition-row">
                            <span class="price-tag">$<?php echo floatval($row['price']); ?></span>
                            <span><img src="icon/fire_icon.png" alt="熱量" class="fire-icon"> <?php echo ($row['calories'] !== null) ? $row['calories'] : '---'; ?> kcal</span>
                        </div>
                        <div class="nutrition-row">
                                <span class="pro-tag">蛋白質 <?php echo isset($row['protein']) ? $row['protein'] : '0'; ?>g</span>
                                <span class="pro-tag">脂肪 <?php echo isset($row['fat']) ? $row['fat'] : '0'; ?>g</span>
                                <span class="pro-tag">碳水 <?php echo isset($row['carbs']) ? $row['carbs'] : '0'; ?>g</span>
                            </div>
                    </div>
                </div>
                
                <div class="action-icons" style="margin-left: 15px; flex-shrink: 0;">
                    <button class="btn-edit" onclick="openEditModal(<?php echo $row['item_id']; ?>)">編輯</button>
                    <button class="btn-delete" onclick="deleteItem(<?php echo $row['item_id']; ?>)">刪除</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #999;">目前沒有餐點。</div>
    <?php endif; ?>
</div>

<!-- 🟢 新增：新增餐點 Modal -->
<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>新增餐點</h2>
                <p>填寫新餐點的各項資料</p>
            </div>
            <button class="close-btn" onclick="closeAddModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>餐點分類 <span style="color:red;">*</span></label>
                <select id="addCategory" class="date-input" style="width: 100%; box-sizing: border-box;">
                    <?php if(!empty($categories)): ?>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['c_id']; ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">-- 請先至後台建立分類 --</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>餐點名稱 <span style="color:red;">*</span></label>
                <input id="addName" class="date-input" style="width: 100%; box-sizing: border-box;" placeholder="例如：香蒜吐司" />
            </div>
            <div class="form-group">
                <label>價格 <span style="color:red;">*</span></label>
                <input id="addPrice" type="number" step="0.01" class="date-input" style="width: 100%; box-sizing: border-box;" placeholder="例如：45" />
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>熱量 (kcal)</label>
                    <input id="addCalories" type="number" class="date-input" style="width: 100%; box-sizing: border-box;" placeholder="0" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>蛋白質 (g)</label>
                    <input id="addProtein" type="number" step="0.1" class="date-input" style="width: 100%; box-sizing: border-box;" placeholder="0" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>脂肪 (g)</label>
                    <input id="addFat" type="number" step="0.1" class="date-input" style="width: 100%; box-sizing: border-box;" placeholder="0" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>碳水 (g)</label>
                    <input id="addCarbs" type="number" step="0.1" class="date-input" style="width: 100%; box-sizing: border-box;" placeholder="0" />
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button class="submit-tray-btn" onclick="saveAdd()" type="button" style="width: 100%; box-sizing: border-box;">確定新增</button>
            </div>
        </div>
    </div>
</div>

<!-- 編輯餐點 Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>編輯餐點</h2>
                <p id="editModalItem">調整價格與營養資訊</p>
            </div>
            <button class="close-btn" onclick="closeEditModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editItemId" value="">
            <div class="form-group">
                <label>名稱</label>
                <input id="editName" class="date-input" style="width: 100%; box-sizing: border-box;" />
            </div>
            <div class="form-group">
                <label>價格</label>
                <input id="editPrice" type="number" step="0.01" class="date-input" style="width: 100%; box-sizing: border-box;" />
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>熱量 (kcal)</label>
                    <input id="editCalories" type="number" class="date-input" style="width: 100%; box-sizing: border-box;" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>蛋白質 (g)</label>
                    <input id="editProtein" type="number" step="0.1" class="date-input" style="width: 100%; box-sizing: border-box;" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>脂肪 (g)</label>
                    <input id="editFat" type="number" step="0.1" class="date-input" style="width: 100%; box-sizing: border-box;" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>碳水 (g)</label>
                    <input id="editCarbs" type="number" step="0.1" class="date-input" style="width: 100%; box-sizing: border-box;" />
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button class="submit-tray-btn" onclick="saveEdit()" type="button" style="width: 100%; box-sizing: border-box;">儲存</button>
            </div>
        </div>
    </div>
</div>

<script>
// 🟢 新增：打開與關閉新增視窗的邏輯
function openAddModal() {
    document.getElementById('addName').value = '';
    document.getElementById('addPrice').value = '';
    document.getElementById('addCalories').value = '';
    document.getElementById('addProtein').value = '';
    document.getElementById('addFat').value = '';
    document.getElementById('addCarbs').value = '';
    document.getElementById('addModal').style.display = 'flex';
}
function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }

// 點擊 Modal 外部背景處直接關閉 (新增與編輯皆適用)
document.getElementById('addModal').addEventListener('click', function(e) { if (e.target === this) closeAddModal(); });
document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });

// 🟢 新增：送出新餐點的 Fetch 非同步請求
function saveAdd() {
    const c_id = document.getElementById('addCategory').value;
    const name = document.getElementById('addName').value;
    const price = document.getElementById('addPrice').value;

    if (!c_id) { alert('此餐廳目前沒有建立任何餐點分類，無法新增！'); return; }
    if (!name || !price) { alert('請填寫餐點名稱與價格！'); return; }

    const data = new FormData();
    data.append('action', 'add'); // 對應 API 中的 add 行為
    data.append('c_id', c_id);
    data.append('name', name);
    data.append('price', price);
    data.append('calories', document.getElementById('addCalories').value);
    data.append('protein', document.getElementById('addProtein').value);
    data.append('fat', document.getElementById('addFat').value);
    data.append('carbs', document.getElementById('addCarbs').value);

    fetch('manage_menu_api.php', { method: 'POST', body: data })
    .then(res => res.json())
    .then(resp => { 
        if (resp.success) { 
            alert('餐點新增成功！');
            location.reload(); 
        } else { 
            alert('新增失敗：' + (resp.error || '欄位錯誤')); 
        } 
    })
    .catch(err => { console.error(err); alert('網路錯誤'); });
}

// 編輯餐點
function openEditModal(itemId) {
    const card = document.querySelector('.item-card[data-item-id="' + itemId + '"]');
    if (!card) return;
    document.getElementById('editItemId').value = itemId;
    document.getElementById('editName').value = card.getAttribute('data-item-name') || '';
    document.getElementById('editPrice').value = card.getAttribute('data-price') || '';
    document.getElementById('editCalories').value = card.getAttribute('data-calories') || '';
    document.getElementById('editProtein').value = card.getAttribute('data-protein') || '';
    document.getElementById('editFat').value = card.getAttribute('data-fat') || '';
    document.getElementById('editCarbs').value = card.getAttribute('data-carbs') || '';
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

function saveEdit() {
    const data = new FormData();
    data.append('action', 'update');
    data.append('item_id', document.getElementById('editItemId').value);
    data.append('name', document.getElementById('editName').value);
    data.append('price', document.getElementById('editPrice').value);
    data.append('calories', document.getElementById('editCalories').value);
    data.append('protein', document.getElementById('editProtein').value);
    data.append('fat', document.getElementById('editFat').value);
    data.append('carbs', document.getElementById('editCarbs').value);

    fetch('manage_menu_api.php', { method: 'POST', body: data })
    .then(res => res.json())
    .then(resp => { if (resp.success) { location.reload(); } else { alert('更新失敗：' + (resp.error || '錯誤')); } })
    .catch(err => { console.error(err); alert('網路錯誤'); });
}

function deleteItem(itemId) {
    if (!confirm('確定要刪除此餐點？此操作無法回復。')) return;
    const data = new FormData();
    data.append('action', 'delete');
    data.append('item_id', itemId);

    fetch('manage_menu_api.php', { method: 'POST', body: data })
    .then(res => res.json())
    .then(resp => { if (resp.success) { location.reload(); } else { alert('刪除失敗'); } })
    .catch(err => { console.error(err); alert('網路錯誤'); });
}
</script>
<?php include('footer.php'); ?>