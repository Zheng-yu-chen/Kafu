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

// 🔐 全域身份與權限檢查邏輯
$user_role = isset($_SESSION['role_id']) ? intval($_SESSION['role_id']) : 0;

// 是否為此餐廳的管理者（自身店家）
$is_current_shop_owner = ($user_role === 2 && isset($_SESSION['r_id']) && intval($_SESSION['r_id']) === $r_id);
// 是否為系統管理員
$is_admin = ($user_role === 1);
// 是否為一般消費者帳號
$is_user = ($user_role === 3);

// 🟢 撈取餐廳基本資訊
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

// 🟢 獨立撈取該餐廳所有的肉品來源
$origin_sql = "SELECT meat_type, country FROM meatorigins WHERE r_id = $r_id";
$origin_result = $conn->query($origin_sql);
$origin_list = [];

if ($origin_result && $origin_result->num_rows > 0) {
    while ($ori_data = $origin_result->fetch_assoc()) {
        $origin_list[] = $ori_data['meat_type'] . " : " . $ori_data['country'];
    }
    $res_place = implode(' / ', $origin_list); 
}

// 撈取該店家的餐點品項（包含全部微量元素與素食狀態）
$sql = "SELECT i.item_id, i.name, i.price, i.calories, i.protein, i.fat, i.carbs, i.is_vegetarian
        FROM items i
        JOIN categories c ON i.c_id = c.c_id
        WHERE c.r_id = $r_id 
        AND i.item_status = 1 
        ORDER BY c.c_id ASC, i.item_id ASC";

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
        vertical-align: middle; 
        margin-right: 4px;
        margin-bottom: 3px; 
        opacity: 0.9;
    }
    
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
    
    .meal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .meal-option { display: block; }
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
    
    .meal-option span:active { transform: scale(0.94); }
    .meal-option input:checked + span { background: var(--fujen-blue, #002B5B); color: rgb(255, 255, 255); font-weight: bold; }
    
    .qty-control { display: flex; align-items: center; gap: 10px; }
    .qty-btn { width: 45px; height: 45px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; font-size: 20px; cursor: pointer; transition: 0.2s; }
    .qty-btn:active { background: #eee; }
    .qty-input { flex: 1; text-align: center; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }

    .submit-tray-btn {
        width: 100%;
        background-color: #E6762D; 
        color: white;
        padding: 15px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.3s, transform 0.1s, box-shadow 0.1s;
        box-shadow: 0 4px 0 #B35C22; 
    }
    .submit-tray-btn:hover { background-color: #FF8336; }
    .submit-tray-btn:active { transform: translateY(2px); box-shadow: 0 2px 0 #B35C22; }

    #toast-container { position: fixed; top: 60px; left: 50%; z-index: 100000; pointer-events: none; }
</style>

<div class="header-section">
    <a href="index.php" class="back-btn">❮ 返回店家列表</a>
    
    <div class="header-title" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
        <h1 style="margin: 0; font-size: 24px; color: #ffffff;">
            <?php echo htmlspecialchars($res_name); ?>
        </h1>
        
        <p style="margin: 0; display: flex; align-items: center; gap: 4px;">
            <img src="icon/destination_icon.png" alt="地點" class="dest-icon">
            <?php echo htmlspecialchars($res_loc); ?> • 餐點列表
        </p>
        
        <?php if (!empty($res_place)): ?>
            <div style="margin-top: 2px;">
                <span style="
                    font-size: 11px; 
                    background-color: rgba(255, 255, 255, 0.15); 
                    color: #ffffff; 
                    border: none; 
                    padding: 3px 8px; 
                    border-radius: 5px; 
                    font-weight: 500;
                    letter-spacing: 0.5px;
                    display: inline-flex;
                    align-items: center;
                    backdrop-filter: blur(2px); 
                ">
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
                // 🔐 核心防線：精準控管右側功能鈕的顯示
                // 1. 如果目前使用者是「管理員」或「任何店家帳號」，均不允許加入托盤
                $is_any_business = ($user_role === 1 || $user_role === 2);
                $show_add_btn = !$is_any_business; 

                // 2. 只有此餐廳的「正牌老闆」或「管理員」才會看到編輯與刪除
                $show_edit_delete = ($is_current_shop_owner || $is_admin);

                // 只有一般消費者檢查收藏狀態
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
            <div class="item-card" data-item-id="<?php echo $row['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>" data-price="<?php echo floatval($row['price']); ?>" data-calories="<?php echo htmlspecialchars($row['calories']); ?>" data-protein="<?php echo htmlspecialchars($row['protein']); ?>" data-fat="<?php echo htmlspecialchars($row['fat']); ?>" data-carbs="<?php echo htmlspecialchars($row['carbs']); ?>">
                
                <div class="item-info" style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                    <?php if ($is_user): ?>
                        <button class="favorite-btn <?php echo $is_fav ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(this, <?php echo $row['item_id']; ?>)" 
                            style="background: none; border: none; cursor: pointer; padding: 0; font-size: 20px; line-height: 1; margin-top: 2px; flex-shrink: 0;">
                            <?php echo $is_fav ? '❤️' : '🤍'; ?>
                        </button>
                    <?php endif; ?>

                    <div style="flex: 1;">
                        <h4 class="item-name" style="margin: 0; font-size: 16px; color: var(--fujen-blue, #002B5B);">
                            <?php echo htmlspecialchars($row['name']); ?>
                            <?php if (isset($row['is_vegetarian']) && $row['is_vegetarian'] == 1): ?>
                                <span style="font-size: 12px; background: #4CAF50; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">素</span>
                            <?php endif; ?>
                        </h4>
                        <div class="nutrition">
                            <div class="nutrition-row">
                                <span class="price-tag">NT$ <?php echo floatval($row['price']); ?></span>
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
                
                <?php if ($show_edit_delete): ?>
                    <!-- ✅ 情況 A：管理員或該店老闆，顯示編輯、刪除按鈕 -->
                    <div class="action-icons" style="margin-left: 15px; flex-shrink: 0;">
                        <button class="btn-edit" onclick="openEditModal(<?php echo $row['item_id']; ?>)">編輯</button>
                        <button class="btn-delete" onclick="deleteItem(<?php echo $row['item_id']; ?>)">刪除</button>
                    </div>
                <?php elseif ($show_add_btn): ?>
                    <!-- ✅ 情況 B：一般消費者或未登入訪客，顯示加入托盤的「+」 -->
                    <button class="add-btn" onclick="openTrayModal(<?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')" 
                            style="margin-left: 15px; flex-shrink: 0;">+</button>
                <?php endif; ?>
                <!-- 💡 情況 C：如果是其他店家帳號點進來，上面兩者皆不成立，右側將保持留空，不會出現 + 號 -->

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #999;">
            <div style="font-size: 30px; margin-bottom: 10px;">🥗</div>
            Currently no items available in this restaurant!
        </div>
    <?php endif; ?>
</div>

<!-- 加入托盤彈出視窗 -->
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
                <?php if ($is_user && isset($_SESSION['u_id'])): ?>
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
    function openTrayModal(id, name) {
        const modal = document.getElementById('trayModal');
        const modalName = document.getElementById('modalItemName');
        const modalId = document.getElementById('modalItemId');
        if(modal && modalName && modalId) {
            modalName.textContent = name;
            modalId.value = id;
            modal.style.display = 'flex';
        }
    }

    function closeTrayModal() {
        const modal = document.getElementById('trayModal');
        if(modal) modal.style.display = 'none';
    }

    function changeQty(amount) {
        const qtyInput = document.getElementById('modalQty');
        if(qtyInput) {
            let current = parseInt(qtyInput.value) || 1;
            current += amount;
            if(current < 1) current = 1;
            if(current > 99) current = 99;
            qtyInput.value = current;
        }
    }

    function openEditModal(itemId) {
        window.location.href = 'edit_item.php?item_id=' + itemId;
    }

    function deleteItem(itemId) {
        if (confirm('確定要刪除這項餐點嗎？此操作將無法復原。')) {
            window.location.href = 'delete_item.php?item_id=' + itemId + '&r_id=<?php echo $r_id; ?>';
        }
    }

    function toggleFavorite(btn, itemId) {
        console.log('切換收藏項目 ID:', itemId);
    }
</script>

<?php 
include('footer.php'); 
?>