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
        width: 22px;
        height: 22px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #ddd;
    }

    .user-avatar-placeholder {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background-color: #e9ecef; 
        border: 1px solid #ced4da; 
        display: inline-block;
        box-sizing: border-box;
    }

    .filter-control-bar {
        display: flex;
        gap: 10px;
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
        width: 16px;
        height: 16px;
        object-fit: contain;
        opacity: 0.6;
        cursor: pointer; 
        transition: transform 0.1s ease, opacity 0.2s;
    }
    .search-input-wrapper .search-icon:hover { opacity: 1; }
    
    /* 💡 唯獨保留：右側搜尋放大鏡點擊時的下壓縮小動態效果 */
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
    
    /* 💡 唯獨保留：搜尋框點擊準備打字時的外框亮起效果 */
    .control-search:focus {
        border-color: #002B5B;
        box-shadow: 0 2px 8px rgba(0, 43, 91, 0.1);
    }

    /* 💡 旁邊那個：調回純靜態樣式，點擊不會再縮小 */
    .control-sort {
        padding: 0 15px;
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
            <input type="text" id="commentSearch" class="control-search" placeholder="搜尋餐點名稱或評論關鍵字..." onkeydown="if(event.key === 'Enter') filterAndSortComments()">
        </div>
        <select id="commentSort" class="control-sort" onchange="filterAndSortComments()">
            <option value="latest">⏰︎ 最新評論</option>
            <option value="rating_desc">★ 評分：高到低</option>
            <option value="rating_asc">★ 評分：低到高</option>
        </select>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0 20px;">

    <div id="commentsContainer">
        <?php if ($comments_result->num_rows > 0): ?>
            <?php while($com = $comments_result->fetch_assoc()): ?>
                
                <div class="comment-card-item" 
                     data-rating="<?php echo $com['rating']; ?>"
                     data-time="<?php echo strtotime($com['created_at']); ?>"
                     data-text="<?php echo htmlspecialchars(mb_strtolower($com['item_name'] . $com['content'])); ?>">
                    
                    <div style="background:white; padding:15px; border-radius:12px; margin-bottom:15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0f0f0;">
                        <div class="user-tag">
                            <?php if (!empty($com['user_photo'])): ?>
                                <img src="images/<?php echo htmlspecialchars($com['user_photo']); ?>" class="user-avatar" alt="頭像">
                            <?php else: ?>
                                <div class="user-avatar-placeholder"></div>
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
                <p style="font-size: 40px; margin: 0;">💬</p>
                <p style="margin-top: 10px;">這家餐廳目前還沒有收到任何餐點評價</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="noMatchMessage" class="hidden" style="text-align:center; color:#ccc; padding: 50px 0;">
        <p style="font-size: 40px; margin: 0;">🔍</p>
        <p style="margin-top: 10px;">找不到符合關鍵字的評價內容喔！</p>
    </div>
</div>

<div id="imageModal" class="modal-overlay" onclick="closeFullImage()">
    <img id="fullImage" src="" alt="放大照片">
</div>

<script>
    function filterAndSortComments() {
        const keyword = document.getElementById('commentSearch').value.toLowerCase().trim();
        const sortMode = document.getElementById('commentSort').value;
        const container = document.getElementById('commentsContainer');
        const cards = Array.from(container.getElementsByClassName('comment-card-item'));

        if (cards.length === 0) return;

        // Step 1: 關鍵字過濾
        cards.forEach(card => {
            const text = card.getAttribute('data-text');
            if (text.includes(keyword)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        // Step 2: 星級與時間排序
        cards.sort((a, b) => {
            if (sortMode === 'latest') {
                return b.getAttribute('data-time') - a.getAttribute('data-time'); 
            } else if (sortMode === 'rating_desc') {
                return b.getAttribute('data-rating') - a.getAttribute('data-rating'); 
            } else if (sortMode === 'rating_asc') {
                return a.getAttribute('data-rating') - b.getAttribute('data-rating'); 
            }
            return 0;
        });

        // Step 3: 重新掛載 DOM 節點
        cards.forEach(card => container.appendChild(card));

        // Step 4: 無結果提示
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
</script>

<?php include('footer.php'); ?>