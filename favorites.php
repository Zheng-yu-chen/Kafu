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
    body { background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    
    /* --- 🌟 頂部深藍色標題樣式 (與餐廳頁面統一) --- */
    .header-section { background-color: var(--fujen-blue, #002B5B); color: white; padding: 30px 20px 20px; position: relative; }
    .back-btn { color: white; text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 15px; opacity: 0.9; }

    .fav-list { padding: 20px 20px 100px; }

    /* --- 沿用餐廳詳情頁的卡片樣式 --- */
    .item-card { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .item-name { font-size: 16px; font-weight: bold; color: var(--fujen-blue, #002B5B); margin: 0 0 4px; }
    .item-meta { font-size: 12px; color: #888; margin: 0 0 6px; }
    .dest-icon { width: 14px; height: 14px; object-fit: contain; vertical-align: middle; margin-right: 3px; margin-bottom: 2px;}
    
    .nutrition-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .price-tag { font-weight: bold; color: #E53935; font-size: 14px; }
    .fire-icon { width: 12px; height: 12px; object-fit: contain; vertical-align: middle; margin-right: 2px; margin-bottom: 2px; }
    .item-macros { display: flex; gap: 8px; font-size: 12px; font-weight: bold; color: var(--primary-orange, #FF8C42); margin-top: 6px; }

    /* 藍色加號按鈕 */
    .blue-plus-btn { 
        background: var(--fujen-blue, #002B5B); 
        color: white; 
        width: 34px; 
        height: 34px; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        border-radius: 50%; 
        border: none; 
        font-size: 20px; 
        font-weight: bold; 
        flex-shrink: 0; 
        box-shadow: 0 2px 5px rgba(0,43,91,0.2); 
        cursor: pointer; 
        transition: 0.15s;
    }
    .blue-plus-btn:active { transform: scale(0.92); }

    /* --- Modal 托盤彈出視窗樣式 --- */
    .modal-overlay { display: none; position: fixed !important; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 20000 !important; justify-content: center; align-items: center; padding: 20px; }
    .modal-box { background: white; width: 100%; max-width: 320px; border-radius: 18px; overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.22); animation: slideUp 0.28s cubic-bezier(0.2,0.9,0.2,1); }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { background-color: var(--fujen-blue, #002B5B); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
    .modal-header h2 { margin: 0; font-size: 20px; }
    .modal-header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; font-weight: normal; }
    .close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; }
    
    .modal-body { padding: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: bold; }
    .date-input-wrapper { position: relative; }
    .date-icon { position: absolute; left: 14px; top: 14px; font-size: 16px; color: #555; pointer-events: none; }
    .date-input { width: 100%; padding: 14px 14px 14px 40px; border: 1px solid #e6e6e6; border-radius: 14px; font-size: 15px; box-sizing: border-box; }
    
    .meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .meal-option { display: block; }
    .meal-option input { display: none; }
    .meal-option span { display: block; text-align: center; padding: 14px; background: #eee; color: #333; border-radius: 14px; font-size: 15px; cursor: pointer; box-sizing: border-box; border: 1px solid transparent; transition: 0.18s; }
    .meal-option input:checked + span { background: var(--fujen-blue, #002B5B); color: white; font-weight: bold; }
    
    .qty-control { display: flex; align-items: center; gap: 10px; }
    .qty-btn { width: 48px; height: 48px; border: 1px solid #e6e6e6; background: #f8f9fa; border-radius: 12px; font-size: 20px; cursor: pointer; transition: 0.15s; }
    .qty-input { flex: 1; text-align: center; padding: 14px; border: 1px solid #e6e6e6; border-radius: 12px; font-size: 16px; }

    .submit-tray-btn { width: 100%; background-color: #E6762D; color: white; padding: 16px; border: none; border-radius: 14px; font-size: 17px; font-weight: 700; cursor: pointer; margin-top: 12px; box-shadow: 0 6px 0 #B35C22; transition: 0.1s; }
    .submit-tray-btn:hover { background-color: #FF8336; }
    .submit-tray-btn:active { transform: translateY(2px); box-shadow: 0 2px 0 #B35C22; }

    #toast-container { position: fixed; top: 60px; left: 50%; transform: translateX(-50%); z-index: 100000; pointer-events: none; }
</style>

<!-- 🌟 統一深藍色頂部 -->
<div class="header-section">
    <a href="index.php" class="back-btn">❮ 返回首頁</a>
    
    <div class="header-title" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; width: 100%;">
        <h1 style="margin: 0; font-size: 24px; color: #ffffff;">我的收藏清單</h1>
    </div>
</div>

<div class="fav-list">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="item-card" data-item-id="<?php echo $row['item_id']; ?>">
                
                <div class="item-info" style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                    <!-- 愛心按鈕 (預設為 active) -->
                    <button class="favorite-btn active" 
                        onclick="toggleFavorite(this, <?php echo $row['item_id']; ?>)" 
                        style="background: none; border: none; cursor: pointer; padding: 0; font-size: 20px; line-height: 1; margin-top: 2px; flex-shrink: 0;">
                        ❤️
                    </button>

                    <div style="flex: 1;">
                        <h4 class="item-name"><?php echo htmlspecialchars($row['name']); ?></h4>
                        
                        <!-- 餐廳與地點 (保留可點擊前往餐廳的功能) -->
                        <div class="item-meta">
                            <a href="restaurant_detail.php?r_id=<?php echo $row['r_id']; ?>" style="color: #888; text-decoration: none;">
                                <?php echo htmlspecialchars($row['res_name']); ?> • 
                                <img src="icon/destination_icon.png" class="dest-icon"> <?php echo htmlspecialchars($row['location']); ?>
                            </a>
                        </div>
                        
                        <div class="nutrition">
                            <div class="nutrition-row">
                                <span class="price-tag">$<?php echo floatval($row['price']); ?></span>
                                <span style="font-size: 13px; color: #666;">
                                    <img src="icon/fire_icon.png" alt="熱量" class="fire-icon"> 
                                    <?php echo ($row['calories'] !== null) ? $row['calories'] : '---'; ?> kcal
                                </span>
                            </div>
                            <div class="item-macros">
                                <span>蛋白質 <?php echo isset($row['protein']) ? floatval($row['protein']) : '0'; ?>g</span>
                                <span>脂肪 <?php echo isset($row['fat']) ? floatval($row['fat']) : '0'; ?>g</span>
                                <span>碳水 <?php echo isset($row['carbs']) ? floatval($row['carbs']) : '0'; ?>g</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 藍色加號按鈕 -->
                <button type="button" class="blue-plus-btn" onclick="openTrayModal(<?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                    +
                </button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding:50px; color:#999;">
            <div style="font-size:40px; margin-bottom:10px;">🤍</div>
            目前還沒有收藏任何餐點喔！
        </div>
    <?php endif; ?>
</div>

<!-- ================= 加入托盤 Modal ================= -->
<div id="trayModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>加入托盤</h2>
                <p id="modalItemName">餐點名稱</p>
            </div>
            <button class="close-btn" onclick="closeTrayModal()">×</button>
        </div>

        <form id="trayForm" onsubmit="return submitTrayFormAsync(event, this);">
            <input type="hidden" name="item_id" id="modalItemId" value="">
            <div class="modal-body">
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

<div id="toast-container"></div>

<script>
    // --- 提示訊息 ---
    function showToast(message) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.style.cssText = `position: absolute; left: 50%; background: rgba(0, 0, 0, 0.85); color: white; padding: 12px 24px; border-radius: 50px; font-size: 14px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.3); white-space: nowrap; animation: slideUp 0.3s ease-out forwards; pointer-events: none;`;
        toast.innerText = message; 
        container.appendChild(toast);
        
        setTimeout(() => { 
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    // --- 🌟 收藏與取消收藏功能 (包含確認框與滑順消失) ---
    function toggleFavorite(btn, itemId) {
        // 1. 跳出確認對話框
        if (!confirm('確定要取消收藏此餐點嗎？')) {
            return; // 如果按取消，就什麼都不做
        }

        const formData = new FormData();
        formData.append('item_id', itemId);
        
        fetch('save_favorite.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.status !== 'added') {
                    showToast('已取消收藏');
                    
                    // 2. 找到該餐點的卡片
                    const card = btn.closest('.item-card');
                    
                    // 3. 讓卡片滑順縮小並變透明
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    
                    // 4. 等動畫播完 (300ms) 後，把卡片從畫面上刪除
                    setTimeout(() => {
                        card.remove();
                        
                        // 5. 檢查如果所有餐點都被刪光了，顯示「目前還沒有收藏任何餐點喔！」的提示
                        const list = document.querySelector('.fav-list');
                        if (list.querySelectorAll('.item-card').length === 0) {
                            list.innerHTML = `
                                <div style="text-align:center; padding:50px; color:#999;">
                                    <div style="font-size:40px; margin-bottom:10px;">🤍</div>
                                    目前還沒有收藏任何餐點喔！
                                </div>
                            `;
                        }
                    }, 300);
                }
            } else { 
                alert('操作失敗，請重試！'); 
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // --- 托盤視窗開關 ---
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
        qtyInput.value = (newVal < 1) ? 1 : newVal;
    }
    
    function closeTrayModal() { 
        document.getElementById('trayModal').style.display = 'none'; 
    }
    
    // 點擊背景關閉
    document.getElementById('trayModal').addEventListener('click', function(e) { 
        if (e.target === this) closeTrayModal(); 
    });

    // --- 非同步送出托盤 ---
    function submitTrayFormAsync(event, formElement) {
        event.preventDefault(); 
        const formData = new FormData(formElement);
        
        fetch('add_to_tray.php', {
            method: 'POST',
            body: formData,
            cache: 'no-store'
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                closeTrayModal();          
                if (typeof showTrayConfirmModal === 'function') {
                    showTrayConfirmModal(); // 喚醒成功確認圓角對話框
                } else {
                    showToast('✅ 成功加入托盤！');
                }
            } else {
                alert('加入托盤失敗，請重試！');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('網路錯誤，請稍後再試！');
        });
        
        return false;
    }
</script>

<!-- 引入加入成功後的彈出動畫元件 -->
<?php include('tray_confirm_modal.php'); ?>
<?php include('footer.php'); ?>