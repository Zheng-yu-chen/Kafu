<?php
include('db.php');

/**
 * 根據你的 Schema，評價是跟著「餐點(Items)」跑的
 * 我們需要 JOIN Items 來拿到餐點名，JOIN Restaurants 拿到餐廳名
 */
$sql = "SELECT c.*, i.item_name, r.name as res_name, r.location
        FROM Comments c
        JOIN Items i ON c.item_id = i.item_id
        JOIN Categories cat ON i.c_id = cat.c_id
        JOIN Restaurants r ON cat.r_id = r.r_id
        WHERE c.status = 1 
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>社群評價 - KaFu</title>
    <style>
        :root { --fujen-blue: #002B5B; --primary-orange: #FF8C42; --bg-gray: #f8f9fa; }
        body { background-color: var(--bg-gray); font-family: 'PingFang TC', sans-serif; margin: 0; padding-bottom: 80px; }

        /* --- 頂部標題與搜尋區 --- */
        .header-section {
            background-color: var(--fujen-blue);
            color: white;
            padding: 30px 20px 20px;
        }
        .header-title { text-align: left; margin-bottom: 15px; }
        .header-title h1 { margin: 0; font-size: 24px; }
        .header-title p { margin: 5px 0 0; font-size: 13px; opacity: 0.8; }

        /* 搜尋框設計 */
        .search-container { position: relative; margin-bottom: 15px; }
        .search-input {
            width: 100%; padding: 12px 15px 12px 40px;
            border-radius: 25px; border: none;
            font-size: 14px; outline: none;
        }
        .search-icon { position: absolute; left: 15px; top: 12px; color: #999; }

        /* 分類按鈕 */
        .filter-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
        .filter-tag {
            background: rgba(255,255,255,0.2); color: white;
            padding: 6px 18px; border-radius: 20px;
            font-size: 13px; white-space: nowrap; cursor: pointer;
            border: 1px solid transparent;
        }
        .filter-tag.active { background: white; color: var(--fujen-blue); font-weight: bold; }

        /* --- 分享按鈕 --- */
        .share-banner {
            background: var(--primary-orange); color: white;
            margin: 15px; padding: 15px; border-radius: 12px;
            display: flex; justify-content: center; align-items: center;
            text-decoration: none; font-weight: bold; gap: 10px;
            box-shadow: 0 4px 10px rgba(255,140,66,0.3);
        }

        /* --- 評價列表 --- */
        .comment-list { padding: 0 15px; }
        .comment-card {
            background: white; border-radius: 15px; padding: 15px;
            margin-bottom: 15px; display: flex; align-items: center;
            text-decoration: none; color: inherit;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
        }
        .item-img {
            width: 60px; height: 60px; border-radius: 10px;
            background: #eee; margin-right: 15px; object-fit: cover;
        }
        .comment-info { flex: 1; }
        .comment-info h3 { margin: 0; font-size: 16px; color: #333; }
        .comment-info p { margin: 4px 0; font-size: 12px; color: #888; }
        .rating-row { display: flex; align-items: center; gap: 5px; font-size: 13px; }
        .star { color: var(--primary-orange); font-weight: bold; }
        .arrow { color: #ccc; margin-left: 10px; }

        /* --- 隱藏不符合搜尋的卡片 --- */
        .hidden { display: none; }
    </style>
</head>
<body>

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

<a href="post_comment.php" class="share-banner">
    <span>📷</span> 分享我的用餐體驗
</a>

<div class="comment-list" id="commentList">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <a href="comment_detail.php?com_id=<?php echo $row['com_id']; ?>" 
               class="comment-card" 
               data-name="<?php echo htmlspecialchars($row['item_name'] . $row['res_name']); ?>"
               data-loc="<?php echo $row['location']; ?>">
                
                <img src="https://via.placeholder.com/60" class="item-img" alt="餐點圖">
                
                <div class="comment-info">
                    <h3><?php echo htmlspecialchars($row['item_name']); ?></h3>
                    <p><?php echo htmlspecialchars($row['res_name']); ?> • <?php echo htmlspecialchars($row['location']); ?></p>
                    <div class="rating-row">
                        <span class="star">★ <?php echo number_format($row['rating'], 1); ?></span>
                        <span style="color:#bbb;">(<?php echo rand(10, 50); ?>)</span> <span style="margin-left:5px;">💬 <?php echo rand(1, 5); ?></span>
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
    // 搜尋功能邏輯
    function filterComments() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let cards = document.getElementsByClassName('comment-card');

        for (let card of cards) {
            let name = card.getAttribute('data-name').toLowerCase();
            if (name.includes(input)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        }
    }

    // 位置分類功能邏輯
    function filterByLocation(loc, btn) {
        // 切換按鈕樣式
        document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        let cards = document.getElementsByClassName('comment-card');
        for (let card of cards) {
            let cardLoc = card.getAttribute('data-loc');
            if (loc === '全部' || cardLoc === loc) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        }
    }
</script>
<?php include('footer.php'); ?>
</body>
</html>