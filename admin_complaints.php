<?php
session_start();
include('db.php');
include('header.php');

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { header("Location: login.php"); exit(); }

// 處理檢舉完成
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comp_id'])) {
    $comp_id = intval($_POST['comp_id']);
    $stmt = $conn->prepare("UPDATE complaints SET status = 1 WHERE comp_id = ?");
    $stmt->bind_param("i", $comp_id);
    $stmt->execute();
    header("Location: admin_complaints.php"); exit();
}

// 撈取檢舉留言 (target_type = 'comment')
$comment_complaints = [];
try {
    $sql = "SELECT c.*, a1.name AS reporter_name, a2.name AS target_user_name, cm.content AS bad_comment 
            FROM complaints c 
            JOIN accounts a1 ON c.reporter_id = a1.u_id
            JOIN comments cm ON c.target_id = cm.com_id
            JOIN accounts a2 ON cm.u_id = a2.u_id
            WHERE c.status = 0 AND c.target_type = 'comment' ORDER BY c.created_at DESC";
    $res = $conn->query($sql);
    if($res) { while ($row = $res->fetch_assoc()) $comment_complaints[] = $row; }
} catch (Exception $e) {}

// 撈取檢舉使用者 (target_type = 'user')
$user_complaints = [];
try {
    $sql2 = "SELECT c.*, a1.name AS reporter_name, a2.name AS bad_user_name, a2.accounts AS bad_user_acc 
             FROM complaints c 
             JOIN accounts a1 ON c.reporter_id = a1.u_id
             JOIN accounts a2 ON c.target_id = a2.u_id
             WHERE c.status = 0 AND c.target_type = 'user' ORDER BY c.created_at DESC";
    $res2 = $conn->query($sql2);
    if($res2) { while ($row = $res2->fetch_assoc()) $user_complaints[] = $row; }
} catch (Exception $e) {}

?>
<style>
    .page-content { padding: 20px; padding-bottom: 80px; }
    
    /* 分類標籤 */
    .tab-container { display: flex; background: white; border-bottom: 1px solid #ddd; position: sticky; top: 52px; z-index: 100; }
    .tab-btn { flex: 1; text-align: center; padding: 15px 0; cursor: pointer; color: #666; font-weight: bold; font-size: 15px; }
    .tab-btn.active { color: #E53935; border-bottom: 3px solid #E53935; }
    .content-section { display: none; }
    .content-section.active { display: block; animation: fadeIn 0.3s; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* 卡片設計 */
    .comp-card { background: white; border-left: 5px solid #FF8C42; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .comp-card.user-type { border-left-color: #E53935; }
    .comp-title { font-weight: bold; color: #333; font-size: 15px; margin-bottom: 8px; display: flex; justify-content: space-between;}
    .comp-target { background: #f4f6f8; padding: 10px; border-radius: 8px; font-size: 13px; color: #555; margin-bottom: 10px; border: 1px dashed #ccc; }
    .btn-resolve { background: #34C759; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; }
</style>

<div class="mobile-wrapper">
    <div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
        <a href="admin_dashboard.php" style="color: white; text-decoration: none; font-size: 22px;">❮</a>
        <h1 style="margin: 0; font-size: 20px;">檢舉審核管理</h1>
    </div>

    <div class="tab-container">
        <div class="tab-btn active" onclick="switchTab('comments')">💬 留言檢舉 (<?php echo count($comment_complaints); ?>)</div>
        <div class="tab-btn" onclick="switchTab('users')">🚫 用戶檢舉 (<?php echo count($user_complaints); ?>)</div>
    </div>

    <div class="page-content">
        <div id="tab-comments" class="content-section active">
            <?php if(count($comment_complaints) > 0): ?>
                <?php foreach($comment_complaints as $c): ?>
                    <div class="comp-card">
                        <div class="comp-title">
                            <span>檢舉理由：<?php echo htmlspecialchars($c['reason']); ?></span>
                            <span style="font-size: 12px; color: #999; font-weight: normal;"><?php echo date('m/d H:i', strtotime($c['created_at'])); ?></span>
                        </div>
                        <div class="comp-target">
                            <span style="color: #E53935; font-weight: bold;">[被檢舉留言]</span> <?php echo htmlspecialchars($c['target_user_name']); ?>：<br>
                            "<?php echo htmlspecialchars($c['bad_comment']); ?>"
                        </div>
                        <div style="font-size: 12px; color: #888; margin-bottom: 10px;">檢舉人：<?php echo htmlspecialchars($c['reporter_name']); ?></div>
                        <form method="POST">
                            <input type="hidden" name="comp_id" value="<?php echo $c['comp_id']; ?>">
                            <button type="submit" class="btn-resolve">✓ 標示為已處理</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #999; margin-top: 40px;">目前沒有被檢舉的留言</div>
            <?php endif; ?>
        </div>

        <div id="tab-users" class="content-section">
            <?php if(count($user_complaints) > 0): ?>
                <?php foreach($user_complaints as $c): ?>
                    <div class="comp-card user-type">
                        <div class="comp-title">
                            <span>檢舉理由：<?php echo htmlspecialchars($c['reason']); ?></span>
                            <span style="font-size: 12px; color: #999; font-weight: normal;"><?php echo date('m/d H:i', strtotime($c['created_at'])); ?></span>
                        </div>
                        <div class="comp-target">
                            <span style="color: #E53935; font-weight: bold;">[被檢舉帳號]</span><br>
                            名稱：<?php echo htmlspecialchars($c['bad_user_name']); ?><br>
                            帳號：<?php echo htmlspecialchars($c['bad_user_acc']); ?>
                        </div>
                        <div style="font-size: 12px; color: #888; margin-bottom: 10px;">檢舉人：<?php echo htmlspecialchars($c['reporter_name']); ?></div>
                        <form method="POST">
                            <input type="hidden" name="comp_id" value="<?php echo $c['comp_id']; ?>">
                            <button type="submit" class="btn-resolve">✓ 標示為已處理</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #999; margin-top: 40px;">目前沒有被檢舉的帳號</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }
</script>

<?php include('footer.php'); ?>