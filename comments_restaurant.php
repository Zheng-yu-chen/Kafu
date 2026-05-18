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

// 2. 撈取屬於該餐廳所有餐點的詳細評論，並顯示餐點名稱
$stmt_comments = $conn->prepare("
    SELECT c.*, i.name as item_name, a.name as user_name, a.user_photo 
    FROM comments c 
    JOIN items i ON c.item_id = i.item_id 
    JOIN categories cat ON i.c_id = cat.c_id 
    LEFT JOIN accounts a ON c.u_id = a.u_id 
    WHERE cat.r_id = ? AND c.status = 1 
    ORDER BY c.created_at DESC
");
$stmt_comments->bind_param("i", $r_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();

$is_admin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
?>

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
        gap: 8px; /* 稍微縮小間距以容納三個元件 */
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
        padding: 10px 35px 10px 12px; 
        border-radius: 20px;
        border: 1px solid #ddd;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .control-search:focus {
        border-color: #002B5B;
        box-shadow: 0 2px 8px rgba(0, 43, 91, 0.1);
    }

    .control-sort, .control-filter-stars {
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
</style>

<div style="padding: 20px; max-width: 600px; margin: 0 auto; padding-bottom: 100px;">
    
    <a href="comments.php" class="back-btn">❮ 返回店家列表</a>
    
    <div style="display: flex; align-items: center; gap: 15px; margin-top: 5px; margin-bottom: 15px;">
        <img src="images/<?php echo htmlspecialchars($res_info['image_url'] ?: 'default.jpg'); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
        <div>
            <h2 style="margin: 0; font-size: 22px; color: #000;"><?php echo htmlspecialchars($res_info['name']); ?></h2>
        </div>
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
                     data-rating="<?php echo $com['rating']; ?>"
                     data-time="<?php echo strtotime($com['created_at']); ?>"
                     data-has-image="<?php echo !empty($com['com_img']) ? 'true' : 'false'; ?>"
                     data-text="<?php echo htmlspecialchars(mb_strtolower($com['item_name'] . $com['content'])); ?>">
                    
                    <div style="background:white; padding:15px; border-radius:12px; margin-bottom:15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; position: relative;">
                        <?php if ($is_admin): ?>
                            <button class="comment-delete-btn" onclick="deleteComment(<?php echo $com['com_id']; ?>, this)" title="刪除評論">🗑</button>
                        <?php endif; ?>
                        
                        <div class="user-tag">
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
                        
                        <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom: 4px;">
                            <strong style="font-size: 16px; color: #333;"><?php echo htmlspecialchars($com['item_name']); ?></strong>
                            <span style="color:#FF8C42; font-weight: bold;">★ <?php echo $com['rating']; ?></span>
                        </div>

                        <p style="margin:4px 0 12px; color:#444; font-size: 15px; line-height: 1.5;">
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

                        <div style="text-align: right;">
                            <small style="color:#bbb; font-size: 11px;"><?php echo $com['created_at']; ?></small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; color:#ccc; padding: 50px 0;">
                <p style="font-size: 40px; margin: 0;"></p>
                <p style="margin-top: 10px;">這家餐廳目前沒有任何餐點評價</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="noMatchMessage" class="hidden" style="text-align:center; color:#ccc; padding: 50px 0;">
        <p style="font-size: 40px; margin: 0;"></p>
        <p style="margin-top: 10px;">找不到符合篩選條件的評價內容喔！</p>
    </div>
</div>

<div id="imageModal" class="modal-overlay" onclick="closeFullImage()">
    <img id="fullImage" src="" alt="放大照片">
</div>

<script>
    function filterAndSortComments() {
        const keyword = document.getElementById('commentSearch').value.toLowerCase().trim();
        const selectedStar = document.getElementById('commentFilterStars').value; 
        const sortMode = document.getElementById('commentSort').value; // 最新、有圖片、一個月、一個禮拜
        const container = document.getElementById('commentsContainer');
        const cards = Array.from(container.getElementsByClassName('comment-card-item'));

        if (cards.length === 0) return;

        // 🎯 1. 取得網頁執行的當前時間戳記，用來計算時間範圍
        const nowTs = Math.floor(Date.now() / 1000);
        const oneWeekSec = 7 * 24 * 60 * 60;   // 7天的總秒數
        const oneMonthSec = 30 * 24 * 60 * 60; // 30天的總秒數

        // 🎯 2. 四重連動過濾（關鍵字 + 星級過濾 + 時間範圍過濾 + 是否帶有圖片）
        cards.forEach(card => {
            const text = card.getAttribute('data-text');
            const rating = card.getAttribute('data-rating');
            const hasImg = card.getAttribute('data-has-image');
            const timeTs = parseInt(card.getAttribute('data-time'), 10); 
            
            // 條件 A：關鍵字比對
            const matchesKeyword = text.includes(keyword);
            
            // 條件 B：星級比對
            const matchesStar = (selectedStar === 'all' || rating === selectedStar);
            
            // 條件 C：時間區段範圍與餐點圖片篩選邏輯組合
            let matchesCondition = true;
            if (sortMode === 'one_week') {
                matchesCondition = (nowTs - timeTs <= oneWeekSec);
            } else if (sortMode === 'one_month') {
                matchesCondition = (nowTs - timeTs <= oneMonthSec);
            } else if (sortMode === 'has_image') {
                // 🎯 如果選中「有餐點圖片」，卡片的屬性必須為 'true' 才能通過
                matchesCondition = (hasImg === 'true');
            }

            // 四個條件必須同時滿足時顯示，否則隱藏
            if (matchesKeyword && matchesStar && matchesCondition) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        // 🎯 3. 排序執行：預設一律採由新到舊排序
        cards.sort((a, b) => {
            return b.getAttribute('data-time') - a.getAttribute('data-time'); 
        });

        cards.forEach(card => container.appendChild(card));

        const hasVisible = cards.some(card => !card.classList.contains('hidden'));
        const noMatchMsg = document.getElementById('noMatchMessage');
        if (hasVisible) {
            noMatchMsg.classList.add('hidden');
        } else {
            noMatchMsg.classList.remove('hidden');
        }
    }

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

    function deleteComment(comId, button) {
        if (!confirm('管理員確定要刪除此評論嗎？此操作無法復原。')) return;

        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('com_id', comId);

        fetch('manage_review_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = button.closest('.comment-card-item');
                if (card) card.remove();
            } else {
                alert('刪除失敗，請稍後再試。');
            }
        })
        .catch(() => {
            alert('網路錯誤，無法刪除評論。');
        });
    }
</script>

<?php include('footer.php'); ?>