<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>KaFu - 輔大學餐助手</title>
    <link rel="icon" href="icon/chef_icon.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    
    <link rel="manifest" href="manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KaFu">
    <link rel="apple-touch-icon" href="images/logo_192.png">

    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('service-worker.js')
                .then(reg => console.log('KaFu PWA 服務工作線程註冊成功！'))
                .catch(err => console.log('KaFu PWA 註冊失敗：', err));
        });
    }
    </script>

    <?php if (basename($_SERVER['PHP_SELF']) == 'profile.php'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>
    <div class="mobile-wrapper">

<?php
// === 🔔 全域獨立用餐時間通知邏輯 ===
$hdr_notify_meal = 0;
$hdr_time_b = '08:00';
$hdr_time_l = '12:00';
$hdr_time_d = '18:00';

if (isset($_SESSION['u_id'])) {
    // 💡 核心修正：如果當前頁面還沒有宣告 $conn，主動去抓 db.php 確保連線一定存在
    if (!isset($conn) && file_exists('db.php')) {
        include_once('db.php');
    }

    // 再次確認連線變數存在，才進行 SQL 查詢
    if (isset($conn)) {
        $hdr_u_id = $_SESSION['u_id'];
        $hdr_sql = "SELECT notify_meal, time_breakfast, time_lunch, time_dinner FROM accounts WHERE u_id = ?";
        $hdr_stmt = $conn->prepare($hdr_sql);
        if ($hdr_stmt) {
            $hdr_stmt->bind_param("i", $hdr_u_id);
            $hdr_stmt->execute();
            $hdr_user = $hdr_stmt->get_result()->fetch_assoc();
            
            if ($hdr_user) {
                $hdr_notify_meal = (int)$hdr_user['notify_meal'];
                // 格式化時間為 HH:MM
                if (!empty($hdr_user['time_breakfast'])) $hdr_time_b = substr($hdr_user['time_breakfast'], 0, 5);
                if (!empty($hdr_user['time_lunch']))     $hdr_time_l = substr($hdr_user['time_lunch'], 0, 5);
                if (!empty($hdr_user['time_dinner']))    $hdr_time_d = substr($hdr_user['time_dinner'], 0, 5);
            }
        }
    }
}
?>

<script>
(function() {
    const notifyEnabled = <?php echo $hdr_notify_meal; ?>;
    const timeBreakfast = "<?php echo $hdr_time_b; ?>";
    const timeLunch     = "<?php echo $hdr_time_l; ?>";
    const timeDinner    = "<?php echo $hdr_time_d; ?>";
    
    // 可以在任何頁面按 F12 檢查這行有沒有出現 "🟢 已啟動"
    console.log(`[KaFu 廣播通知] 狀態: ${notifyEnabled === 1 ? '🟢 已啟動' : '🔴 已關閉'} | 頁面: ${window.location.pathname.split('/').pop()}`);

    let alertedMeals = { breakfast: false, lunch: false, dinner: false };

    if (notifyEnabled === 1) {
        // 💡 設定每 30 秒 (30000ms) 自動巡邏檢查一次時間
        setInterval(checkReminderTime, 30000);
        checkReminderTime(); 
    }

    function checkReminderTime() {
        const now = new Date();
        const currentHours = String(now.getHours()).padStart(2, '0');
        const currentMinutes = String(now.getMinutes()).padStart(2, '0');
        const currentTimeStr = `${currentHours}:${currentMinutes}`;

        // 當分鐘離開設定的時間點時，重設旗標
        if (currentMinutes !== timeBreakfast.split(':')[1] && 
            currentMinutes !== timeLunch.split(':')[1] && 
            currentMinutes !== timeDinner.split(':')[1]) {
            alertedMeals = { breakfast: false, lunch: false, dinner: false };
        }

        // 比對三餐時間
        if (currentTimeStr === timeBreakfast && !alertedMeals.breakfast) {
            alertedMeals.breakfast = true;
            alert("🍳 早餐時間到囉！\n\n吃飽了嗎？記得紀錄一下剛才吃了什麼美味早餐喔！");
        } 
        else if (currentTimeStr === timeLunch && !alertedMeals.lunch) {
            alertedMeals.lunch = true;
            alert("🥪 午餐時間到囉！\n\n別忘了紀錄午餐的卡路里歐！");
        } 
        else if (currentTimeStr === timeDinner && !alertedMeals.dinner) {
            alertedMeals.dinner = true;
            alert("🥩 晚餐時間到囉！\n\n記得紀錄晚餐，檢視今天的熱量目標吧！");
        }
    }
})();
</script>

<style>
    /* 登出確認視窗背景 */
    #logoutConfirmModal { 
        display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); 
        z-index: 20000; align-items: center; justify-content: center; 
        backdrop-filter: blur(2px);
    }
    
    /* 極簡卡片本體 */
    .logout-modal-card { 
        background: #fff; padding: 25px 20px; border-radius: 16px; 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); 
        max-width: 300px; width: 85%; text-align: center; 
        animation: popIn 0.25s ease-out; 
        box-sizing: border-box;
        display: flex;              
        flex-direction: column;     /* 卡片內容依然由上往下（先標題、後按鈕） */
        align-items: center;        
    }
    
    @keyframes popIn { 
        from { opacity: 0; transform: scale(0.95); } 
        to { opacity: 1; transform: scale(1); } 
    }
    
    #logoutConfirmModal h3 { 
        margin: 0 0 20px 0; color: #333; font-size: 18px; font-weight: bold; 
    }
    
    /* 🌟 關鍵修改：按鈕專用的左右排列容器 */
    .logout-btn-group {
        display: flex;
        flex-direction: row;        /* 強制兩顆按鈕左右並排 */
        gap: 12px;                  /* 兩顆按鈕中間的間距 */
        width: 100%;
        box-sizing: border-box;
    }
    
    #logoutConfirmModal .btn { 
        flex: 1;                    /* 🌟 關鍵：平分左右寬度 */
        padding: 12px 0; border-radius: 10px; 
        font-weight: bold; font-size: 15px; cursor: pointer; border: none; 
        transition: 0.15s; box-sizing: border-box;
    }
    
    /* 左邊：確定按鈕 (紅色) */
    #logoutConfirmModal .btn-danger { background: #E53935; color: white; }
    #logoutConfirmModal .btn-danger:active { background: #D32F2F; transform: scale(0.98); }
    
    /* 右邊：取消按鈕 (灰色) */
    #logoutConfirmModal .btn-cancel { background: #f0f0f0; color: #555; }
    #logoutConfirmModal .btn-cancel:active { background: #e4e4e4; transform: scale(0.98); }
