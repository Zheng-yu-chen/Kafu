<?php
session_start();
include('db.php');
include('header.php');

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { header("Location: login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_store') {
        $store_name = trim($_POST['store_name']);
        $account = trim($_POST['account']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $r_id = !empty($_POST['r_id']) ? intval($_POST['r_id']) : null;
        try {
            $stmt = $conn->prepare("INSERT INTO accounts (name, accounts, password, role_id, r_id) VALUES (?, ?, ?, 2, ?)");
            $stmt->bind_param("sssi", $store_name, $account, $password, $r_id);
            $stmt->execute();
            echo "<script>alert('店家帳號新增成功！'); window.location.href='admin_stores.php';</script>";
        } catch (Exception $e) { echo "<script>alert('新增失敗，帳號可能已存在！');</script>"; }
    } elseif ($_POST['action'] === 'delete') {
        $del_id = intval($_POST['u_id']);
        $conn->query("DELETE FROM accounts WHERE u_id = $del_id AND role_id = 2");
        header("Location: admin_stores.php"); exit();
    }
}

$stores = [];
$res = $conn->query("SELECT a.u_id, a.name, a.accounts, r.name AS res_name FROM accounts a LEFT JOIN restaurants r ON a.r_id = r.r_id WHERE a.role_id = 2");
while ($row = $res->fetch_assoc()) $stores[] = $row;

$restaurants = [];
$res2 = $conn->query("SELECT r_id, name FROM restaurants");
while ($row = $res2->fetch_assoc()) $restaurants[] = $row;
?>
<style>
    .page-content { padding: 20px; padding-bottom: 80px; }
    .card { background: white; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .btn { padding: 8px 15px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white; }
    .btn-add { background: #FF8C42; width: 100%; padding: 12px; margin-bottom: 20px; }
    .btn-danger { background: #FF3B30; font-size: 12px; }
    input, select { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;}
</style>

<div class="mobile-wrapper">
    <div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
        <a href="admin_dashboard.php" style="color: white; text-decoration: none; font-size: 22px;">❮</a>
        <h1 style="margin: 0; font-size: 20px;">店家帳號管理</h1>
    </div>

    <div class="page-content">
        <button class="btn btn-add" onclick="document.getElementById('addBox').style.display='block'">+ 新增店家帳號</button>
        
        <div id="addBox" class="card" style="display: none; border: 2px solid #002B5B;">
            <h3 style="margin-top:0;">新增店家</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_store">
                <label>店家顯示名稱</label><input type="text" name="store_name" required>
                <label>登入帳號</label><input type="text" name="account" required>
                <label>登入密碼</label><input type="password" name="password" required>
                <label>綁定餐廳</label>
                <select name="r_id">
                    <option value="">-- 暫不綁定 --</option>
                    <?php foreach($restaurants as $r): ?>
                        <option value="<?php echo $r['r_id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn" style="background:#002B5B; flex:2;">儲存</button>
                    <button type="button" class="btn" style="background:#ccc; flex:1;" onclick="document.getElementById('addBox').style.display='none'">取消</button>
                </div>
            </form>
        </div>

        <?php foreach($stores as $s): ?>
        <div class="card" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="font-size: 16px;"><?php echo htmlspecialchars($s['name']); ?></strong>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">帳號: <?php echo htmlspecialchars($s['accounts']); ?></div>
                <div style="font-size: 12px; color: #FF8C42; font-weight: bold;">📍 <?php echo htmlspecialchars($s['res_name'] ?? '未綁定'); ?></div>
            </div>
            <form method="POST" onsubmit="return confirm('確定刪除此店家？');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="u_id" value="<?php echo $s['u_id']; ?>">
                <button type="submit" class="btn btn-danger">刪除</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include('footer.php'); ?>