<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('db.php');
include('header.php'); // 開啟手機殼與全域 CSS

// 安全防護：只有一般用戶 (role_id 3) 可以進入評價頁面
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    echo "<script>alert('無權限訪問！'); window.location.href='login.php';</script>";
    exit();
}


$r_id = isset($_GET['r_id']) ? intval($_GET['r_id']) : 0;

// 🎯 新增：撈取當前餐廳的名稱
$restaurant_name = "未知店家";
$stmt_res = $conn->prepare("SELECT name FROM restaurants WHERE r_id = ?");
$stmt_res->bind_param("i", $r_id);
$stmt_res->execute();
$res_result = $stmt_res->get_result()->fetch_assoc();
if ($res_result) {
    $restaurant_name = $res_result['name'];
}

// 撈取當前餐廳的所有餐點
$items = [];
$stmt_items = $conn->prepare("
    SELECT i.item_id, i.name AS item_name 
    FROM items i 
    JOIN categories c ON i.c_id = c.c_id
    WHERE c.r_id = ?
");
$stmt_items->bind_param("i", $r_id);
$stmt_items->execute();
$item_result = $stmt_items->get_result();
if ($item_result) {
    while($i = $item_result->fetch_assoc()) {
        $items[] = $i;
    }
}
?>

<style>
    /* 配合導覽列調整背景與間距 */
    body { background-color: #f4f7f9; }
    
    .modal-header {
        background-color:  #002B5B;
        color: white; padding: 20px;
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 100;
    }
    .modal-header h2 { margin: 0; font-size: 18px; letter-spacing: 1px; }
    .close-btn { color: white; text-decoration: none; font-size: 26px; line-height: 1; opacity: 0.9; }

    /* 💡 修正：增加底部內距 (80px)，確保不會被導覽列遮擋 */
    .form-content { padding: 25px 20px 80px; background: white; min-height: calc(100vh - 140px); }
    
    .form-group { margin-bottom: 25px; }
    .form-group label { display: block; font-size: 13px; color: #555; margin-bottom: 10px; font-weight: bold; }
    
    .custom-select, .custom-textarea {
        width: 100%; padding: 14px 15px; border: 1px solid #e0e0e0;
        border-radius: 10px; font-size: 15px; color: #333;
        background-color: #fafafa; box-sizing: border-box;
        appearance: none; outline: none; transition: 0.2s;
    }
    .custom-select:focus, .custom-textarea:focus { border-color: var(--primary-orange, #FF8C42); background: white; }
    
    .select-wrapper { position: relative; }
    .select-wrapper::after {
        content: '⌄'; position: absolute; right: 15px; top: 50%;
        transform: translateY(-60%); font-size: 20px; color: #888; pointer-events: none;
    }

    .rating-container { text-align: center; margin: 10px 0; }
    .stars { display: flex; justify-content: center; gap: 8px; flex-direction: row-reverse; }
    .star { font-size: 35px; color: #e0e0e0; cursor: pointer; transition: 0.2s; }
    .star:hover, .star:hover ~ .star, .star.active, .star.active ~ .star { color: #FFC107; }
    .rating-hint { font-size: 12px; color: #999; margin-top: 8px; }

    .upload-box {
        border: 2px dashed #d0d0d0; border-radius: 12px; padding: 30px 20px;
        text-align: center; cursor: pointer; transition: 0.2s; background: #fafafa;
    }
    .upload-icon { font-size: 28px; color: #aaa; margin-bottom: 8px; }
    .upload-text { font-size: 14px; color: #666; font-weight: bold; margin-bottom: 4px; }
    #fileInput { display: none; }

    .submit-btn {
        width: 100%; background-color: #F8C471; 
        color: white; border: none; padding: 16px; border-radius: 10px;
        font-size: 16px; font-weight: bold; cursor: pointer;
        margin-top: 10px; box-shadow: 0 4px 10px rgba(248,196,113,0.3);
    }
    .submit-btn.active { background-color: var(--primary-orange, #FF8C42); }
    .submit-hint { text-align: center; font-size: 11px; color: #aaa; margin-top: 15px; }
</style>

<div class="modal-header">
    <div style="display: flex; align-items: baseline; gap: 10px;">
        <h2>分享用餐體驗</h2>
        <span style="font-size: 14px; font-weight: normal;">
            (<?php echo htmlspecialchars($restaurant_name); ?>)
        </span>
    </div>
    <a href="comments_restaurant.php?r_id=<?php echo $r_id; ?>" class="close-btn">×</a>
</div>

<div class="form-content">
    <form action="save_comment.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="r_id" value="<?php echo $r_id; ?>">

        <div class="form-group">
            <label>選擇餐點</label>
            <div class="select-wrapper">
                <select name="item_id" id="itemSelect" class="custom-select" required>
                    <option value="" disabled selected>請選擇餐點</option>
                    <?php foreach($items as $item): ?>
                        <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>滿意度評分</label>
            <div class="rating-container">
                <input type="hidden" name="rating" id="ratingInput" value="0" required>
                <div class="stars">
                    <span class="star" data-value="5">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="1">★</span>
                </div>
                <div class="rating-hint" id="ratingHint">請選擇評分</div>
            </div>
        </div>

        <div class="form-group">
            <label>文字評論</label>
            <textarea name="content" class="custom-textarea" rows="4" placeholder="分享你的用餐心得..." required></textarea>
        </div>

        <div class="form-group">
            <label>上傳餐點照片 (選填)</label>
            <div class="upload-box" onclick="document.getElementById('fileInput').click();">
                <div class="upload-icon">↑</div>
                <div class="upload-text" id="uploadText">點擊上傳照片</div>
            </div>
            <input type="file" name="photo" id="fileInput" accept="image/jpeg, image/png" onchange="updateFileName()">
        </div>

        <button type="submit" class="submit-btn" id="submitBtn">提交評價</button>
    </form>
</div>

<script>
    // 🎯 原先落落長的 JS 店家過濾陣列與邏輯全部移除，讓效能變超輕量！

    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingInput');
    const ratingHint = document.getElementById('ratingHint');
    const submitBtn = document.getElementById('submitBtn');
    const hints = ["非常不滿意", "不滿意", "普通", "滿意", "非常滿意！"];

    stars.forEach(star => {
        star.addEventListener('click', function() {
            stars.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            let val = this.getAttribute('data-value');
            ratingInput.value = val;
            ratingHint.innerText = val + " 星 - " + hints[val-1];
            ratingHint.style.color = "#FF8C42";
            submitBtn.classList.add('active');
        });
    });

    function updateFileName() {
        const input = document.getElementById('fileInput');
        const uploadText = document.getElementById('uploadText');
        if (input.files && input.files.length > 0) {
            uploadText.innerText = "已選擇: " + input.files[0].name;
        }
    }
</script>

<?php 
// 💡 確保導覽列正常顯示
include('footer.php'); 
?>