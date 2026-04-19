<?php
include('db.php');
include('header.php');
session_start();

$u_id = $_SESSION['u_id'] ?? null; 
$is_logged_in = ($u_id !== null);
$user_name = "訪客模式"; $user_account = "尚未登入"; $display_cal = 0; $display_pro = 0;

if ($is_logged_in) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $sql_user = "SELECT * FROM accounts WHERE u_id = $u_id";
    $user_result = $conn->query($sql_user);
    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_name = $user_data['name']; $user_account = $user_data['accounts'];
    }
    $today = date('Y-m-d');
    // 這裡的查詢未來會配合圖表做擴充，目前先抓今日總和
    $sql_stats = "SELECT SUM(i.calories) as total_cal, SUM(i.protein) as total_pro 
                  FROM consumptionlogs l JOIN items i ON l.item_id = i.item_id 
                  WHERE l.u_id = $u_id AND DATE(l.recorded_at) = '$today'";
    try {
        $stats_result = $conn->query($sql_stats);
        $stats = $stats_result->fetch_assoc();
        $display_cal = $stats['total_cal'] ?? 0; $display_pro = $stats['total_pro'] ?? 0;
    } catch (mysqli_sql_exception $e) { }
}
?>

<style>
    .profile-header { background-color: var(--fujen-blue, #002B5B); color: white; padding: 60px 20px 80px; display: flex; align-items: center; gap: 15px; }
    .avatar-circle { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    .user-info h2 { margin: 0; font-size: 20px; }
    .user-info p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }
    .stats-row { display: flex; gap: 15px; padding: 0 20px; margin-top: -56px; position: relative; z-index: 10; }
    .stat-card { flex: 1; background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0,0,0,0.05); border-radius: 12px; padding: 18px 15px; color: #333; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .stat-label { font-size: 11px; color: #888; margin-bottom: 5px; }
    .stat-value { font-size: 26px; font-weight: bold; color: var(--fujen-blue, #002B5B); }
    .stat-unit { font-size: 12px; margin-left: 3px; font-weight: normal; color: #888; }
    .white-section { background: white; margin: 20px; padding: 20px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-header { font-weight: bold; font-size: 15px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    .menu-link { display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #333; padding: 12px 0; border-top: 1px solid #f0f0f0; }
    .menu-text h4 { margin: 0; font-size: 14px; }
    .menu-text p { margin: 4px 0 0; font-size: 12px; color: var(--text-gray, #888); }
    .btn-login { margin-left: auto; background: white; color: var(--fujen-blue, #002B5B); padding: 6px 18px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: bold; }
    .logout-link { display: flex; justify-content: center; align-items: center; gap: 5px; color: #ff4d4d; font-size: 14px; margin-top: 30px; text-decoration: none; }
</style>

<div class="profile-header">
    <div class="avatar-circle"><?php echo $is_logged_in ? "👤" : "👣"; ?></div>
    <div class="user-info">
        <h2><?php echo htmlspecialchars($user_name); ?></h2>
        <p><?php echo $is_logged_in ? "帳號：" . htmlspecialchars($user_account) : "登入後開啟健康追蹤功能"; ?></p>
    </div>
    <?php if (!$is_logged_in): ?><a href="login.php" class="btn-login">登入</a><?php endif; ?>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">今日熱量</div>
        <div class="stat-value"><?php echo $display_cal; ?><span class="stat-unit">kcal</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">今日蛋白質</div>
        <div class="stat-value"><?php echo $display_pro; ?><span class="stat-unit">g</span></div>
    </div>
</div>

<div class="white-section">
    <div class="section-header"><span>📈</span> 本週攝取趨勢</div>
    
    <?php if ($is_logged_in): ?>
        <canvas id="trendChart" height="150"></canvas>
    <?php else: ?>
        <div style="text-align:center; padding:20px 0;">
            <div style="font-size:35px; margin-bottom:10px; opacity:0.3;">📊</div>
            <p style="margin:0; font-size:13px; color:#999;">登入後即可解鎖專屬攝取趨勢</p>
            <a href="login.php" style="display:inline-block; margin-top:15px; color:var(--fujen-blue, #002B5B); text-decoration:none; font-weight:bold; font-size:13px; border:1px solid var(--fujen-blue, #002B5B); padding:6px 20px; border-radius:20px; transition:0.2s;">
                前往登入
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="white-section">
    <div class="section-header"><span>📅</span> 歷史紀錄</div>
    <a href="<?php echo $is_logged_in ? 'history.php' : 'login.php'; ?>" class="menu-link">
        <div class="menu-text"><h4>查看完整歷史紀錄</h4><p><?php echo $is_logged_in ? "檢視您的飲食數據" : "登入後即可查看歷史紀錄"; ?></p></div>
        <div style="color:#ccc;">❯</div>
    </a>
</div>

<div class="white-section">
    <div class="section-header"><span>⚙️</span> 設定</div>
    <a href="<?php echo $is_logged_in ? 'settings.php' : 'login.php'; ?>" class="menu-link">
        <div class="menu-text">
            <h4>個人目標設定</h4>
            <p>修改目標熱量與密碼</p>
        </div>
        <div style="color:#ccc;">❯</div>
    </a>
</div>

<?php if ($is_logged_in): ?><a href="logout.php" class="logout-link">登出</a><?php endif; ?>

<?php if ($is_logged_in): ?>
<script>
    if(document.getElementById('trendChart')) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['04/13', '04/14', '04/15', '04/16', '04/17', '04/18', '今日'],
                datasets: [{ label: '熱量', data: [1800, 2150, 1900, 2200, 1750, 2100, <?php echo $display_cal; ?>], borderColor: '#FF8C42', backgroundColor: 'rgba(255, 140, 66, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } } }
        });
    }
</script>
<?php endif; ?>

<?php include('footer.php'); ?>