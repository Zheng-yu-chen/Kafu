<?php
// ==========================================
// 1. 後端 PHP 邏輯 (這部分使用者看不到)
// ==========================================
// 這個區塊用來處理按下按鈕後的資料

// 檢查是否有收到 POST 請求，且 identity 欄位有值
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['identity'])) {
    
    // 抓到使用者選定的身份
    $chosen_identity = $_POST['identity'];
    
    // 這裡是一個「關聯」的範例：
    // 根據選定的身份，重定向到不同登入頁面
    if ($chosen_identity == 'user') {
        // 使用者
        header("Location: user_login.php");
        exit();
    } elseif ($chosen_identity == 'store') {
        // 店家
        header("Location: store_login.php");
        exit();
    } elseif ($chosen_identity == 'admin') {
        // 管理員
        header("Location: admin_login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaFu - 輔大學餐熱量計算機</title>
    
    <style>
        /* 基本重設 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'PingFang TC', 'Heiti TC', 'Noto Sans TC', sans-serif;
        }

        /* 全螢幕深藍色背景 */
        body {
            background-color: #002B5B; /* 輔大深藍色 */
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            /* 針對手機比例的優化：內容置中垂直堆疊 */
        }

        /* -------------------
           Logo 和標題區塊
           ------------------- */
        .header-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-box {
            background-color: white;
            border-radius: 12px;
            padding: 15px 30px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .logo-box h1 {
            color: black;
            font-size: 2.2rem;
            margin-bottom: 5px;
        }

        .logo-box p {
            color: black;
            font-size: 0.9rem;
        }

        .main-title {
            font-size: 1.1rem;
            font-weight: normal;
        }

        /* -------------------
           身分卡片區域 (表單)
           這部分已經設計為在手機上垂直排列
           ------------------- */
        .identity-form {
            display: flex;
            flex-direction: column; /* 手機上垂直堆疊 */
            gap: 15px; /* 卡片間距 */
            width: 100%;
            max-width: 400px; /* 限制手機比例的寬度 */
            margin-bottom: 30px;
        }

        /* 關鍵：將卡片作為一個 `<button>` 來實現關聯 */
        .identity-card {
            background-color: white;
            color: black;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
            border: none; /* 消除按鈕默認邊框 */
            cursor: pointer; /* 顯示為可點擊 */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        /* 點擊時的互動效果 */
        .identity-card:hover, .identity-card:focus {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
            outline: none; /* 消除聚焦時的藍色框 */
        }

        /* 卡片內的圖標 */
        .icon-container {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        /* 使用者圖標 (灰色) */
        .user-icon {
            background-color: #E8EBF0;
            color: #4A5568;
        }
        /* 店家圖標 (綠色) */
        .store-icon {
            background-color: #E6F6F0;
            color: #2D9F75;
        }
        /* 管理員圖標 (橙色) */
        .admin-icon {
            background-color: #FFF6EA;
            color: #F69D3C;
        }

        /* 卡片內的主標題 (如 使用者) */
        .identity-card h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        /* 卡片內的副標題 (如 學生 / 教職員登入) */
        .identity-card p {
            font-size: 0.8rem;
            color: #4A5568;
        }

        /* -------------------
           訪客連結和頁尾
           ------------------- */
        .visitor-link {
            color: white;
            text-decoration: underline;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .footer-container {
            font-size: 0.8rem;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="logo-box">
            <h1>KaFu</h1>
            <p>輔大學餐熱量計算機</p>
        </div>
        <h2 class="main-title">請選擇您的身份</h2>
    </div>

    <form class="identity-form" method="POST" action="kafu_login.php">
        
        <button type="submit" name="identity" value="user" class="identity-card user-card">
            <div class="icon-container user-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            </div>
            <h3>使用者</h3>
            <p>學生 / 教職員登入</p>
        </button>

        <button type="submit" name="identity" value="store" class="identity-card store-card">
            <div class="icon-container store-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20.16 7.65l-1.42-3.8c-.33-.88-1.13-1.43-2.07-1.43H7.33c-.94 0-1.74.55-2.07 1.43l-1.41 3.8C3.31 9.06 3.69 10 4.5 10c.06 0 .12 0 .17-.01l1.79-.1V19c0 .55.45 1 1 1h8.08c.55 0 1-.45 1-1v-9.11l1.79.1c.06 0 .11.01.17.01.81 0 1.19-.94.66-2.35zM7.33 4.42h9.34L17.5 7.1H6.5l.83-2.68z"/></svg>
            </div>
            <h3>店家</h3>
            <p>餐廳業者登入</p>
        </button>

        <button type="submit" name="identity" value="admin" class="identity-card admin-icon">
            <div class="icon-container admin-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
            </div>
            <h3>管理員</h3>
            <p>系統管理者登入</p>
        </button>
    </form>

    <a href="guest_continue.php" class="visitor-link">以訪客身分繼續</a>

    <div class="footer-container">
        <p>Fu Jen Catholic University • 輔仁大學</p>
    </div>

</body>
</html>