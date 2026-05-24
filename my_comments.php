<?php
session_start();
include('db.php');
include('header.php'); // 引入手機殼與全域 CSS

// 1. 安全防護：沒登入或不是學生身分 (role_id != 3) 直接踢走
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    echo "<script>alert('請先登入學生帳號喔！'); window.location.href='login.php';</script>";
    exit();
}

$current_user_id = $_SESSION['u_id'];

// 2. 核心 SQL：精準抓取「當前登入者自己」的所有歷史評價，並關聯餐廳與餐點
// 🎯 請檢查最上面的 SQL，確保有加上 r.image_url 喔！
$stmt_comments = $conn->prepare("
    SELECT c.*, i.name as item_name, r.name as res_name, r.r_id, r.image_url
    FROM comments c 
    JOIN items i ON c.item_id = i.item_id 
    JOIN categories cat ON i.c_id = cat.c_id 
    JOIN restaurants r ON cat.r_id = r.r_id
    WHERE c.u_id = ? 
    ORDER BY c.created_at DESC
");
$stmt_comments->bind_param("i", $current_user_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
?>

<style>
    .my-header-section {
        background-color: var(--fujen-blue, #002B5B);
        color: white; 

        padding: 20px 20px 1px; 
    }
    
    .back-btn { 
        text-decoration: none !important; 
        color: white !important; 
        font-size: 14px; 
        display: inline-block; 
        margin-bottom: 20px; 
        font-weight: bold; 
        opacity: 0.9;
        transition: opacity 0.2s;
    }
    .back-btn:hover { opacity: 1; }
    
    .my-header-section h2 { 
        margin: 0; 
        font-size: 24px; 
        color: white; 
    }
    
    .my-header-section p { 
        margin: 5px 0 20px; 
        font-size: 13px; 
        color: white;
        opacity: 0.8; 
    }

    .main-content-wrapper {
        padding: 20px; 
        max-width: 600px; 
        margin: 0 auto; 
        padding-bottom: 100px;
        position: relative;
        z-index: 10;
    }

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
    
    /* 🎯 餐廳連結的基礎樣式 */
    .restaurant-tag-link { 
        display: inline-flex; 
        align-items: center; 
        gap: 10px; 
        text-decoration: none; 
        margin-bottom: 12px;
        transition: transform 0.2s; /* 讓移動時有平滑感 */
    }

    /* 🎯 滑鼠移過去（Hover）時，文字變成亮橘色，且整體輕微往右動一下 */
    .restaurant-tag-link:hover span { 
        color: #FF8C42 !important; 
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
    .search-input-wrapper .search-icon:active { transform: translateY(-50%) scale(0.8); opacity: 0.5; }
    
    .control-search {
        width: 100%;
        padding: 10px 35px 10px 12px; 
        border-radius: 20px;
        border: 1px solid #ddd;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        background: white;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .control-search:focus { border-color: #002B5B; box-shadow: 0 2px 8px rgba(0, 43, 91, 0.1); }

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
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .comment-card-item { position: relative; }
    .hidden { display: none !important; }

.go-modify-btn {
    text-decoration: none; 
    background-color:  #002B5B; 
    color:white;                              
    padding: 8px 16px; 
    border-radius: 20px; 
    font-weight: bold; 
    font-size: 13px; 
    display: inline-flex; 
    align-items: center; 
    gap: 4px; 
    box-shadow: 0 2px 6px rgba(0,43,91,0.15);
    transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    cursor: pointer;
}

.go-modify-btn:hover {    
    transform: scale(1.10);         
}

.go-modify-btn:active {
    transform: scale(0.95);
}


</style>

<div class="my-header-section">
    <a href="comments.php" class="back-btn">
        ❮ 返回社群評價
    </a>
    <h2>我的歷史評論</h2>
    <p>管理你發表過的所有用餐紀錄</p>

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
            <option value="one_month">一個月內</option>
            <option value="one_week">一個禮拜內</option>
        </select>
    </div>
</div>

<div class="main-content-wrapper">
    
    <div id="commentsContainer">
        <?php if ($comments_result->num_rows > 0): ?>
            <?php while($com = $comments_result->fetch_assoc()): ?>
                
                <div class="comment-card-item" 
                     id="comment-card-<?php echo $com['com_id']; ?>"
                     data-rating="<?php echo $com['rating']; ?>"
                     data-time="<?php echo strtotime($com['created_at']); ?>"
                     data-has-image="<?php echo !empty($com['com_img']) ? 'true' : 'false'; ?>"
                     data-text="<?php echo htmlspecialchars(mb_strtolower($com['item_name'] . $com['content'])); ?>">
                    
                    <div style="background:white; padding:15px; border-radius:12px; margin-bottom:15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; position: relative;">
                        
                        <a href="comments_restaurant.php?r_id=<?php echo $com['r_id']; ?>" class="restaurant-tag-link">
                            <img src="images/<?php echo htmlspecialchars($com['image_url'] ?: 'default.jpg'); ?>" 
                                style="width: 32px; height: 32px; object-fit: cover; border: 1px solid #eee;" 
                                alt="店家圖片">
                            
                            <span style="color: #002B5B; font-size: 15px; font-weight: bold; transition: color 0.2s;">
                                <?php echo htmlspecialchars($com['res_name']); ?>
                            </span>
                        </a>
                                                
                        <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom: 4px;">
                            <strong id="comment-item-name-<?php echo $com['com_id']; ?>" style="font-size: 16px; color: #333;"><?php echo htmlspecialchars($com['item_name']); ?></strong>
                            <span style="color:#FF8C42; font-weight: bold;">★ <span id="comment-rating-num-<?php echo $com['com_id']; ?>"><?php echo $com['rating']; ?></span></span>
                        </div>

                        <p id="comment-text-content-<?php echo $com['com_id']; ?>" style="margin:4px 0 12px; color:#444; font-size: 15px; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($com['content'])); ?>
                        </p>

                        <?php if (!empty($com['reply_content'])): ?>
                            <div style="margin-top:12px; background:#f5f7fb; border-left:4px solid #002B5B; padding:10px 12px; border-radius:8px;">
                                <div style="font-size:13px; font-weight:bold; color:#002B5B; margin-bottom:5px;">店家回覆</div>
                                <div style="font-size:14px; color:#444; line-height:1.5;">
                                    <?php echo nl2br(htmlspecialchars($com['reply_content'])); ?>
                                </div>
                                <div style="text-align:right; margin-top:5px;">
                                    <small style="color:#aaa;"><?php echo $com['reply_created_at']; ?></small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="comment-image-block-<?php echo $com['com_id']; ?>" style="margin: 10px 0;">
                            <?php if (!empty($com['com_img'])): ?>
                                <img src="food/<?php echo htmlspecialchars($com['com_img']); ?>" class="comment-img-thumb" onclick="openFullImage(this.src)" alt="用餐照片">
                            <?php endif; ?>
                        </div>

                            <div style="text-align: right; display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                                <small style="color:#bbb; font-size: 11px;">
                                    <?php 
                                        echo $com['created_at']; 
                                        if (isset($com['is_edited']) && $com['is_edited'] == 1) {
                                            echo ' <span style="color:#aaa; font-weight:normal; margin-left:5px;">(已編輯)</span>';
                                        }
                                    ?>
                                </small>
                                
                                <div class="user-maintenance-bar">
                                    <a href="comments_restaurant.php?r_id=<?php echo $com['r_id']; ?>#comment-card-<?php echo $com['com_id']; ?>" class="go-modify-btn">
                                        前往修改此評論 ❯
                                    </a>
                                </div>
                            </div>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; color:#ccc; padding: 50px 0;">
                <p style="margin-top: 10px;">目前沒有任何餐點評價歷史紀錄</p>
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

<?php 
// 引入你原本寫好的篩選、修改與刪除控制組件
include('comments_filter.php');
include('update_comments.php'); 
include('delete_comments.php'); 
include('footer.php'); 
?>