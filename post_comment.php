<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>撰寫評價</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-box { padding: 20px; background: white; border-radius: 20px; margin: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        select, textarea, input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .submit-btn { background: #002B5B; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body style="background:#f4f7f9;">

<div class="form-box">
    <h3 style="color:#002B5B; text-align:center;">發表評價</h3>
    <form action="save_comment.php" method="POST">
        <label>選擇您點過的餐點：</label>
        <select name="item_id" required>
            <option value="">-- 請選擇餐點 --</option>
            <option value="1">蜂蜜鬆餅</option>
            <option value="26">白飯</option>
        </select>

        <label>評分 (1-5 星)：</label>
        <input type="number" name="rating" min="1" max="5" value="5" required>

        <label>評論內容：</label>
        <textarea name="content" rows="5" placeholder="說說看這道餐點好不好吃..." required></textarea>

        <button type="submit" class="submit-btn">提交評價 (需審核)</button>
    </form>
</div>

</body>
</html>