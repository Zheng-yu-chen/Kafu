<?php
session_start();
include('db.php');

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 處理儲存邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name']; 
    $goal_cal = intval($_POST['goal_cal']);
    
    // 配合資料表 tinyint 型態：未填寫則給 null，有填寫則轉為數字
    $gender = ($_POST['gender'] !== '') ? intval($_POST['gender']) : null;
    $activity_level = ($_POST['activity_level'] !== '') ? intval($_POST['activity_level']) : null;
    
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;

    $notify_meal = isset($_POST['notify_meal']) ? 1 : 0;
    
    // 接收各餐點的提醒時間，若未填寫則給預設或 null
    $time_breakfast = !empty($_POST['time_breakfast']) ? $_POST['time_breakfast'] : null;
    $time_lunch     = !empty($_POST['time_lunch']) ? $_POST['time_lunch'] : null;
    $time_dinner    = !empty($_POST['time_dinner']) ? $_POST['time_dinner'] : null;
    
    // 進行 UPDATE (包含新加入的時間欄位)
    $sql = "UPDATE accounts SET 
            name = ?, 
            goal_cal = ?, 
            gender = ?, 
            height = ?, 
            weight = ?, 
            activity_level = ?,
            notify_meal = ?,
            time_breakfast = ?,
            time_lunch = ?,
            time_dinner = ?
            WHERE u_id = ?";
    
    $stmt = $conn->prepare($sql);
    // 參數型態對應：s(name), i(goal_cal), i(gender), d(height), d(weight), i(activity_level), i(notify_meal), s(b_time), s(l_time), s(d_time), i(u_id) -> "siiiddisssi"
    $stmt->bind_param("siiiddisssi", $name, $goal_cal, $gender, $height, $weight, $activity_level, $notify_meal, $time_breakfast, $time_lunch, $time_dinner, $u_id);
    
    if($stmt->execute()) {
        echo "<script>alert('個人身體與用餐時間設定已成功儲存！'); window.location.href='profile.php';</script>";
        exit();
    } else {
        echo "<script>alert('儲存失敗，請檢查資料格式或稍後再試。');</script>";
    }
}

