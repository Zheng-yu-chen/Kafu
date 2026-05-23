<?php
session_start();
include('db.php');
include('header.php');

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { header("Location: login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = intval($_POST['report_id']);
    $stmt = $conn->prepare("UPDATE bugreports SET status = 1 WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    header("Location: admin_reports.php"); exit();
}

$openReports = [];
$closedReports = [];
$res = $conn->query("SELECT b.*, a.name AS reporter_name FROM bugreports b LEFT JOIN accounts a ON b.u_id = a.u_id WHERE b.status = 0 ORDER BY b.created_at DESC");
if($res) { while ($row = $res->fetch_assoc()) $openReports[] = $row; }
$res2 = $conn->query("SELECT b.*, a.name AS reporter_name FROM bugreports b LEFT JOIN accounts a ON b.u_id = a.u_id WHERE b.status = 1 ORDER BY b.created_at DESC");
if($res2) { while ($row = $res2->fetch_assoc()) $closedReports[] = $row; }
?>
<style>
    .page-content { padding: 20px; padding-bottom: 80px; }
    .section-title { font-size: 18px; font-weight: bold; margin: 20px 0 12px; }
    .report-card { background: white; border-left: 5px solid #FF3B30; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .report-card.closed { border-left-color: #8E8E93; opacity: 0.95; }
    .report-meta { font-size: 12px; color: #999; display: flex; justify-content: space-between; }
    .btn-resolve { background: #34C759; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
    .empty-state { text-align: center; color: #999; margin-top: 50px; }
</style>

<div class="mobile-wrapper">
    <div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
        <a href="admin_dashboard.php" style="color: white; text-decoration: none; font-size: 22px;">❮</a>
        <h1 style="margin: 0; font-size: 20px;">錯誤回報與檢舉</h1>
    </div>

    <div class="page-content">
        <div class="section-title">待處理回報</div>
        <?php if(count($openReports) > 0): ?>
            <?php foreach($openReports as $rep): ?>
                <div class="report-card">
                    <div style="font-weight: bold; color: #333; font-size: 16px; margin-bottom: 8px;">⚠️ <?php echo htmlspecialchars($rep['title']); ?></div>
                    <div style="font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 10px;"><?php echo nl2br(htmlspecialchars($rep['description'])); ?></div>
                    <div class="report-meta">
                        <span>回報者: <?php echo htmlspecialchars($rep['reporter_name'] ?? '匿名'); ?></span>
                        <span><?php echo substr($rep['created_at'], 0, 16); ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="report_id" value="<?php echo $rep['report_id']; ?>">
                        <button type="submit" class="btn-resolve">✓ 標記為已處理</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                目前沒有待處理的檢舉或錯誤！
            </div>
        <?php endif; ?>

        <div class="section-title">已處理回報</div>
        <?php if(count($closedReports) > 0): ?>
            <?php foreach($closedReports as $rep): ?>
                <div class="report-card closed">
                    <div style="font-weight: bold; color: #333; font-size: 16px; margin-bottom: 8px;">✅ <?php echo htmlspecialchars($rep['title']); ?></div>
                    <div style="font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 10px;"><?php echo nl2br(htmlspecialchars($rep['description'])); ?></div>
                    <div class="report-meta">
                        <span>回報者: <?php echo htmlspecialchars($rep['reporter_name'] ?? '匿名'); ?></span>
                        <span><?php echo substr($rep['created_at'], 0, 16); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                目前沒有已處理的回報。
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include('footer.php'); ?>