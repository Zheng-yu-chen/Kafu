<?php
// publish_announcement.php
session_start();
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
            echo "<script>alert('發布失敗：" . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

// 引入共同頂部 (已包含 head 標籤與 Font Awesome)
require_once 'header.php';
?>

<style>
    /* 🌟 終極消除頂部留白：強制歸零這個頁面的所有上方邊距與內距 */
    body, html { 
        background-color: #f4f7f9; 
        margin-top: 0 !important; 
        padding-top: 0 !important;
    }

    /* 強制清除 header.php 中 wrapper 預設的 Padding */
    .mobile-wrapper {
        padding-top: 0 !important;
        margin-top: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* 🌟 頂部系統主體深藍色風格 */
    .announcement-header {
        background: linear-gradient(135deg, #002B5B, #001f42);
        color: white; 
        padding: 10vh 20px 12vh; 
        position: relative;
        text-align: center;
        margin: 0; /* 拿掉負邊距，因為 wrapper 已經沒有 padding 了 */
        width: 100%;
        box-sizing: border-box;
    }
    
    .announcement-header h1 { 
        margin: 0; 
        font-size: 24px; 
        letter-spacing: 1px; 
    }
    .announcement-header p { 
        margin: 8px 0 0; 
        font-size: 14px; 
        opacity: 0.9; 
    }
    
    .btn-back {
        position: absolute;
        top: 25px; /* 因為貼頂了，所以按鈕稍微往下移一點 */
        left: 20px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: white;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(255,255,255,0.15);
        transition: background 0.2s ease;
    }
    .btn-back:hover {
        background: rgba(255,255,255,0.25);
    }

    /* 🌟 白底高質感卡片區塊 */
    .form-container {
        background: #ffffff;
        border-radius: 16px;
        width: 90%;
        max-width: 420px;
        margin: -7vh auto 40px; /* 負邊距讓卡片往上吃到藍色區塊 */
        padding: 35px 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        position: relative; 
        z-index: 10;
        box-sizing: border-box;
    }

    /* 表單輸入欄位優化 */
    .form-group {
        margin-bottom: 22px;
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
        padding: 14px;
        border: 1px solid #edf2f7;
        background-color: #f8fafc;
        border-radius: 10px;
        font-size: 15px;
        color: #2d3748;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }
    .form-control:focus {
        outline: none;
        border-color: #002B5B;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(0, 43, 91, 0.15);
    }
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
        line-height: 1.5;
    }

    /* 按鈕樣式組合 */
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 10px;
    }
    
    /* 系統主體深藍確認發布按鈕 */
    .btn-submit {
        background: linear-gradient(135deg, #002B5B, #001f42);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 6px 15px rgba(0, 43, 91, 0.2);
    }
    .btn-submit:active {
        transform: translateY(2px);
        box-shadow: 0 2px 8px rgba(0, 43, 91, 0.2);
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="announcement-header">
    <a href="store_profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> 返回</a>
    <h1><i class="fa-solid fa-bullhorn"></i> 店家公告系統</h1>
    <p>在這裡發布的訊息將會即時顯示給所有顧客</p>
</div>

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
        </div>

    </form>
</div>

<?php 
// 引入共同底部
include('footer.php'); 
?>