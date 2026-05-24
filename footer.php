<?php
// 安全防護：如果 Session 還沒啟動且還沒送出 headers，就啟動它
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// 判斷身份，決定「我的」按鈕要連到哪一頁
$profile_url = 'profile.php'; 
if (isset($_SESSION['role_id'])) {
    if ($_SESSION['role_id'] == 2) {
        $profile_url = 'store_profile.php'; 
    } else if ($_SESSION['role_id'] == 1) {
        $profile_url = 'admin_dashboard.php'; 
    }
}

// 取得當前檔名，用於判斷哪個按鈕要變藍色（Active）
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="footer-nav">
    <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><img src="icon/home_icon.png" alt="首頁"></div>
        <span>首頁</span>
    </a>

    <?php if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] == 3): ?>
    <a href="tray.php" class="nav-item <?php echo ($current_page == 'tray.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><img src="icon/tray_icon.png" alt="托盤"></div>
        <span>托盤</span>
    </a>
    <?php endif; ?>

    <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
    <a href="ai_assistant.php" class="nav-item ai-nav-item <?php echo ($current_page == 'ai_assistant.php') ? 'active' : ''; ?>">
        <div class="ai-nav-icon-ring">
            <img src="icon/chatbot_icon.png" alt="小助手">
        </div>
        <span class="ai-nav-text">小助手</span>
    </a>
    <?php endif; ?>

    <a href="comments.php" class="nav-item <?php echo ($current_page == 'comments.php') ? 'active' : ''; ?>">
        <div class="nav-icon"><img src="icon/comment_icon.png" alt="評價"></div>
        <span>評價</span>
    </a>

    <a href="<?php echo $profile_url; ?>" class="nav-item <?php echo (in_array($current_page, ['profile.php', 'store_profile.php', 'admin_dashboard.php'])) ? 'active' : ''; ?>">
        <div class="nav-icon"><img src="icon/profile_icon.png" alt="我的"></div>
        <span>我的</span>
    </a>
</div>

<style>
    .footer-nav {
        position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
        width: 100%; max-width: 430px; background: white;
        display: flex; justify-content: space-around; align-items: flex-end; padding: 10px 0 5px;
        border-top: 1px solid #eee; z-index: 9999;
    }
    .nav-item { flex: 1; text-align: center; text-decoration: none; color: #bbb; font-size: 12px; transition: 0.2s; padding-bottom: 5px; }
    .nav-item.active { color: var(--fujen-blue, #002B5B); font-weight: bold; }
    .nav-item.active span { color: var(--fujen-blue, #002B5B); }
    .nav-icon { font-size: 20px; margin-bottom: 2px; }

    .footer-nav .nav-icon img {
        width: 24px !important;   
        height: 24px !important;  
        object-fit: contain !important; 
        display: block !important;
        margin: 0 auto 4px !important; 
    }

    .footer-nav .nav-item.active .nav-icon img {
        transform: scale(1.15);
        transition: transform 0.2s ease;
    }

    /* --- 底部導覽列 AI 助理按鈕專用 --- */
    .ai-nav-item {
        position: relative; /* 🌟 改回 relative，讓它乖乖待在導覽列的 flex 容器內 */
        flex: 1.2; 
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end; 
        text-decoration: none;
        cursor: pointer;
        transform: translateY(-20px); /* 🌟 控制往上凸起的高度，數值越大凸越高 */
        padding-bottom: 0;
        z-index: 10005;
    }

    .ai-nav-icon-ring {
        width: 64px;
        height: 64px;
        background-color: #ffffff; 
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.08); /* 微微的上方立體陰影 */
        margin-bottom: 4px; /* 與文字的距離 */
        z-index: 10006;
    }

    .ai-nav-icon-ring img {
        width: 50px !important;  
        height: 50px !important;
        margin: 0 !important;
        border-radius: 50%;
        object-fit: contain !important;
        box-shadow: 0 4px 8px rgba(0, 43, 91, 0.25); 
        transition: transform 0.2s ease;
    }

    /* 如果在小助手頁面，讓外圈圖片也有放大效果 */
    .ai-nav-item.active .ai-nav-icon-ring img {
        transform: scale(1.08);
    }

    .ai-nav-text {
        font-size: 11px;
        color: #bbb;
        font-weight: bold;
        position: absolute;
        bottom: -16px; /* 🌟 把文字往下壓，讓它剛好跟「首頁」、「我的」等文字切齊 */
    }
    
    .ai-nav-item.active .ai-nav-text {
        color: var(--fujen-blue, #002B5B);
    }
</style>

</div> 
</body>
</html>