// 撈取資料，包含新增的時間欄位
$sql = "SELECT name, goal_cal, gender, height, weight, activity_level, notify_meal, time_breakfast, time_lunch, time_dinner FROM accounts WHERE u_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 將資料庫撈出的 TIME 格式 (HH:MM:SS) 轉為 HTML input[type=time] 需要的 (HH:MM)
$time_b = $user['time_breakfast'] ? substr($user['time_breakfast'], 0, 5) : '08:00';
$time_l = $user['time_lunch'] ? substr($user['time_lunch'], 0, 5) : '12:00';
$time_d = $user['time_dinner'] ? substr($user['time_dinner'], 0, 5) : '18:00';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設定</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-content { padding: 20px; }
        .section-title { font-size: 14px; color: var(--text-gray, #888); margin: 15px 0 8px 5px; font-weight: bold; text-align: left; }
        .settings-card { background: var(--white, #fff); border-radius: 12px; padding: 5px 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 15px; }
        .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f2f2f2; }
        .setting-item:last-child { border-bottom: none; }
        .item-label { display: flex; flex-direction: column; text-align: left; }
        .label-main { font-size: 16px; color: #333; font-weight: 500; }
        .label-sub { font-size: 12px; color: #999; margin-top: 2px; }
        
        .item-input, .item-select {
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            width: 150px;
            height: 38px;
            color: #333;
            outline: none;
            transition: border 0.2s;
            background-color: #fff;
            text-align: left;
        }
        
        .item-select {
            text-align-last: left;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .item-input:focus, .item-select:focus { border-color: var(--fujen-blue, #002B5B); }

        /* Switch 開關樣式 */
        .switch { position: relative; display: inline-block; width: 46px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e0e0e0; transition: .3s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        input:checked + .slider { background-color: #4CAF50; }
        input:checked + .slider:before { transform: translateX(20px); }

        /* 儲存按鈕 */
        .save-btn { background-color: var(--fujen-blue, #002B5B); color: white; border: none; width: 100%; padding: 14px; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,43,91,0.2); transition: 0.2s; }
        .save-btn:active { transform: scale(0.98); }

        /* 💡 動態時間設定區塊：預設依通知狀態顯示或隱藏 */
        .time-settings-block {
            display: <?php echo $user['notify_meal'] ? 'block' : 'none'; ?>;
            border-top: 1px solid #f2f2f2;
            margin-top: 5px;
        }
        .time-sub-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0 12px 10px;
            border-bottom: 1px dashed #eee;
        }
        .time-sub-item:last-child { border-bottom: none; }
    </style>
</head>
<body>

<div class="mobile-wrapper">
    
    <div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
        <a href="profile.php" style="color: white; text-decoration: none; font-size: 22px; font-weight: bold; line-height: 1;">❮</a>
        <h1 style="margin: 0; font-size: 22px;">設定</h1>
    </div>

    <div class="settings-content">
        <form action="settings.php" method="POST">
            
            <div class="section-title">👤 個人身體檔案管理</div>
            <div class="settings-card">
                <div class="setting-item">
                    <div class="item-label">
                        <span class="label-main">顯示姓名</span>
                        <span class="label-sub">在主頁面顯示的名稱</span>
                    </div>
                    <input type="text" class="item-input" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="setting-item">
                    <div class="item-label">
                        <span class="label-main">生理性別</span>
                        <span class="label-sub">用於更精準的代謝率計算</span>
                    </div>
                    <select class="item-select" name="gender">
                        <option value="">請選擇</option>
                        <option value="1" <?php if($user['gender'] == 1) echo 'selected'; ?>>男</option>
                        <option value="2" <?php if($user['gender'] == 2) echo 'selected'; ?>>女</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="item-label">
                        <span class="label-main">身高 (cm)</span>
                        <span class="label-sub">請輸入您的身高</span>
                    </div>
                    <input type="number" step="0.1" class="item-input" name="height" placeholder="例如: 170.5" value="<?php echo $user['height'] ? htmlspecialchars($user['height']) : ''; ?>">
                </div>

                <div class="setting-item">
                    <div class="item-label">
                        <span class="label-main">體重 (kg)</span>
                        <span class="label-sub">請輸入當前體重數據</span>
                    </div>
                    <input type="number" step="0.1" class="item-input" name="weight" placeholder="例如: 62.3" value="<?php echo $user['weight'] ? htmlspecialchars($user['weight']) : ''; ?>">
                </div>

                <div class="setting-item">
                    <div class="item-label">
                        <span class="label-main">日常活動量</span>
                        <span class="label-sub">根據日常作息型態選擇</span>
                    </div>
                    <select class="item-select" name="activity_level">
                        <option value="">請選擇</option>
                        <option value="1" <?php if($user['activity_level'] == 1) echo 'selected'; ?>>久坐 (幾乎不運動)</option>
                        <option value="2" <?php if($user['activity_level'] == 2) echo 'selected'; ?>>輕度 (每週輕度運動1-3天)</option>
                        <option value="3" <?php if($user['activity_level'] == 3) echo 'selected'; ?>>中度 (每週中度運動3-5天)</option>
                        <option value="4" <?php if($user['activity_level'] == 4) echo 'selected'; ?>>高度 (高強度運動)</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="item-label">
                        <span class="label-main">每日目標熱量(kal)</span>
                        <span class="label-sub">設定您每日預期攝取的卡路里</span>
                    </div>
                    <input type="number" class="item-input" name="goal_cal" value="<?php echo htmlspecialchars($user['goal_cal']); ?>" required>
                </div>
            </div>

            <div class="section-title">🔔 通知設定</div>
            <div class="settings-card">
                <div class="setting-item" style="border-bottom: none;">
                    <div class="item-label">
                        <span class="label-main">用餐提醒</span>
                        <span class="label-sub">開啟後，可自訂各餐點提醒時間</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notifyMealToggle" name="notify_meal" <?php if($user['notify_meal']) echo 'checked'; ?> onchange="toggleTimeSettings()">
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="time-settings-block" id="timeSettingsBlock">
                    <div class="time-sub-item">
                        <span class="label-sub" style="font-size: 14px; color: #555;">🍳 早餐提醒時間</span>
                        <input type="time" class="item-input" name="time_breakfast" value="<?php echo $time_b; ?>">
                    </div>
                    <div class="time-sub-item">
                        <span class="label-sub" style="font-size: 14px; color: #555;">🥪 午餐提醒時間</span>
                        <input type="time" class="item-input" name="time_lunch" value="<?php echo $time_l; ?>">
                    </div>
                    <div class="time-sub-item">
                        <span class="label-sub" style="font-size: 14px; color: #555;">🥩 晚餐提醒時間</span>
                        <input type="time" class="item-input" name="time_dinner" value="<?php echo $time_d; ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="save-btn">儲存設定</button>
        </form>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
function toggleTimeSettings() {
    const checkbox = document.getElementById('notifyMealToggle');
    const timeBlock = document.getElementById('timeSettingsBlock');
    if (checkbox.checked) {
        timeBlock.style.display = 'block';
    } else {
        timeBlock.style.display = 'none';
    }
}
</script>

</body>
</html>