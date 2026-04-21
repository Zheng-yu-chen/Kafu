<?php
include('db.php');
include('header.php');

$item_id = $_GET['item_id'] ?? 0;

// 1. 撈取餐點基本資訊
$stmt_item = $conn->prepare("SELECT i.name, r.name as res_name FROM items i JOIN categories c ON i.c_id = c.c_id JOIN restaurants r ON c.r_id = r.r_id WHERE i.item_id = ?");
$stmt_item->bind_param("i", $item_id);
$stmt_item->execute();
$item_info = $stmt_item->get_result()->fetch_assoc();

// 2. 撈取該餐點所有「已通過審核(status=1)」的評價
$stmt_comments = $conn->prepare("SELECT c.*, a.name as user_name FROM comments c LEFT JOIN accounts a ON c.u_id = a.u_id WHERE c.item_id = ? AND c.status = 1 ORDER BY c.created_at DESC");
$stmt_comments->bind_param("i", $item_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
?>

<div style="padding: 20px;">
    <a href="comments.php" style="text-decoration:none; color:#002B5B;">← 返回評價列表</a>
    <h2 style="margin-top:20px;"><?php echo htmlspecialchars($item_info['name']); ?></h2>
    <p style="color:#666;"><?php echo htmlspecialchars($item_info['res_name']); ?> 的所有評價</p>

    <hr>

    <?php if ($comments_result->num_rows > 0): ?>
        <?php while($com = $comments_result->fetch_assoc()): ?>
            <div style="background:white; padding:15px; border-radius:10px; margin-bottom:10px; border:1px solid #eee;">
                <div style="display:flex; justify-content:space-between;">
                    <strong><?php echo htmlspecialchars($com['user_name'] ?? '匿名使用者'); ?></strong>
                    <span style="color:#FF8C42;">★ <?php echo $com['rating']; ?></span>
                </div>
                <p style="margin:10px 0; color:#444;"><?php echo nl2br(htmlspecialchars($com['content'])); ?></p>
                <small style="color:#999;"><?php echo $com['created_at']; ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color:#ccc; margin-top:30px;">目前尚無人評價此餐點</p>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>