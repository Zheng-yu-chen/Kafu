<?php
session_start();
include('db.php');
include('header.php');

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { header("Location: login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_u_id = intval($_POST['u_id']);
    $new_status = intval($_POST['new_status']);
    $stmt = $conn->prepare("UPDATE accounts SET is_blocked = ? WHERE u_id = ? AND role_id = 3");
    $stmt->bind_param("ii", $new_status, $target_u_id);
    $stmt->execute();
    header("Location: admin_users.php"); exit();
}

$users = [];
$res = $conn->query("SELECT u_id, name, accounts, is_blocked FROM accounts WHERE role_id = 3");
if($res) { while ($row = $res->fetch_assoc()) $users[] = $row; }
?>
<style>
    .page-content { padding: 20px; padding-bottom: 80px; }
    .user-card { background: white; padding: 15px; border-radius: 12px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;}
    .btn { padding: 6px 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; color: white; font-size: 13px;}
</style>

<div class="mobile-wrapper">
    <div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
        <a href="admin_dashboard.php" style="color: white; text-decoration: none; font-size: 22px;">❮</a>
        <h1 style="margin: 0; font-size: 20px;">用戶權限管理</h1>
    </div>

    <div class="page-content">
        <?php foreach($users as $u): ?>
        <div class="user-card">
            <div>
                <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                <div style="font-size: 12px; color: #888;">帳號: <?php echo htmlspecialchars($u['accounts']); ?></div>
            </div>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="u_id" value="<?php echo $u['u_id']; ?>">
                <?php if(isset($u['is_blocked']) && $u['is_blocked'] == 1): ?>
                    <input type="hidden" name="new_status" value="0">
                    <button type="submit" class="btn" style="background:#34C759;">解鎖帳號</button>
                <?php else: ?>
                    <input type="hidden" name="new_status" value="1">
                    <button type="submit" class="btn" style="background:#FF3B30;" onclick="return confirm('確定封鎖此用戶？');">停權封鎖</button>
                <?php endif; ?>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include('footer.php'); ?>