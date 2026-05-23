<?php
session_start();
include('db.php');
include('header.php');

// 🎯 確保這段 PHP 代碼獨立存在，不要被包在任何 <div> 裡面
if (isset($_GET['from']) && $_GET['from'] === 'dice') {
    $safe_name = htmlspecialchars($_GET['name']);
    echo <<<HTML
    <div id="dice-welcome-bubble" style="
        position: fixed; 
        top: 25px; 
        left: 50%; 
        transform: translateX(-50%); 
        z-index: 99999; 
        background: #FF8C42; 
        color: white; 
        padding: 12px 25px; 
        border-radius: 50px; 
        font-weight: bold; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        white-space: nowrap;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
    ">
        現在，我想來點... {$safe_name} 😋
    </div>

    <style>
        #dice-welcome-bubble {
            animation: slideInDice 0.5s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }
        @keyframes slideInDice {
            from { top: -80px; opacity: 0; }
            to { top: 25px; opacity: 1; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bubble = document.getElementById('dice-welcome-bubble');
            if (bubble) {
                setTimeout(() => {
                    bubble.style.transition = 'opacity 0.6s, transform 0.6s';
                    bubble.style.opacity = '0';
                    bubble.style.transform = 'translateX(-50%) translateY(-20px)';
                    setTimeout(() => bubble.remove(), 600);
                }, 2500);
            }
        });
    </script>
HTML;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$r_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 0;

if ($r_id === 0) {
    echo "<div style='padding:50px; text-align:center;'>找不到指定的餐廳。</div>";
    include('footer.php');
    exit();
}

$res_sql = "SELECT r.name, r.location 
            FROM restaurants r 
            WHERE r.r_id = $r_id";

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

// 🟢 撈取此餐廳的分類，供新增餐點的下拉選單使用
$cat_sql = "SELECT c_id, cat_name FROM categories WHERE r_id = $r_id";
$cat_result = $conn->query($cat_sql);
$categories = [];
if ($cat_result && $cat_result->num_rows > 0) {
    while($c = $cat_result->fetch_assoc()) {
        $categories[] = $c;
    }
}

$sql = "SELECT i.item_id, i.name, i.price, i.calories, i.protein, i.fat, i.carbs, i.is_vegetarian
        FROM items i
        JOIN categories c ON i.c_id = c.c_id
        WHERE c.r_id = $r_id 
        AND i.item_status = 1 
        ORDER BY i.c_id ASC, i.item_id ASC";

$result = $conn->query($sql);
?>