</style>

<div id="logoutConfirmModal">
    <div class="logout-modal-card" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
        <h3 id="logoutTitle">確定要登出嗎？</h3>
        
        <div class="logout-btn-group">
            <button class="btn btn-danger" id="confirmLogoutBtn">確定</button>
            <button class="btn btn-cancel" id="cancelLogoutBtn">取消</button>
        </div>
    </div>
</div>

<script>
    function showLogoutConfirm() {
        const modal = document.getElementById('logoutConfirmModal');
        if (modal) modal.style.display = 'flex';
    }
    
    function closeLogoutConfirm() { 
        const modal = document.getElementById('logoutConfirmModal'); 
        if (modal) modal.style.display = 'none'; 
    }
    
    document.addEventListener('DOMContentLoaded', function(){
        const confirmBtn = document.getElementById('confirmLogoutBtn');
        const cancelBtn = document.getElementById('cancelLogoutBtn');
        
        if (confirmBtn) confirmBtn.addEventListener('click', function(){ location.href = 'logout.php?confirm=1'; });
        if (cancelBtn) cancelBtn.addEventListener('click', closeLogoutConfirm);
        
        // 點擊觸發
        document.body.addEventListener('click', function(e){
            const target = e.target.closest && e.target.closest('a.logout-btn');
            if (target) { e.preventDefault(); showLogoutConfirm(); }
        });
        
        // 點擊背景關閉
        const modalBg = document.getElementById('logoutConfirmModal');
        if(modalBg) {
            modalBg.addEventListener('click', function(e) {
                if (e.target === this) closeLogoutConfirm();
            });
        }
    });
</script>