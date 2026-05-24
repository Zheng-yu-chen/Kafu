<?php
session_start();
include('db.php');
include('header.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$sql = "SELECT 
            r.r_id, 
            r.name AS res_name, 
            r.location,
            r.image_url,
            AVG(c.rating) AS avg_rating,
            COUNT(c.com_id) AS total_comments
        FROM restaurants r
        LEFT JOIN categories cat ON r.r_id = cat.r_id
        LEFT JOIN items i ON cat.c_id = i.c_id
        LEFT JOIN comments c ON i.item_id = c.item_id
        GROUP BY r.r_id, r.name, r.location, r.image_url
        ORDER BY r.r_id ASC";

try {
    $result = $conn->query($sql);
} catch (mysqli_sql_exception $e) {
    die("評價頁面載入失敗！原因：" . $e->getMessage());
}
?>

<style>
    .header-section {
        background-color: var(--fujen-blue, #002B5B);
        color: white; 
        padding: 30px 20px 8px; 
    }
    .header-top-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        width: 100%;
    }
    .header-title { text-align: left; }
    .header-title h1 { margin: 0; font-size: 24px; }
    .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }

 
.history-btn-top {
    text-decoration: none !important;
    background-color: #FF8C42; 
    color: white !important;
    padding: 8px 18px; 
    border-radius: 50px; 
    font-size: 13px; 
    font-weight: bold; 
    display: inline-flex; 
    align-items: center; 
    gap: 5px; 
    box-shadow: 0 2px 6px rgba(255,140,66,0.2); 
    transition: background-color 0.2s, transform 0.1s;
    flex-shrink: 0;
    margin-top: 0px; 
}
    .history-btn-top:hover { 
        background-color: #E67E38; 
    }

    .history-btn-top:active { 
        transform: scale(0.90); 
    }

    .search-container { position: relative; margin-bottom: 25px; }
    
    .search-input { 
        width: 100%; 
        padding: 12px 40px 12px 15px; 
        border-radius: 25px; 
        border: none; 
        font-size: 14px; 
        outline: none; 
        box-sizing: border-box; 
        transition: box-shadow 0.2s;
    }

    .search-input:focus {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    .search-icon { 
        position: absolute; 
        right: 15px; 
        top: 50%; 
        transform: translateY(-50%); 
        width: 24px;
        height: 24px;
        object-fit: contain;
        opacity: 0.6;
        cursor: pointer;
        transition: transform 0.1s ease, opacity 0.2s;
    }
    .search-icon:hover { opacity: 1; }
    .search-icon:active {
        transform: translateY(-50%) scale(0.8);
        opacity: 0.5;
    }

    .filter-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
    .filter-row::-webkit-scrollbar { display: none; } 
    .filter-tag { background: rgba(255,255,255,0.2); color: white; padding: 6px 18px; border-radius: 20px; font-size: 13px; white-space: nowrap; cursor: pointer; border: 1px solid transparent; transition: 0.2s; }
    .filter-tag.active { background: white; color: var(--fujen-blue, #002B5B); font-weight: bold; }


    .comment-list { 
        padding: 0 20px; 
        position: relative;
        z-index: 5;
        margin-top: 15px; 
    }
    
    .comment-card { background: white; border-radius: 15px; padding: 15px; margin-bottom: 15px; display: flex; align-items: center; text-decoration: none; color: inherit; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .res-img-thumb { width: 60px; height: 60px; border-radius: 10px; background: #f4f7f9; margin-right: 15px; object-fit: cover; flex-shrink: 0; }
    .comment-info { flex: 1; overflow: hidden; }
    .comment-info h3 { margin: 0; font-size: 16px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .comment-info p { margin: 4px 0 8px; font-size: 12px; color: #888; }
    .rating-row { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .star { color: var(--primary-orange, #FF8C42); font-weight: bold; }
    .arrow { color: #ccc; margin-left: 10px; font-weight: bold; font-size: 18px; }
    .hidden { display: none !important; }
</style>

<div class="header-section">
    <div class="header-top-row">
        <div class="header-title">
            <h1>社群評價</h1>
            <p>挑選想查看評價的輔大校園美食店家</p>
        </div>
        
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
            <a href="my_comments.php" class="history-btn-top">
                <span></span>歷史評論
            </a>
        <?php endif; ?>
    </div>

    <div class="search-container">
        <img src="icon/search_icon.png" class="search-icon" alt="搜尋" onclick="filterComments()">
        <input type="text" id="searchInput" class="search-input" placeholder="搜尋店家名稱..." onkeydown="if(event.key === 'Enter') filterComments()">
    </div>

    <div class="filter-row">
        <div class="filter-tag active" onclick="filterByLocation('全部', this)">全部</div>
        <div class="filter-tag" onclick="filterByLocation('心園', this)">心園</div>
        <div class="filter-tag" onclick="filterByLocation('理園', this)">理園</div>
        <div class="filter-tag" onclick="filterByLocation('輔園', this)">輔園</div>
    </div>
</div>

<div class="comment-list" id="commentList">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <a href="comments_restaurant.php?r_id=<?php echo $row['r_id']; ?>" 
               class="comment-card" 
               data-name="<?php echo htmlspecialchars($row['res_name']); ?>"
               data-loc="<?php echo $row['location']; ?>">
                
                <img src="images/<?php echo htmlspecialchars($row['image_url'] ?: 'default.jpg'); ?>" class="res-img-thumb" alt="店家圖片">
                
                <div class="comment-info">
                    <h3><?php echo htmlspecialchars($row['res_name']); ?></h3>
                    <p>📍<?php echo htmlspecialchars($row['location']); ?></p>
                    <div class="rating-row">
                        <span class="star">★ <?php echo $row['avg_rating'] ? number_format($row['avg_rating'], 1) : '0.0'; ?></span>
                        <span style="color:#888;">💬<?php echo $row['total_comments']; ?> </span> 
                    </div>
                </div>
                <div class="arrow">❯</div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color:#ccc; margin-top:50px;">目前尚無評價資料</p>
    <?php endif; ?>
</div>

<script>
    function filterComments() {
        let input = document.getElementById('searchInput').value.toLowerCase().trim();
        let cards = document.getElementsByClassName('comment-card');
        for (let card of cards) {
            let name = card.getAttribute('data-name').toLowerCase();
            if (name.includes(input)) card.classList.remove('hidden');
            else card.classList.add('hidden');
        }
    }
    
    function filterByLocation(loc, btn) {
        document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        let cards = document.getElementsByClassName('comment-card');
        for (let card of cards) {
            let cardLoc = card.getAttribute('data-loc');
            if (loc === '全部' || cardLoc === loc) card.classList.remove('hidden');
            else card.classList.add('hidden');
        }
    }
</script>

<?php include('footer.php'); ?>