<style>
    .header-section { background-color: var(--fujen-blue, #002B5B); color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }
    .header-title h1 { margin: 0; font-size: 24px; }
    .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }
    .menu-container { padding: 20px; }
    .item-card { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .item-info h4 { margin: 0; font-size: 16px; color: var(--fujen-blue, #002B5B); }
    
    .nutrition { font-size: 13px; margin-top: 8px; color: #666; display: flex; flex-direction: column; gap: 5px; }
    .nutrition-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    
    .price-tag { color: #E53935; font-weight: bold; font-size: 14px; }
    .pro-tag { color: var(--primary-orange, #FF8C42); font-weight: bold; }

    .fire-icon { width: 14px; height: 14px; object-fit: contain; vertical-align: middle; margin-right: 2px; margin-bottom: 2px; }
    .dest-icon { width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px; margin-bottom: 3px; opacity: 0.9; }
    
    .add-btn { background: var(--fujen-blue, #002B5B); color: white; width: 34px; height: 34px; display: flex; justify-content: center; align-items: center; border-radius: 50%; border: none; font-size: 20px; font-weight: bold; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,43,91,0.2); cursor: pointer; }
    .add-btn:active { transform: scale(0.95); }

    .action-icons { display: flex; flex-direction: column; gap: 5px; }
    .btn-edit { background: #002B5B; color: white; border: none; padding: 3px 8px; border-radius: 5px; cursor: pointer; font-size: 12px; }
    .btn-delete { background: #F44336; color: white; border: none; padding: 3px 8px; border-radius: 5px; cursor: pointer; font-size: 12px; }

    /* ================= 彈出視窗專屬樣式 ================= */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5); z-index: 10000; 
        justify-content: center; align-items: center; padding: 20px;
    }
    .modal-box {
        background: white; width: 100%; max-width: 320px; border-radius: 12px; overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: slideUp 0.3s ease;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    
    .modal-header {
        background-color: var(--fujen-blue, #002B5B); color: white; padding: 20px;
        display: flex; justify-content: space-between; align-items: flex-start;
    }
    .modal-header h2 { margin: 0; font-size: 20px; }
    .modal-header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; font-weight: normal; }
    .close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; }
    
    .modal-body { padding: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: bold; }
    
    .date-input-wrapper { position: relative; }
    .date-icon { position: absolute; left: 12px; top: 12px; font-size: 16px; color: #555; pointer-events: none; }
    .date-input { width: 100%; padding: 12px 12px 12px 35px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
    /* 沒有 icon 的 input */
    .clean-input { padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; width: 100%; box-sizing: border-box; }

    .meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .meal-option { display: block; }
    .meal-option input { display: none; }
    .meal-option span { 
        display: block; text-align: center; padding: 12px; background: #eee; color: #333; border-radius: 8px; font-size: 14px; cursor: pointer; box-sizing: border-box; border: 1px solid transparent; transition: transform 0.1s ease, background 0.2s, color 0.2s; 
    }
    .meal-option span:active { transform: scale(0.94); }
    .meal-option input:checked + span { background: var(--fujen-blue, #002B5B); color: rgb(255, 255, 255); font-weight: bold; }
    
    .qty-control { display: flex; align-items: center; gap: 10px; }
    .qty-btn { width: 45px; height: 45px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; font-size: 20px; cursor: pointer; transition: 0.2s; }
    .qty-btn:active { background: #eee; }
    .qty-input { flex: 1; text-align: center; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }

    .submit-tray-btn {
        width: 100%; background-color: #E6762D; color: white; padding: 15px; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; transition: background 0.3s, transform 0.1s, box-shadow 0.1s; box-shadow: 0 4px 0 #B35C22; 
    }
    .submit-tray-btn:hover { background-color: #FF8336; }
    .submit-tray-btn:active { transform: translateY(2px); box-shadow: 0 2px 0 #B35C22; }

    @keyframes fadeInOut {
        0% { opacity: 0; transform: translate(-50%, 20px); }
        15% { opacity: 1; transform: translate(-50%, 0); }
        85% { opacity: 1; transform: translate(-50%, 0); }
        100% { opacity: 0; transform: translate(-50%, -20px); }
    }
    #toast-container { position: fixed; top: 60px; left: 50%; z-index: 100000; pointer-events: none; }
</style>

<div class="header-section">
    <a href="index.php" class="back-btn">❮ 返回店家列表</a>
    
    <div class="header-title" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; width: 100%;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h1 style="margin: 0; font-size: 24px; color: #ffffff;">
                <?php echo htmlspecialchars($res_name); ?>
            </h1>
            
            <?php if (isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1,2])): ?>
                <button onclick="openAddModal()" style="background: var(--primary-orange, #FF8C42); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-weight: bold; font-size: 13px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">+ 新增餐點</button>
            <?php endif; ?>
        </div>
        
        <p style="margin: 0; display: flex; align-items: center; gap: 4px;">
            <img src="icon/destination_icon.png" alt="地點" class="dest-icon">
            <?php echo htmlspecialchars($res_loc); ?> • 餐點列表
        </p>
        
        <?php if (!empty($res_place)): ?>
            <div style="margin-top: 2px;">
                <span style="font-size: 11px; background-color: rgba(255, 255, 255, 0.15); color: #ffffff; padding: 3px 8px; border-radius: 5px; font-weight: 500; display: inline-flex; align-items: center; backdrop-filter: blur(2px);">
                    🥩 肉類來源：<?php echo htmlspecialchars($res_place); ?>
                </span>
            </div>
        <?php endif; ?>
        
    </div>
</div>
<div class="menu-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php 
                $is_admin_or_owner = isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1,2]);
                $is_user = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3;
                $show_add_btn = !$is_admin_or_owner; 

                $is_fav = false;
                if ($is_user && isset($_SESSION['u_id'])) {
                    $u_id = $_SESSION['u_id'];
                    $item_id = $row['item_id'];
                    $fav_check_sql = "SELECT 1 FROM favorites WHERE u_id = $u_id AND item_id = $item_id";
                    $fav_check_res = $conn->query($fav_check_sql);
                    if ($fav_check_res && $fav_check_res->num_rows > 0) {
                        $is_fav = true;
                    }
                }
            ?>
            <div class="item-card" data-item-id="<?php echo $row['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>" data-price="<?php echo floatval($row['price']); ?>" data-calories="<?php echo htmlspecialchars($row['calories'] ?? ''); ?>" data-protein="<?php echo htmlspecialchars($row['protein'] ?? ''); ?>" data-fat="<?php echo htmlspecialchars($row['fat'] ?? ''); ?>" data-carbs="<?php echo htmlspecialchars($row['carbs'] ?? ''); ?>">
                
                <div class="item-info" style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                    <?php if ($is_user): ?>
                        <button class="favorite-btn <?php echo $is_fav ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(this, <?php echo $row['item_id']; ?>)" 
                            style="background: none; border: none; cursor: pointer; padding: 0; font-size: 20px; line-height: 1; margin-top: 2px; flex-shrink: 0;">
                            <?php echo $is_fav ? '❤️' : '🤍'; ?>
                        </button>
                    <?php endif; ?>

                    <div style="flex: 1;">
                        <h4 class="item-name"><?php echo htmlspecialchars($row['name']); ?></h4>
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
                </div>
                
                <?php if ($show_add_btn): ?>
                    <button class="add-btn" onclick="openTrayModal(<?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')" 
                            style="margin-left: 15px; flex-shrink: 0;">+</button>
                <?php elseif ($is_admin_or_owner): ?>
                    <div class="action-icons" style="margin-left: 15px; flex-shrink: 0;">
                        <button class="btn-edit" onclick="openEditModal(<?php echo $row['item_id']; ?>)">編輯</button>
                        <button class="btn-delete" onclick="deleteItem(<?php echo $row['item_id']; ?>)">刪除</button>
                    </div>
                <?php endif; ?>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #999;">
            <div style="font-size: 30px; margin-bottom: 10px;">🥗</div>
            目前這間餐廳還沒有上架餐點喔！
        </div>
    <?php endif; ?>
</div>

<div id="trayModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>加入托盤</h2>
                <p id="modalItemName">餐點名稱</p>
            </div>
            <button class="close-btn" onclick="closeTrayModal()">×</button>
        </div>
        <form action="add_to_tray.php" method="POST">
            <input type="hidden" name="item_id" id="modalItemId" value="">
            <div class="modal-body">
                <?php if (isset($_SESSION['u_id'])): ?>
                    <div class="form-group">
                        <label>用餐日期</label>
                        <div class="date-input-wrapper">
                            <span class="date-icon">📅</span>
                            <input type="date" name="eat_date" class="date-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>用餐時段</label>
                        <div class="meal-grid">
                            <label class="meal-option"><input type="radio" name="meal_time" value="早餐" required><span>早餐</span></label>
                            <label class="meal-option"><input type="radio" name="meal_time" value="午餐"><span>午餐</span></label>
                            <label class="meal-option"><input type="radio" name="meal_time" value="晚餐"><span>晚餐</span></label>
                            <label class="meal-option"><input type="radio" name="meal_time" value="點心"><span>點心</span></label>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="eat_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="hidden" name="meal_time" value="全天">
                    <div style="text-align:center; color:#888; padding: 10px 0 20px; font-size:14px; line-height: 1.5;">
                        <span style="font-size:24px; display:block; margin-bottom:5px;">👣</span>
                        您目前為訪客模式<br>將直接暫存於托盤中
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>餐點份數 / 數量</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="number" name="quantity" id="modalQty" value="1" min="1" max="99" class="qty-input">
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>
                <button type="submit" class="submit-tray-btn">確認加入</button>
            </div>
        </form>
    </div>
</div>

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
                <input id="editName" class="clean-input" />
            </div>
            <div class="form-group">
                <label>價格</label>
                <input id="editPrice" type="number" step="1" class="clean-input" />
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>熱量 (kcal)</label>
                    <input id="editCalories" type="number" class="clean-input" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>蛋白質 (g)</label>
                    <input id="editProtein" type="number" step="0.1" class="clean-input" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>脂肪 (g)</label>
                    <input id="editFat" type="number" step="0.1" class="clean-input" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>碳水 (g)</label>
                    <input id="editCarbs" type="number" step="0.1" class="clean-input" />
                </div>
            </div>
            <div style="margin-top: 20px;">
                <button class="submit-tray-btn" onclick="saveEdit()" type="button">儲存修改</button>
            </div>
        </div>
    </div>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>新增餐點</h2>
                <p>輸入餐點與營養資訊</p>
            </div>
            <button class="close-btn" onclick="closeAddModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>餐點分類</label>
                <select id="addCategory" class="clean-input">
                    <?php if(!empty($categories)): ?>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['c_id']; ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">請先至後台新增分類</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>名稱</label>
                <input id="addName" class="clean-input" placeholder="例如：香蒜吐司" />
            </div>
            <div class="form-group">
                <label>價格 ($)</label>
                <input id="addPrice" type="number" step="1" class="clean-input" placeholder="例如：45" />
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>熱量 (kcal)</label>
                    <input id="addCalories" type="number" class="clean-input" placeholder="例如：320" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>蛋白質 (g)</label>
                    <input id="addProtein" type="number" step="0.1" class="clean-input" placeholder="0" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>脂肪 (g)</label>
                    <input id="addFat" type="number" step="0.1" class="clean-input" placeholder="0" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>碳水 (g)</label>
                    <input id="addCarbs" type="number" step="0.1" class="clean-input" placeholder="0" />
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button class="submit-tray-btn" onclick="saveNewItem()" type="button">確定新增</button>
            </div>
        </div>
    </div>
</div>

<div id="toast-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 100000;"></div>

<script>
    // --- 提示訊息 ---
    function showToast(message) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.style.cssText = `position: absolute; left: 50%; background: rgba(0, 0, 0, 0.85); color: white; padding: 12px 24px; border-radius: 50px; font-size: 14px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.3); white-space: nowrap; animation: fadeInOut 2s ease-in-out forwards; pointer-events: none;`;
        toast.innerText = message; 
        container.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 2000);
    }

    // --- 托盤功能 ---
    function openTrayModal(itemId, itemName) {
        document.getElementById('modalItemId').value = itemId;
        document.getElementById('modalItemName').innerText = itemName;
        document.getElementById('modalQty').value = 1;
        document.getElementById('trayModal').style.display = 'flex';
    }
    function changeQty(amt) {
        const qtyInput = document.getElementById('modalQty');
        let currentVal = parseInt(qtyInput.value) || 1;
        let newVal = currentVal + amt;
        if (newVal < 1) newVal = 1;
        qtyInput.value = newVal;
    }
    function closeTrayModal() { document.getElementById('trayModal').style.display = 'none'; }
    document.getElementById('trayModal').addEventListener('click', function(e) { if (e.target === this) closeTrayModal(); });

    // --- 收藏功能 ---
    function toggleFavorite(btn, itemId) {
        const formData = new FormData();
        formData.append('item_id', itemId);
        fetch('save_favorite.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.status === 'added') {
                    btn.innerText = '❤️'; btn.classList.add('active'); showToast('收藏餐點');
                } else {
                    btn.innerText = '🤍'; btn.classList.remove('active'); showToast('取消收藏餐點');
                }
            } else { alert('操作失敗，請先登入！'); }
        })
        .catch(error => console.error('Error:', error));
    }

    // --- 編輯功能 ---
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
        .then(resp => {
            if (resp.success) location.reload();
            else alert('更新失敗：' + (resp.error || '伺服器回應錯誤'));
        })
        .catch(err => { console.error(err); alert('網路錯誤'); });
    }

    // --- 刪除功能 ---
    function deleteItem(itemId) {
        if (!confirm('確定要刪除此餐點？此操作無法回復。')) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('item_id', itemId);

        fetch('manage_menu_api.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) location.reload();
            else alert('刪除失敗');
        })
        .catch(err => { console.error(err); alert('網路錯誤'); });
    }

    // --- 🌟 新增功能 ---
    function openAddModal() {
        // 清空欄位
        document.getElementById('addName').value = '';
        document.getElementById('addPrice').value = '';
        document.getElementById('addCalories').value = '';
        document.getElementById('addProtein').value = '';
        document.getElementById('addFat').value = '';
        document.getElementById('addCarbs').value = '';
        document.getElementById('addModal').style.display = 'flex';
    }
    
    function closeAddModal() { 
        document.getElementById('addModal').style.display = 'none'; 
    }
    
    function saveNewItem() {
        const c_id = document.getElementById('addCategory').value;
        const name = document.getElementById('addName').value;
        const price = document.getElementById('addPrice').value;

        if (!c_id) {
            alert('沒有可用的餐點分類，請先至後台新增分類！');
            return;
        }
        if (!name || !price) {
            alert('餐點名稱與價格為必填！');
            return;
        }

        const data = new FormData();
        data.append('action', 'add');
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
                alert('新增成功！');
                location.reload();
            } else {
                alert('新增失敗：' + (resp.error || resp.message || '伺服器回應錯誤'));
            }
        })
        .catch(err => { console.error(err); alert('網路錯誤'); });
    }
</script>

<?php include('footer.php'); ?>