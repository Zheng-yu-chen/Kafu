<div class="footer-nav">
    <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <div class="nav-icon">🏠</div>
        <span>店家</span>
    </a>
    <a href="tray.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'tray.php') ? 'active' : ''; ?>">
        <div class="nav-icon">📋</div>
        <span>托盤</span>
    </a>
    <a href="comment.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'comments.php') ? 'active' : ''; ?>">
        <div class="nav-icon">💬</div>
        <span>評價</span>
    </a>
    <a href="profile.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
        <div class="nav-icon">👤</div>
        <span>我的</span>
    </a>
</div>

<style>
    .footer-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #ffffff;
        display: flex;
        justify-content: space-around;
        padding: 10px 0;
        border-top: 1px solid #eeeeee;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        z-index: 9999; /* 確保在最上層 */
    }
    .nav-item {
        text-decoration: none;
        color: #bbbbbb; /* 未選中時的灰色 */
        text-align: center;
        flex: 1;
        font-size: 12px;
        transition: 0.3s;
    }
    .nav-item.active {
        color: #002B5B; /* 輔大藍，選中時的顏色 */
        font-weight: bold;
    }
    .nav-icon {
        font-size: 20px;
        margin-bottom: 2px;
    }
    /* 自動幫 body 留白，避免內容被遮住 */
    body {
        padding-bottom: 70px; 
    }
</style>