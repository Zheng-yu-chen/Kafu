
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

$res_sql = "SELECT name, location FROM restaurants WHERE r_id = $r_id";
$res_result = $conn->query($res_sql);
$res_name = "餐廳資訊";
$res_loc = "未知位置";

if ($res_result && $res_result->num_rows > 0) {
    $res_data = $res_result->fetch_assoc();
    $res_name = $res_data['name'];
    $res_loc = $res_data['location'];
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
    
    .nutrition { 
        font-size: 13px; 
        margin-top: 8px; 
        color: #666; 
        display: flex; 
        flex-direction: column; 
        gap: 5px;              
    }
    .nutrition-row {
        display: flex;         
        flex-wrap: wrap;       
        gap: 10px;             
        align-items: center;
    }
    
    .price-tag { color: #E53935; font-weight: bold; font-size: 14px; }
    .pro-tag { color: var(--primary-orange, #FF8C42); font-weight: bold; }

    .fire-icon {
        width: 14px; 
        height: 14px; 
        object-fit: contain;
        vertical-align: middle; 
        margin-right: 2px; 
        margin-bottom: 2px; 
    }

    .dest-icon {
        width: 16px;
        height: 16px;
        object-fit: contain;
        vertical-align: middle; /* 💡 改成 middle 讓它垂直置中對齊文字 */
        margin-right: 4px;
        margin-bottom: 3px; /* 💡 稍微加上一點底邊距，把圖示往上推 */
        opacity: 0.9;
    }
    
    .add-btn { background: var(--fujen-blue, #002B5B); color: white; width: 34px; height: 34px; display: flex; justify-content: center; align-items: center; border-radius: 50%; border: none; font-size: 20px; font-weight: bold; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,43,91,0.2); cursor: pointer; }
    .add-btn:active { transform: scale(0.95); }

    /* admin_dashboard 的編輯/刪除按鈕樣式，與菜單維護頁一致 */
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
    
    .meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .meal-option { 
        display: block; 
    }
    .meal-option input { display: none; }

    .meal-option span { 
        display: block; text-align: center; padding: 12px; 
        background: #eee;          
        color: #333;               
        border-radius: 8px; font-size: 14px; cursor: pointer; 
        box-sizing: border-box;         
        border: 1px solid transparent; 
        transition: transform 0.1s ease, background 0.2s, color 0.2s; 
    }
    
    .meal-option span:active {
        transform: scale(0.94);    
    }

    .meal-option input:checked + span { 
        background: var(--fujen-blue, #002B5B);                    
        color: rgb(255, 255, 255);
        font-weight: bold; 
    }
    
    .qty-control { display: flex; align-items: center; gap: 10px; }
    .qty-btn { width: 45px; height: 45px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; font-size: 20px; cursor: pointer; transition: 0.2s; }
    .qty-btn:active { background: #eee; }
    .qty-input { flex: 1; text-align: center; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }

.submit-tray-btn {
    width: 100%;
    background-color: #E6762D; /* 深橘色 */
    color: white;
    padding: 15px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.3s, transform 0.1s, box-shadow 0.1s;
    
    /* 底部陰影：讓按鈕看起來是立體的 */
    box-shadow: 0 4px 0 #B35C22; 
}

/* 懸停效果：滑鼠移上去稍微變亮一點點，增加互動感 */
.submit-tray-btn:hover {
    background-color: #FF8336;
}

/* 點擊效果：按鈕往下位移，同時陰影縮短，模擬真的按下去的感覺 */
.submit-tray-btn:active {
    transform: translateY(2px); /* 往下移動 2px */
    box-shadow: 0 2px 0 #B35C22; /* 陰影也跟著縮短 */
}
/* 讓提示訊息有淡入淡出的動畫 */

@keyframes fadeInOut {
    0% { 
        opacity: 0; 
        transform: translate(-50%, 20px); /* 水平鎖定 -50%，垂直從下方 20px */
    }
    15% { 
        opacity: 1; 
        transform: translate(-50%, 0);    /* 回到目標點 */
    }
    85% { 
        opacity: 1; 
        transform: translate(-50%, 0); 
    }
    100% { 
        opacity: 0; 
        transform: translate(-50%, -20px); /* 向上飄走 */
    }
}

#toast-container {
    position: fixed;
    top: 60px; /* 控制出現的高度位置 */
    left: 50%;
    z-index: 100000;
    pointer-events: none;
    /* 容器本身不需要寫 transform，因為會被動畫覆蓋 */
}
</style>

<div class="header-section">
    <a href="index.php" class="back-btn">❮ 返回店家列表</a>
    <div class="header-title">
        <h1><?php echo htmlspecialchars($res_name); ?></h1>
        <p><img src="icon/destination_icon.png" alt="地點" class="dest-icon"><?php echo htmlspecialchars($res_loc); ?> • 餐點列表</p>
    </div>
</div>
<div class="menu-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php 
                // 權限判斷：管理員(1) / 店家(2) 顯示編輯刪除；使用者(3) 顯示收藏與加入托盤
                $is_admin_or_owner = isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1,2]);
                $is_user = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3;

                $show_add_btn = !$is_admin_or_owner; 

                // 只有使用者才檢查收藏狀態
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
            <div class="item-card" data-item-id="<?php echo $row['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>" data-price="<?php echo floatval($row['price']); ?>" data-calories="<?php echo htmlspecialchars($row['calories']); ?>" data-protein="<?php echo htmlspecialchars($row['protein']); ?>" data-fat="<?php echo htmlspecialchars($row['fat']); ?>" data-carbs="<?php echo htmlspecialchars($row['carbs']); ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                
                <div class="item-info" style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                    <?php if ($is_user): ?>
                        <button class="favorite-btn <?php echo $is_fav ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(this, <?php echo $row['item_id']; ?>)" 
                            style="background: none; border: none; cursor: pointer; padding: 0; font-size: 20px; line-height: 1; margin-top: 2px; flex-shrink: 0;">
                            <?php echo $is_fav ? '❤️' : '🤍'; ?>
                        </button>
                    <?php endif; ?>

                    <div style="flex: 1;">
                        <h4 class="item-name" style="margin: 0; font-size: 16px; color: var(--fujen-blue, #002B5B);"><?php echo htmlspecialchars($row['name']); ?></h4>
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

<script>
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

    function closeTrayModal() {
        document.getElementById('trayModal').style.display = 'none';
    }

    document.getElementById('trayModal').addEventListener('click', function(e) {
        if (e.target === this) closeTrayModal();
    });
function showToast(message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    
    // 這裡強制寫入位置屬性，確保不同長度的文字都能置中
    toast.style.cssText = `
        position: absolute;
        left: 50%;
        background: rgba(0, 0, 0, 0.85);
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        white-space: nowrap;
        animation: fadeInOut 2s ease-in-out forwards;
        pointer-events: none;
    `;
    
    toast.innerText = message; // 這裡會接收「收藏餐點」或「取消收藏餐點」
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 2000);
}

function toggleFavorite(btn, itemId) {
    const isCurrentlyFav = btn.classList.contains('active') || btn.innerText.trim() === '❤️';

    const formData = new FormData();
    formData.append('item_id', itemId);

    fetch('save_favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.status === 'added') {
                btn.innerText = '❤️';
                btn.classList.add('active');
                showToast('收藏餐點');
            } else if (data.status === 'removed') {
                btn.innerText = '🤍';
                btn.classList.remove('active');
                showToast('取消收藏餐點');
            }
        } else {
            alert('操作失敗，請先登入！');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('網路連線有誤，請稍後再試');
    });
}

// 編輯彈窗相關
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
    const id = document.getElementById('editItemId').value;
    const name = document.getElementById('editName').value;
    const price = document.getElementById('editPrice').value;
    const calories = document.getElementById('editCalories').value;
    const protein = document.getElementById('editProtein').value;
    const fat = document.getElementById('editFat').value;
    const carbs = document.getElementById('editCarbs').value;

    const data = new FormData();
    data.append('action', 'update');
    data.append('item_id', id);
    data.append('name', name);
    data.append('price', price);
    data.append('calories', calories);
    data.append('protein', protein);
    data.append('fat', fat);
    data.append('carbs', carbs);

    fetch('manage_menu_api.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.success) {
            location.reload();
        } else {
            alert('更新失敗：' + (resp.error || '伺服器回應錯誤'));
        }
    })
    .catch(err => { console.error(err); alert('網路錯誤'); });
}

function deleteItem(itemId) {
    if (!confirm('確定要刪除此餐點？此操作無法回復。')) return;
    const data = new FormData();
    data.append('action', 'delete');
    data.append('item_id', itemId);

    fetch('manage_menu_api.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.success) {
            location.reload();
        } else {
            alert('刪除失敗');
        }
    })
    .catch(err => { console.error(err); alert('網路錯誤'); });
}
</script>

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
                <input id="editProtein" type="number" class="date-input" style="width: 100%; box-sizing: border-box;" />
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>脂肪 (g)</label>
                <input id="editFat" type="number" class="date-input" style="width: 100%; box-sizing: border-box;" />
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>碳水 (g)</label>
                <input id="editCarbs" type="number" class="date-input" style="width: 100%; box-sizing: border-box;" />
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button class="submit-tray-btn" onclick="saveEdit()" type="button" style="width: 100%; box-sizing: border-box;">儲存</button>
        </div>
    </div>
</div>

</div> <div id="toast-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 100000;"></div>
<?php include('footer.php'); ?>