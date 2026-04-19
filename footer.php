<?php
// 安全防護：如果 Session 還沒啟動，就幫它啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 💡 判斷身份，決定「我的」按鈕要連到哪一頁
$profile_url = 'profile.php'; // 預設是學生的個人檔案
if (isset($_SESSION['role_id'])) {
    if ($_SESSION['role_id'] == 2) {
        $profile_url = 'store_profile.php'; // 店家連到儀表板
    } else if ($_SESSION['role_id'] == 1) {
        $profile_url = 'admin_dashboard.php'; // 管理員連到後台
    }
}
?>

<div class="footer-nav">
    <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <div class="nav-icon">🏠</div>
        <span>首頁</span>
    </a>

    <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
    <a href="tray.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'tray.php') ? 'active' : ''; ?>">
        <div class="nav-icon">📋</div>
        <span>托盤</span>
    </a>
    <?php endif; ?>

    <a href="comments.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'comments.php') ? 'active' : ''; ?>">
        <div class="nav-icon">💬</div>
        <span>評價</span>
    </a>

    <a href="<?php echo $profile_url; ?>" class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profile.php', 'store_profile.php', 'admin_dashboard.php'])) ? 'active' : ''; ?>">
        <div class="nav-icon">👤</div>
        <span>我的</span>
    </a>
</div>

<style>
    /* 維持原本的 CSS 樣式 */
    .footer-nav {
        position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
        width: 100%; max-width: 430px; background: white;
        display: flex; justify-content: space-around; padding: 10px 0;
        border-top: 1px solid #eee; z-index: 9999;
    }
    .nav-item { flex: 1; text-align: center; text-decoration: none; color: #bbb; font-size: 12px; transition: 0.2s; }
    .nav-item.active { color: var(--fujen-blue, #002B5B); font-weight: bold; }
    .nav-icon { font-size: 20px; margin-bottom: 2px; }
</style>

</div> </body>
</html>