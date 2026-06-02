<?php
session_start();
include('db.php');
include('header.php');

$r_id = $_GET['r_id'] ?? 0;

// 1. 撈取餐廳基本資訊
$stmt_res = $conn->prepare("SELECT name, location, image_url FROM restaurants WHERE r_id = ?");
$stmt_res->bind_param("i", $r_id);
$stmt_res->execute();
$res_info = $stmt_res->get_result()->fetch_assoc();

if (!$res_info) {
    echo "<script>alert('找不到該店家資訊！'); window.location.href='comments.php';</script>";
    exit;
}

// 🎯 撈取這家餐廳的所有餐點品項，供外部 update_comments.php 檔案的選單使用
$all_items = [];
$stmt_all_items = $conn->prepare("
    SELECT i.item_id, i.name 
    FROM items i 
    JOIN categories cat ON i.c_id = cat.c_id 
    WHERE cat.r_id = ?
");
$stmt_all_items->bind_param("i", $r_id);
$stmt_all_items->execute();
$items_res = $stmt_all_items->get_result();
while ($row = $items_res->fetch_assoc()) {
    $all_items[] = $row;
}

// 2. 撈取屬於該餐廳所有餐點的詳細評論，並顯示餐點名稱
$stmt_comments = $conn->prepare("
    SELECT c.*, i.name as item_name, a.name as user_name, a.user_photo 
    FROM comments c 
    JOIN items i ON c.item_id = i.item_id 
    JOIN categories cat ON i.c_id = cat.c_id 
    LEFT JOIN accounts a ON c.u_id = a.u_id 
    WHERE cat.r_id = ? 
    ORDER BY c.created_at DESC
");
$stmt_comments->bind_param("i", $r_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();

// 檢查身分與權限變數
$is_current_shop_owner = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2 && isset($_SESSION['r_id']) && $_SESSION['r_id'] == $r_id;
$is_any_shop_owner = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
$is_admin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
$current_user_id = $_SESSION['u_id'] ?? null;

// 店家回覆評論處理邏輯
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    
    // 後端權限驗證：只有目前該餐廳的店家允許提交回覆
    if (!$is_current_shop_owner) {
        echo "<script>
            alert('您沒有權限回覆此餐廳的評論！');
            window.location.href = window.location.href;
        </script>";
        exit;
    }

    $com_id = intval($_POST['com_id']);
    $reply_content = trim($_POST['reply_content']);

    if (!empty($reply_content)) {
        $stmt_reply = $conn->prepare("
            UPDATE comments
            SET reply_content = ?, 
                reply_created_at = NOW()
            WHERE com_id = ?
        ");

        $stmt_reply->bind_param("si", $reply_content, $com_id);
        $stmt_reply->execute();

        echo "<script>
            alert('回覆成功！');
            window.location.href = window.location.href;
        </script>";
        exit;
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .comment-img-thumb {
        width: 85px;
        height: 85px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        border: 1px solid #eee;
        transition: opacity 0.2s;
    }
    .comment-img-thumb:hover { opacity: 0.8; }

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
    .modal-overlay img { max-width: 100%; max-height: 100vh; object-fit: contain; }
    
    .back-btn { 
        text-decoration: none !important; 
        color: #002B5B !important; 
        font-size: 14px; 
        display: inline-block; 
        margin-bottom: 20px; 
        font-weight: bold; 
    }
    .back-btn:hover { opacity: 0.7; }
    
    .user-tag { 
        color: #002B5B; 
        font-size: 13px; 
        font-weight: bold; 
        display: inline-flex; 
        align-items: center;
        gap: 8px;
        margin-bottom: 10px; 
    }

    .user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #ddd;
    }

    .user-avatar-placeholder {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #e9ecef; 
        border: 1px solid #ced4da; 
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        font-size: 14px;
    }

    .filter-control-bar {
        display: flex;
        gap: 8px; 
        margin-bottom: 20px;
    }
    .search-input-wrapper {
        position: relative;
        flex: 1;
    }
    
    .search-input-wrapper .search-icon {
        position: absolute;
        right: 12px;         
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        object-fit: contain;
        opacity: 0.6;
        cursor: pointer; 
        transition: transform 0.1s ease, opacity 0.2s;
    }
    .search-input-wrapper .search-icon:hover { opacity: 1; }
    

    .search-input-wrapper .search-icon:active {
        transform: translateY(-50%) scale(0.8);
        opacity: 0.5;
    }
    
    .control-search {
        width: 100%;
        height: 32px; 
        padding: 0 35px 0 12px; 
        border-radius: 20px;
        border: 1px solid #ddd;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        background: white;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .control-search:focus {
        border-color: #002B5B;
        box-shadow: 0 2px 8px rgba(0, 43, 91, 0.1);
    }

    .control-sort, .control-filter-stars {
        height: 32px; 
        padding: 0 10px;
        border-radius: 20px;
        border: 1px solid #ddd;
        font-size: 13px;
        color: #002B5B;
        font-weight: bold;
        background: white;
        outline: none;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    .comment-card-item { position: relative; }
    .comment-delete-btn {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: rgba(244,67,54,0.1);
        color: #D32F2F;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: background 0.2s, transform 0.1s;
        z-index: 5;
    }
    .comment-delete-btn:hover {
        background: rgba(244,67,54,0.18);
        transform: scale(1.03);
    }

    .hidden { display: none !important; }

    .user-maintenance-bar { display: inline-flex; gap: 8px; margin-left: 10px; }
    .btn-user-action { background: none; border: none; font-size: 12px; cursor: pointer; padding: 2px 6px; border-radius: 5px; font-weight: bold; }
    .btn-user-edit-text { color: #002B5B; background: rgba(0, 43, 91, 0.05); display: inline-flex; align-items: center; gap: 3px; }
    .btn-user-edit-text:hover { background: rgba(0, 43, 91, 0.12); }
    .btn-user-delete-text { color: #D32F2F; background: rgba(211, 47, 47, 0.05); display: inline-flex; align-items: center; gap: 3px; }
    .btn-user-delete-text:hover { background: rgba(211, 47, 47, 0.12); }

    .report-user-btn {
        background-color: #f0f2f5;
        border: none;
        color: #888; 
        cursor: pointer;
        width: 28px;
        height: 28px;
        border-radius: 50%; 
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px; 
        transition: background-color 0.2s, color 0.2s, transform 0.1s;
    }

    .report-user-btn:hover {
        background-color: rgba(211, 47, 47, 0.1) !important;
        color: #D32F2F !important;
        transform: scale(1.05);
    }

    .report-user-btn:active {
        transform: scale(0.95);
    }
</style>

<div style="padding: 20px; max-width: 600px; margin: 0 auto; padding-bottom: 100px;">
    
    <div style="margin-bottom: 15px; position: relative; z-index: 10;">
        <a href="comments.php" class="back-btn" style="text-decoration: none !important; color: #002B5B !important; font-size: 14px; font-weight: bold; display: inline-block;">
            ❮ 返回店家列表
        </a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 5px; margin-bottom: 15px; position: relative; z-index: 5;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="images/<?php echo htmlspecialchars($res_info['image_url'] ?: 'default.jpg'); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
            <div>
                <h2 style="margin: 0; font-size: 22px; color: #000;"><?php echo htmlspecialchars($res_info['name']); ?></h2>
            </div>
        </div>
        
        <?php if ($current_user_id !== null && !$is_current_shop_owner && !$is_admin&& !$is_any_shop_owner): ?>
            <a href="add_comment.php?r_id=<?php echo $r_id; ?>" 
            style="text-decoration: none; background: #002B5B; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(0,43,91,0.15);">
            
            <span style="transform: translateY(1.5px); display: inline-block;"><i class="fa-solid fa-camera"></i></span>新增評論

            </a>
        <?php endif; ?>
    </div> 
    
    <div class="filter-control-bar">
    </div>
    
    <div class="filter-control-bar">
        <div class="search-input-wrapper">
            <img src="icon/search_icon.png" class="search-icon" alt="搜尋" onclick="filterAndSortComments()">
            <input type="text" id="commentSearch" class="control-search" placeholder="搜尋餐點或關鍵字..." onkeydown="if(event.key === 'Enter') filterAndSortComments()">
        </div>
        
        <select id="commentFilterStars" class="control-filter-stars" onchange="filterAndSortComments()">
            <option value="all">★所有</option>
            <option value="5">★5星</option>
            <option value="4">★4星</option>
            <option value="3">★3星</option>
            <option value="2">★2星</option>
            <option value="1">★1星</option>
        </select>

        <select id="commentSort" class="control-sort" onchange="filterAndSortComments()">
            <option value="latest">⏰︎最新評論</option>
            <option value="has_image">含餐點圖片</option>
            <option value="one_month">一個月內</option>
            <option value="one_week">一個禮拜內</option>
        </select>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0 20px;">

    <div id="commentsContainer">
        <?php if ($comments_result->num_rows > 0): ?>
            <?php while($com = $comments_result->fetch_assoc()): ?>
                
                <div class="comment-card-item" 
                     id="comment-card-<?php echo $com['com_id']; ?>"
                     data-rating="<?php echo $com['rating']; ?>"
                     data-time="<?php echo strtotime($com['created_at']); ?>"
                     data-has-image="<?php echo !empty($com['com_img']) ? 'true' : 'false'; ?>"
                     data-text="<?php echo htmlspecialchars(mb_strtolower($com['item_name'] . $com['content'])); ?>">
                    
                    <div style="background:white; padding:15px 15px 15px 15px; border-radius:12px; margin-bottom:15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; position: relative; box-sizing: border-box; padding-right: 58px;">
                        <?php if ($is_admin): ?>
                            <button class="comment-delete-btn" onclick="deleteComment(<?php echo $com['com_id']; ?>, this)" title="刪除評論">🗑</button>
                        <?php endif; ?>
                        
                        <div class="user-tag" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <?php if (!empty($com['user_photo'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($com['user_photo']); ?>" 
                                         class="user-avatar" 
                                         alt="頭像" 
                                         onerror="this.onerror=null; this.src=this.src.replace('.jpg', '.JPG');">
                                <?php else: ?>
                                    <div class="user-avatar-placeholder">👤</div>
                                <?php endif; ?>
                                
                                <?php echo htmlspecialchars($com['user_name'] ?? '匿名使用者'); ?>
                            </div>

                          <?php
                            $can_report_comment = false;
                            if ($current_user_id !== null && (int)$current_user_id !== (int)$com['u_id'] && !$is_admin) {
                                $can_report_comment = !$is_any_shop_owner || $is_current_shop_owner;
                            }
                          ?>
                          <?php if ($can_report_comment): ?>
                                <button class="report-user-btn" onclick="reportComment(<?php echo $com['com_id']; ?>)" title="檢舉此評論">
                                    <i class="fa-solid fa-triangle-exclamation"></i> </button>
                          <?php endif; ?>
                        </div>
                        
                        <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom: 4px;">
                            <strong id="comment-item-name-<?php echo $com['com_id']; ?>" style="font-size: 16px; color: #333;"><?php echo htmlspecialchars($com['item_name']); ?></strong>
                            <span style="color:#FF8C42; font-weight: bold;">★ <span id="comment-rating-num-<?php echo $com['com_id']; ?>"><?php echo $com['rating']; ?></span></span>
                        </div>

                        <p id="comment-text-content-<?php echo $com['com_id']; ?>" style="margin:4px 0 12px; color:#444; font-size: 15px; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($com['content'])); ?>
                        </p>

                        <?php if (!empty($com['reply_content'])): ?>
                            <div style="
                                margin-top:12px;
                                background:#f5f7fb;
                                border-left:4px solid #002B5B;
                                padding:10px 12px;
                                border-radius:8px;
                            ">
                                <div style="font-size:13px; font-weight:bold; color:#002B5B; margin-bottom:5px;">
                                    店家回覆
                                </div>
                                <div style="font-size:14px; color:#444; line-height:1.5;">
                                    <?php echo nl2br(htmlspecialchars($com['reply_content'])); ?>
                                </div>
                                <div style="text-align:right; margin-top:5px;">
                                    <small style="color:#aaa;">
                                        <?php echo $com['reply_created_at']; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="comment-image-block-<?php echo $com['com_id']; ?>" style="margin: 10px 0;">
                            <?php if (!empty($com['com_img'])): ?>
                                <img src="food/<?php echo htmlspecialchars($com['com_img']); ?>" class="comment-img-thumb" onclick="openFullImage(this.src)" alt="用餐照片">
                            <?php endif; ?>
                        </div>

                        <div style="text-align: right; display: flex; justify-content: space-between; align-items: center;">
                            <small id="comment-time-box-<?php echo $com['com_id']; ?>" style="color:#bbb; font-size: 11px;">
                                <?php 
                                    echo $com['created_at']; 
                                    if (isset($com['is_edited']) && $com['is_edited'] == 1) {
                                        echo ' <span style="color:#aaa; font-weight:normal; margin-left:5px;">(已編輯)</span>';
                                    }
                                ?>
                            </small>
                            
                            <?php if ($current_user_id !== null && (int)$current_user_id === (int)$com['u_id']): ?>
                                <div class="user-maintenance-bar">
                                    <button class="btn-user-action btn-user-edit-text" 
                                            id="btn-edit-trigger-<?php echo $com['com_id']; ?>"
                                            onclick="openStudentEditModal(<?php echo $com['com_id']; ?>, <?php echo $com['item_id']; ?>, <?php echo $com['rating']; ?>, '<?php echo htmlspecialchars($com['com_img']); ?>')">
                                        ✏️ 編輯
                                    </button>
                                    <button class="btn-user-action btn-user-delete-text" onclick="studentDeleteComment(<?php echo $com['com_id']; ?>, this)">🗑 刪除</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_current_shop_owner): ?>
                            <div style="margin-top: 12px;">
                                <button 
                                    onclick="toggleReplyBox(<?php echo $com['com_id']; ?>)"
                                    style="
                                        background:#002B5B;
                                        color:white;
                                        border:none;
                                        padding:6px 14px;
                                        border-radius:20px;
                                        cursor:pointer;
                                        font-size:13px;
                                    ">
                                    回覆
                                </button>

                                <div id="reply-box-<?php echo $com['com_id']; ?>" style="display:none; margin-top:10px;">
                                    <form method="POST">
                                        <input type="hidden" name="com_id" value="<?php echo $com['com_id']; ?>">
                                        <textarea 
                                            name="reply_content"
                                            rows="3"
                                            placeholder="輸入回覆內容..."
                                            style="
                                                width:100%;
                                                padding:10px;
                                                border-radius:10px;
                                                border:1px solid #ddd;
                                                resize:none;
                                                box-sizing:border-box;
                                            "
                                            required></textarea>

                                        <button 
                                            type="submit"
                                            name="reply_submit"
                                            style="
                                                margin-top:8px;
                                                background:#FF8C42;
                                                color:white;
                                                border:none;
                                                padding:8px 16px;
                                                border-radius:20px;
                                                cursor:pointer;
                                            ">
                                            送出回覆
                                        </button>
                                    </form>
                                </div>
                            </div>        
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; color:#ccc; padding: 50px 0;">
                <p style="margin-top: 10px;">這家餐廳目前沒有任何餐點評價</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="noMatchMessage" class="hidden" style="text-align:center; color:#ccc; padding: 50px 0;">
        <p style="margin-top: 10px;">找不到符合篩選條件的評價內容喔！</p>
    </div>
</div>

<div id="imageModal" class="modal-overlay" onclick="closeFullImage()">
    <img id="fullImage" src="" alt="放大照片">
</div>

<script>
    function openFullImage(src) {
        document.getElementById('fullImage').src = src;
        document.getElementById('imageModal').style.display = 'flex';
        const toolbar = document.querySelector('footer') || document.querySelector('nav') || document.querySelector('.footer');
        if (toolbar) toolbar.style.display = 'none';
    }

    function closeFullImage() {
        document.getElementById('imageModal').style.display = 'none';
        const toolbar = document.querySelector('footer') || document.querySelector('nav') || document.querySelector('.footer');
        if (toolbar) toolbar.style.display = 'flex';
    }

    
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.location.hash) {
        const targetId = window.location.hash;
        const targetCard = document.querySelector(targetId);
        
        if (targetCard) {
            setTimeout(() => {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                const innerBox = targetCard.querySelector('div[style*="background:white"]');
                if (innerBox) {
                    innerBox.style.transition = "all 0.4s ease";
                    innerBox.style.borderColor = "#FF8C42"; 
                    innerBox.style.boxShadow = "0 0 15px rgba(255,140,66,0.4)"; 
                    
                    setTimeout(() => {
                        innerBox.style.borderColor = "#f0f0f0";
                        innerBox.style.boxShadow = "0 2px 8px rgba(0,0,0,0.05)";
                    }, 2500);
                }
            }, 300);
        }
    }
});
</script>

<?php 
include('comments_filter.php');
include('update_comments.php'); 
include('delete_comments.php'); 
include('manage_comment.php');
include('report_modal.php');
include('footer.php'); 
?>