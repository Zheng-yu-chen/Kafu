<?php
session_start();
include('db.php');
include('header.php');

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 處理儲存邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 💡 新增：接收姓名參數
    $name = $_POST['name']; 
    $goal_cal = intval($_POST['goal_cal']);
    $goal_pro = intval($_POST['goal_pro']);
    
    $pref_veg = isset($_POST['pref_veg']) ? 1 : 0;
    $pref_low_cal = isset($_POST['pref_low_cal']) ? 1 : 0;
    $notify_meal = isset($_POST['notify_meal']) ? 1 : 0;
    $notify_pickup = isset($_POST['notify_pickup']) ? 1 : 0;
    
    // 💡 修改 SQL：加入 name = ?
    $sql = "UPDATE accounts SET 
            name = ?, 
            goal_cal = ?, goal_pro = ?, 
            pref_veg = ?, pref_low_cal = ?, 
            notify_meal = ?, notify_pickup = ? 
            WHERE u_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiiiiii", $name, $goal_cal, $goal_pro, $pref_veg, $pref_low_cal, $notify_meal, $notify_pickup, $u_id);
    
    if($stmt->execute()) {
        echo "<script>alert('設定已儲存！');</script>";
    }
}

// 撈取最新資料
$res = $conn->query("SELECT * FROM accounts WHERE u_id = $u_id");
$user = $res->fetch_assoc();
?>

<style>
    .settings-container { max-width: 500px; margin: auto; padding: 20px; background: #f8f9fa; min-height: 100vh; font-family: sans-serif; }
    .section-title { font-size: 1.1em; font-weight: bold; color: #002B5B; margin: 25px 0 10px 5px; }
    .settings-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f2f2f2; }
    .setting-item:last-child { border-bottom: none; }
    
    .item-label { display: flex; flex-direction: column; }
    .label-main { font-size: 1em; color: #333; font-weight: 500; }
    .label-sub { font-size: 0.8em; color: #999; margin-top: 2px; }
    
    /* 輸入框樣式優化 */
    .setting-input { border: none; text-align: right; color: #002B5B; font-weight: bold; font-size: 1.1em; outline: none; background: transparent; }
    .name-input { width: 150px; } /* 姓名寬度稍微長一點 */
    .num-input { width: 80px; }
    
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #002B5B; }
    input:checked + .slider:before { transform: translateX(20px); }

    .save-btn { width: 100%; padding: 16px; background: #002B5B; color: white; border: none; border-radius: 12px; font-size: 1em; font-weight: bold; margin-top: 10px; cursor: pointer; }
</style>

<div class="settings-container">
    <form method="POST">
        <h2 style="color: #002B5B;">⚙️ 設定</h2>

        <div class="section-title">👤 個人資料</div>
        <div class="settings-card">
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">顯示姓名</span>
                    <span class="label-sub">這會顯示在您的個人中心</span>
                </div>
                <input type="text" name="name" class="setting-input name-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">學號</span>
                    <span class="label-sub">帳號 ID (不可修改)</span>
                </div>
                <span style="color: #ccc; font-weight: bold;"><?php echo htmlspecialchars($user['accounts']); ?></span>
            </div>
        </div>

        <div class="section-title">🎯 目標設定</div>
        <div class="settings-card">
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">每日熱量目標 (kcal)</span>
                    <span class="label-sub">建議成人攝取 1800-2400 kcal</span>
                </div>
                <input type="number" name="goal_cal" class="setting-input num-input" value="<?php echo $user['goal_cal'] ?? 2000; ?>">
            </div>
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">每日蛋白質目標 (g)</span>
                    <span class="label-sub">建議成人攝取 50-60g 蛋白質</span>
                </div>
                <input type="number" name="goal_pro" class="setting-input num-input" value="<?php echo $user['goal_pro'] ?? 60; ?>">
            </div>
        </div>

        <div class="section-title">🥗 飲食偏好</div>
        <div class="settings-card">
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">素食</span>
                    <span class="label-sub">優先顯示素食餐點</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="pref_veg" <?php if($user['pref_veg']) echo 'checked'; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">低卡</span>
                    <span class="label-sub">優先顯示低於 500 kcal 的餐點</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="pref_low_cal" <?php if($user['pref_low_cal']) echo 'checked'; ?>>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="section-title">🔔 通知設定</div>
        <div class="settings-card">
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">用餐提醒</span>
                    <span class="label-sub">在用餐時間提醒您記錄飲食</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notify_meal" <?php if($user['notify_meal']) echo 'checked'; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div class="item-label">
                    <span class="label-main">取餐通知</span>
                    <span class="label-sub">餐點準備完成時通知您</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notify_pickup" <?php if($user['notify_pickup']) echo 'checked'; ?>>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <button type="submit" class="save-btn">儲存設定</button>
        <div style="text-align:center; margin-top:20px; padding-bottom:30px;">
            <a href="profile.php" style="color:#888; text-decoration:none; font-size:0.9em;">返回個人中心</a>
        </div>
    </form>
</div>

<?php include('footer.php'); ?>