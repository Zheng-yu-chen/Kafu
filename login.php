<?php
session_start();
include('db.php');
include('header.php');

$error_msg = '';

// 當表單送出時執行登入驗證
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acc = $_POST['accounts'];
    $pwd = $_POST['password'];

    $sql = "SELECT * FROM accounts WHERE accounts = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $acc, $pwd);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 將資料存入 Session
        $_SESSION['u_id'] = $user['u_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role_id'] = $user['role_id'];

        // 身分自動分流
        if ($user['role_id'] == 3) {
            echo "<script>window.location.href = 'profile.php';</script>";
        } else if ($user['role_id'] == 2) {
            echo "<script>window.location.href = 'store_profile.php';</script>";
        } else if ($user['role_id'] == 1) {
            echo "<script>window.location.href = 'admin_dashboard.php';</script>";
        }
        exit();
    } else {
        $error_msg = '帳號或密碼錯誤，請重新輸入！';
    }
}
?>

<style>
    /* 基礎背景設定 */
    .login-page {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--fujen-blue, #002B5B);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 20px; overflow-y: auto; z-index: 100;
        -ms-overflow-style: none; scrollbar-width: none;
    }
    .login-page::-webkit-scrollbar { display: none; }

    .back-btn-login {
        position: absolute; top: 25px; left: 20px; color: white;
        text-decoration: none; font-size: 14px; font-weight: bold; opacity: 0.9; transition: 0.2s; z-index: 1000;
    }
    .back-btn-login:active { transform: scale(0.95); opacity: 1; }

    /* Logo 區塊 */
    .logo-section { text-align: center; margin-bottom: 25px; }
    .logo-box { background-color: transparent; border-radius: 0; padding: 10px; display: inline-block; }
    .logo-box img { max-width: 140px; height: auto; display: block; margin: 0 auto; }
    .logo-box p { color: white; font-size: 15px; margin-top: 12px; font-weight: bold; letter-spacing: 1px; }
    
    /* 白色登入卡片 */
    .form-container {
        background: white; width: 100%; max-width: 340px;
        border-radius: 20px; padding: 40px 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    /* 膠囊狀輸入框設計 */
    .input-pill {
        display: flex; align-items: center; background: #f4f6f8;
        border-radius: 30px; padding: 5px 20px; margin-bottom: 20px;
        transition: 0.2s; border: 1px solid transparent;
    }
    .input-pill:focus-within { background: white; border-color: var(--fujen-blue, #002B5B); box-shadow: 0 0 0 3px rgba(0,43,91,0.1); }
    
    .input-pill svg { fill: #aaa; width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0; }
    
    .input-pill input { 
        flex: 1; border: none; background: transparent; 
        padding: 12px 0; font-size: 14px; outline: none; color: #333;
    }
    .input-pill input::placeholder { color: #aaa; }
    
    /* 圓潤的登入按鈕 */
    .submit-btn { 
        width: 100%; background: var(--primary-orange, #FF8C42); color: white; 
        border: none; padding: 14px; border-radius: 30px; font-size: 16px; font-weight: bold; 
        cursor: pointer; transition: 0.2s; margin-top: 5px; letter-spacing: 2px;
        box-shadow: 0 4px 10px rgba(255,140,66,0.3);
    }
    .submit-btn:active { transform: scale(0.97); }

    /* 左右輔助連結 (註冊 / 忘記密碼) */
    .helper-links {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 18px; padding: 0 5px;
    }
    .helper-links a {
        color: #666; font-size: 13px; text-decoration: none; transition: 0.2s;
    }
    .helper-links a:hover { color: var(--primary-orange, #FF8C42); }
    
    .error-msg { color: #ff4d4d; font-size: 13px; text-align: center; margin-bottom: 20px; font-weight: bold; background: #ffe6e6; padding: 10px; border-radius: 8px;}
    
    /* 其他登入方式分隔線 */
    .divider {
        display: flex; align-items: center; text-align: center;
        color: #aaa; font-size: 12px; margin: 35px 0 20px;
    }
    .divider::before, .divider::after {
        content: ''; flex: 1; border-bottom: 1px solid #eee;
    }
    .divider span { padding: 0 15px; }

    /* 💡 長條形 Google 登入按鈕 */
    .google-long-btn {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        width: 100%; background: white; color: #555; border: 1px solid #ddd;
        padding: 12px; border-radius: 8px; font-size: 15px; font-weight: bold;
        text-decoration: none; transition: 0.2s; box-sizing: border-box;
    }
    .google-long-btn:active { background: #f9f9f9; transform: scale(0.98); }
    .google-long-btn img { width: 20px; height: 20px; }

    .footer-text { margin-top: 40px; font-size: 11px; color: rgba(255,255,255,0.5); text-align: center; line-height: 1.6; }
</style>

<div class="login-page">
    
    <a href="profile.php" class="back-btn-login">❮ 返回</a>
    
    <div class="logo-section">
        <div class="logo-box">
            <img src="logo.png" alt="KaFu Logo">
            <p>輔大學餐熱量計算機</p>
        </div>
    </div>

    <div class="form-container">
        <?php if($error_msg): ?>
            <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            
            <!-- 帳號輸入框 -->
            <div class="input-pill">
                <!-- SVG User Icon -->
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <input type="text" name="accounts" placeholder="用戶名 / 學號" required autocomplete="off">
            </div>
            
            <!-- 密碼輸入框 -->
            <div class="input-pill">
                <!-- SVG Lock Icon -->
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                <input type="password" name="password" placeholder="請輸入您的密碼" required>
            </div>
            
            <!-- 登入按鈕 -->
            <button type="submit" class="submit-btn">登入</button>
            
            <!-- 註冊與忘記密碼 -->
            <div class="helper-links">
                <a href="register.php">註冊帳號</a>
            </div>

            <!-- 分隔線 -->
            <div class="divider">
                <span>其他登入方式</span>
            </div>

            <!-- 💡 單一長條形 Google 按鈕 -->
            <a href="google_login.php" class="google-long-btn">
                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google">
                使用 Google 登入
            </a>
            
        </form>
    </div>

    <div class="footer-text">
        Fu Jen Catholic University • 輔仁大學<br>
        Information Management Sophomore Project
    </div>
</div>