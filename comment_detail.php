<?php
include('db.php');
include('header.php');

$item_id = $_GET['item_id'] ?? 0;

// 1. 撈取餐點與餐廳資訊
$stmt_item = $conn->prepare("SELECT i.name, r.name as res_name FROM items i JOIN categories c ON i.c_id = c.c_id JOIN restaurants r ON c.r_id = r.r_id WHERE i.item_id = ?");
$stmt_item->bind_param("i", $item_id);
$stmt_item->execute();
$item_info = $stmt_item->get_result()->fetch_assoc();

// 2. 撈取該餐點所有評論[cite: 1]
$stmt_comments = $conn->prepare("SELECT c.*, a.name as user_name FROM comments c LEFT JOIN accounts a ON c.u_id = a.u_id WHERE c.item_id = ? AND c.status = 1 ORDER BY c.created_at DESC");
$stmt_comments->bind_param("i", $item_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
?>

<style>
    /* 縮圖樣式 */
    .comment-img-thumb {
        width: 85px;
        height: 85px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        border: 1px solid #eee;
        transition: 0.2s;
    }
    .comment-img-thumb:hover { opacity: 0.8; }

    /* 全螢幕遮罩 - 這裡設定超高 z-index 並加上 !important */
    .modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999 !important; 
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.95);
        justify-content: center;
        align-items: center;
        cursor: zoom-out;
    }

    .modal-overlay img {
        max-width: 100%;
        max-height: 100vh;
        object-fit: contain;
    }

    .back-btn { text-decoration:none; color:#002B5B; font-size: 14px; display: inline-block; margin-bottom: 15px; }
</style>

<div style="padding: 20px; max-width: 600px; margin: 0 auto; padding-bottom: 100px;">
    <a href="comments.php" class="back-btn">← 返回評價列表</a>
    
    <h2 style="margin-top:5px;"><?php echo htmlspecialchars($item_info['name']); ?></h2>
    <p style="color:#666; font-size: 14px;"><?php echo htmlspecialchars($item_info['res_name']); ?> 的評價</p>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

    <?php if ($comments_result->num_rows > 0): ?>
        <?php while($com = $comments_result->fetch_assoc()): ?>
            <div style="background:white; padding:15px; border-radius:12px; margin-bottom:15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0f0f0;">
                <div style="display:flex; justify-content:space-between; align-items: center;">
                    <strong><?php echo htmlspecialchars($com['user_name'] ?? '匿名使用者'); ?></strong>
                    <span style="color:#FF8C42;">★ <?php echo $com['rating']; ?></span>
                </div>
                
                <p style="margin:12px 0; color:#444; font-size: 15px; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($com['content'])); ?>
                </p>

                <?php if (!empty($com['com_img'])): ?>
                    <div style="margin: 10px 0;">
                        <img src="food/<?php echo htmlspecialchars($com['com_img']); ?>" 
                             class="comment-img-thumb" 
                             onclick="openFullImage(this.src)"
                             alt="用餐照片">
                    </div>
                <?php endif; ?>

                <small style="color:#bbb; font-size: 11px;"><?php echo $com['created_at']; ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color:#ccc; margin-top:50px;">目前尚無評價</p>
    <?php endif; ?>
</div>

<!-- 放大圖片的遮罩 -->
<div id="imageModal" class="modal-overlay" onclick="closeFullImage()">
    <img id="fullImage" src="" alt="放大照片">
</div>

<script>
    function openFullImage(src) {
        // 1. 設定圖片並顯示遮罩
        document.getElementById('fullImage').src = src;
        document.getElementById('imageModal').style.display = 'flex';
        
        // 2. 💡 隱藏底部的工具列 (通常是 footer 或 nav 標籤)
        const toolbar = document.querySelector('footer') || document.querySelector('nav') || document.querySelector('.footer');
        if (toolbar) {
            toolbar.style.display = 'none';
        }
    }

    function closeFullImage() {
        // 1. 隱藏遮罩
        document.getElementById('imageModal').style.display = 'none';
        
        // 2. 💡 恢復顯示工具列
        const toolbar = document.querySelector('footer') || document.querySelector('nav') || document.querySelector('.footer');
        if (toolbar) {
            toolbar.style.display = 'flex'; // 或是 'block'，視你原本樣式而定
        }
    }
</script>

<?php include('footer.php'); ?>