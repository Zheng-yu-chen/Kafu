<?php
session_start();
include('db.php');
include('header.php');

// 權限檢查：只有管理員 (role_id 1) 可以訪問
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo "<script>alert('無權限訪問！'); window.location.href='login.php';</script>";
    exit();
}

$u_id = $_SESSION['u_id'];

// 撈取管理員基本資料
$admin_name = "系統管理員";
$admin_account = "";
$admin_photo = null;
$sql_admin = "SELECT name, accounts, user_photo FROM accounts WHERE u_id = $u_id";
$res_admin = $conn->query($sql_admin);
if ($res_admin && $row = $res_admin->fetch_assoc()) {
    $admin_name = $row['name'];
    $admin_account = $row['accounts'];
    $admin_photo = $row['user_photo'];
}

// 撈取待處理的錯誤回報數量
$pending_bugs = 0;
$res_bugs = $conn->query("SELECT COUNT(*) AS cnt FROM bugreports WHERE status = 0");
if ($res_bugs && $row = $res_bugs->fetch_assoc()) { $pending_bugs = $row['cnt']; }

// 撈取待處理的檢舉數量
$pending_complaints = 0;
// 加上防呆：如果 complaints 表還沒建，就不會報錯
try {
    $res_comp = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 0");
    if ($res_comp && $row = $res_comp->fetch_assoc()) { $pending_complaints = $row['cnt']; }
} catch (Exception $e) { }

?>
<style>
    body { background: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
    
    /* 頂部深藍色區塊 (與 Profile 相同) */
    .profile-header { background-color: #002B5B; color: white; padding: 60px 20px 80px; display: flex; align-items: center; gap: 15px; position: relative; }
    .avatar-circle { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; overflow: hidden; }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
    .user-info { display: flex; flex-direction: column; gap: 6px; }
    .user-info h2 { margin: 0; font-size: 20px; line-height: 1.2; }
    .user-info p { margin: 0; font-size: 13px; opacity: 0.8; }
    .admin-badge { background: #FF8C42; color: white; font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: bold; align-self: flex-start; margin-bottom: 4px; }

    /* 懸浮統計卡片 */
    .stats-card-combined { background: white; border-radius: 15px; padding: 20px; margin: -40px 20px 20px; position: relative; z-index: 10; box-shadow: 0 4px 15px rgba(0,0,0,0.08); display: flex; justify-content: space-around; text-align: center; }
    .stat-item { flex: 1; }
    .stat-val { font-size: 24px; font-weight: 900; color: #E53935; margin-bottom: 5px; }
    .stat-val.safe { color: #4CAF50; }
    .stat-label { font-size: 12px; color: #888; font-weight: bold; }
    .stat-divider { width: 1px; background: #eee; }

    /* 白色選單區塊 */
    .white-section { background: white; margin: 20px; padding: 20px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-header { font-weight: bold; font-size: 15px; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; color: #002B5B; }
    
    .menu-link { display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: #333; padding: 16px 0; border-bottom: 1px solid #f0f0f0; transition: 0.2s; }
    .menu-link:last-child { border-bottom: none; padding-bottom: 0; }
    .menu-link:hover { transform: translateX(5px); }
    .menu-text h4 { margin: 0 0 4px 0; font-size: 15px; display: flex; align-items: center; gap: 8px;}
    .menu-text p { margin: 0; font-size: 12px; color: #888; }
    
    .logout-section { text-align: center; margin: 30px 0 100px; }
    .logout-btn { display: inline-block; background-color: white; color: #F44336; border: 1.5px solid #FFCDD2; padding: 10px 40px; border-radius: 25px; text-decoration: none; font-size: 15px; font-weight: bold; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    .logout-btn:active { background-color: #FFF5F5; transform: scale(0.95); }
</style>

<div class="mobile-wrapper">
    <div class="profile-header">
        <div class="avatar-circle">
            <?php 
            if (!empty($admin_photo) && file_exists("uploads/" . $admin_photo)) {
                echo '<img src="uploads/' . htmlspecialchars($admin_photo) . '" alt="頭像">';
            } else {
                echo "👑"; // 管理員專屬王冠頭像
            }
            ?>
        </div>
        <div class="user-info">
            <h2><?php echo htmlspecialchars($admin_name); ?></h2>
            <p>帳號：<?php echo htmlspecialchars($admin_account); ?></p>
        </div>
    </div>

    <div class="stats-card-combined">
        <div class="stat-item">
            <div class="stat-val <?php echo ($pending_bugs == 0) ? 'safe' : ''; ?>"><?php echo $pending_bugs; ?></div>
            <div class="stat-label">待處理回報</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-val <?php echo ($pending_complaints == 0) ? 'safe' : ''; ?>"><?php echo $pending_complaints; ?></div>
            <div class="stat-label">待審核檢舉</div>
        </div>
    </div>

    <div class="white-section">
        <div class="section-header">👥 帳號管理中心</div>
        <a href="admin_stores.php" class="menu-link">
            <div class="menu-text">
                <h4>店家帳號管理</h4>
                <p>新增、刪除學餐店家與餐廳綁定</p>
            </div>
            <div style="color:#ccc;">❯</div>
        </a>
        <a href="admin_users.php" class="menu-link">
            <div class="menu-text">
                <h4>用戶權限與封鎖</h4>
                <p>管理一般用戶帳號、執行停權封鎖</p>
            </div>
            <div style="color:#ccc;">❯</div>
        </a>
    </div>

    <div class="white-section">
        <div class="section-header">⚖️ 系統審核與客服</div>
        <a href="admin_complaints.php" class="menu-link">
            <div class="menu-text">
                <h4>檢舉審核管理 <?php if($pending_complaints > 0) echo "<span style='color:red; font-size:12px;'>($pending_complaints)</span>"; ?></h4>
                <p>審核不當留言與違規使用者</p>
            </div>
            <div style="color:#ccc;">❯</div>
        </a>
        <a href="admin_reports.php" class="menu-link">
            <div class="menu-text">
                <h4>系統錯誤回報 <?php if($pending_bugs > 0) echo "<span style='color:red; font-size:12px;'>($pending_bugs)</span>"; ?></h4>
                <p>處理用戶回報的系統Bug或菜單錯誤</p>
            </div>
            <div style="color:#ccc;">❯</div>
        </a>
    </div>

    <div class="logout-section">
        <a href="logout.php" class="logout-btn">登出</a>
    </div>
</div>

<?php include('footer.php'); ?>