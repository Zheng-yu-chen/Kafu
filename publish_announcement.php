<?php
// publish_announcement.php
require_once 'header.php';
require_once 'db.php'; // 引入你的資料庫連線設定

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $content);
        
        if ($stmt->execute()) {
            echo "<script>alert('公告發布成功！'); location.href='store_profile.php';</script>";
        } else {
            echo "發布失敗：" . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>發布新公告</title>
    <!-- 引入 Font Awesome 確保圖示質感 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* 繼承你原本 store_profile.php 的整體風格 */
        body { 
            background-color: #f4f7f9; 
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding-bottom: 40px; 
        }

        /* 頂部綠色儀表板風格 */
        .announcement-header {
            background: linear-gradient(135deg, #4CAF50, #388E3C);
            color: white; 
            padding: 40px 20px 60px;
            position: relative;
            text-align: center;
        }
        .announcement-header h1 { 
            margin: 0; 
            font-size: 24px; 
            letter-spacing: 1px; 
        }
        .announcement-header p { 
            margin: 5px 0 0; 
            font-size: 14px; 
            opacity: 0.9; 
        }

        /* 白底高質感卡片區塊 */
        .form-container {
            background: #ffffff;
            border-radius: 12px;
            max-width: 500px;
            margin: -30px auto 25px; /* 往上縮進去綠色區塊，營造層次感 */
            padding: 30px 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: relative; 
            z-index: 10;
            box-sizing: border-box;
        }

        /* 表單輸入欄位優化 */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #edf2f7;
            background-color: #f8fafc;
            border-radius: 8px;
            font-size: 14px;
            color: #2d3748;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #4CAF50;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        /* 按鈕樣式組合 */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 30px;
        }
        
        /* 綠色確認發布按鈕 */
        .btn-submit {
            background: linear-gradient(135deg, #4CAF50, #388E3C);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 25px; /* 呼應你的登出按鈕圓角 */
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.2);
        }
        .btn-submit:active {
            opacity: 0.9;
        }

        /* 返回按鈕（模仿你的紅框登出按鈕，但改成溫和的灰色調） */
        .btn-cancel {
            display: block;
            text-align: center;
            background-color: white;
            color: #666666;
            border: 1.5px solid #e2e8f0;
            padding: 10px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }
        .btn-cancel:hover {
            background-color: #f7fafc;
            color: #333333;
        }
    </style>
</head>
<body>

    <!-- 頂部綠色標題列 -->
    <div class="announcement-header">
        <h1><i class="fa-solid fa-bullhorn"></i> 店家公告系統</h1>
        <p>在這裡發布的訊息將會即時顯示給所有顧客</p>
    </div>

    <!-- 主要輸入表單卡片 -->
    <div class="form-container">
        <form action="publish_announcement.php" method="POST">
            
            <div class="form-group">
                <label for="title">公告標題</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="請輸入公告標題... (例如：端午節店休通知)" required>
            </div>
            
            <div class="form-group">
                <label for="content">公告詳細內容</label>
                <textarea id="content" name="content" class="form-control" rows="6" placeholder="請詳細說明公告內容..." required></textarea>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-submit">確認發布</button>
                <a href="store_profile.php" class="btn-cancel">返回商家檔案</a>
            </div>

        </form>
    </div>

</body>
</html>