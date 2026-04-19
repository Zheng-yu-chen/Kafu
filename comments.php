<?php
session_start();
include('db.php');
include('header.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$sql = "SELECT 
            i.item_id, 
            i.item_name, 
            r.name AS res_name, 
            r.location,
            AVG(c.rating) AS avg_rating,
            COUNT(c.com_id) AS total_comments
        FROM comments c
        JOIN items i ON c.item_id = i.item_id
        JOIN categories cat ON i.c_id = cat.c_id
        JOIN restaurants r ON cat.r_id = r.r_id
        WHERE c.status = 1 
        GROUP BY i.item_id, i.item_name, r.name, r.location
        ORDER BY avg_rating DESC, total_comments DESC";

try {
    $result = $conn->query($sql);
} catch (mysqli_sql_exception $e) {
    die("評價頁面載入失敗！原因：" . $e->getMessage());
}
?>

<style>
    .header-section {
        background-color: var(--fujen-blue, #002B5B);
        color: white; padding: 30px 20px 20px;
    }
    .header-title { text-align: left; margin-bottom: 15px; }
    .header-title h1 { margin: 0; font-size: 24px; }
    .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }

    .search-container { position: relative; margin-bottom: 15px; }
    .search-input { width: 100%; padding: 12px 15px 12px 40px; border-radius: 25px; border: none; font-size: 14px; outline: none; box-sizing: border-box; }
    .search-icon { position: absolute; left: 15px; top: 12px; color: #999; }

    .filter-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
    .filter-row::-webkit-scrollbar { display: none; } 
    .filter-tag { background: rgba(255,255,255,0.2); color: white; padding: 6px 18px; border-radius: 20px; font-size: 13px; white-space: nowrap; cursor: pointer; border: 1px solid transparent; transition: 0.2s; }
    .filter-tag.active { background: white; color: var(--fujen-blue, #002B5B); font-weight: bold; }

    .share-banner { background: var(--primary-orange, #FF8C42); color: white; margin: 15px 20px; padding: 15px; border-radius: 12px; display: flex; justify-content: center; align-items: center; text-decoration: none; font-weight: bold; gap: 10px; box-shadow: 0 4px 10px rgba(255,140,66,0.3); }

    .comment-list { padding: 0 20px; }
    .comment-card { background: white; border-radius: 15px; padding: 15px; margin-bottom: 15px; display: flex; align-items: center; text-decoration: none; color: inherit; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .item-img { width: 60px; height: 60px; border-radius: 10px; background: #f4f7f9; margin-right: 15px; display: flex; justify-content: center; align-items: center; font-size: 28px; flex-shrink: 0; }
    .comment-info { flex: 1; overflow: hidden; }
    .comment-info h3 { margin: 0; font-size: 16px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .comment-info p { margin: 4px 0 8px; font-size: 12px; color: #888; }
    .rating-row { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .star { color: var(--primary-orange, #FF8C42); font-weight: bold; }
    .count-text { color: #bbb; }
    .arrow { color: #ccc; margin-left: 10px; font-weight: bold; font-size: 18px; }
    .hidden { display: none !important; }
</style>

<div class="header-section">
    <div class="header-title">
        <h1>社群評價</h1>
        <p>品項導向評價系統</p>
    </div>

    <div class="search-container">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" class="search-input" placeholder="搜尋餐點或店家..." onkeyup="filterComments()">
    </div>

    <div class="filter-row">
        <div class="filter-tag active" onclick="filterByLocation('全部', this)">全部</div>
        <div class="filter-tag" onclick="filterByLocation('心園', this)">心園</div>
        <div class="filter-tag" onclick="filterByLocation('理園', this)">理園</div>
        <div class="filter-tag" onclick="filterByLocation('輔園', this)">輔園</div>
    </div>
</div>

<?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
<a href="post_comment.php" class="share-banner">
    <span>📷</span> 分享我的用餐體驗
</a>
<?php endif; ?>

<div class="comment-list" id="commentList">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <a href="comment_detail.php?item_id=<?php echo $row['item_id']; ?>" 
               class="comment-card" 
               data-name="<?php echo htmlspecialchars($row['item_name'] . $row['res_name']); ?>"
               data-loc="<?php echo $row['location']; ?>">
                
                <div class="item-img">🍲</div>
                
                <div class="comment-info">
                    <h3><?php echo htmlspecialchars($row['item_name']); ?></h3>
                    <p><?php echo htmlspecialchars($row['res_name']); ?> • <?php echo htmlspecialchars($row['location']); ?></p>
                    <div class="rating-row">
                        <span class="star">★ <?php echo number_format($row['avg_rating'], 1); ?></span>
                        <span class="count-text">(<?php echo $row['total_comments']; ?>)</span> 
                        <span style="color:#888;">💬 <?php echo $row['total_comments']; ?></span>
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
        let input = document.getElementById('searchInput').value.toLowerCase();
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