<?php
session_start();
include('db.php');
include('header.php');

$error_msg = '';

// 當表單送出時執行登入驗證
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acc = $_POST['accounts'];
    $pwd = $_POST['password'];

    // 💡 邏輯改變：我們不再限制 role_id，只要帳號密碼對了就把資料抓出來
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

        // 💡 這裡是最關鍵的「身分自動分流」
        if ($user['role_id'] == 3) {
            // 一般學生 -> 跳到學生個人檔案
            echo "<script>window.location.href = 'profile.php';</script>";
        } else if ($user['role_id'] == 2) {
            // 店家 -> 跳到剛剛做好的店家儀表板
            echo "<script>window.location.href = 'store_profile.php';</script>";
        } else if ($user['role_id'] == 1) {
            // 管理員 -> 跳到管理員後台
            echo "<script>window.location.href = 'admin_dashboard.php';</script>";
        }
        exit();
    } else {
        $error_msg = '帳號或密碼錯誤，請重新輸入！';
    }
}
?>

<style>
    /* 基礎背景設定：鋪滿畫面的深藍色 */
    .login-page {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--fujen-blue, #002B5B);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 20px; overflow-y: auto; z-index: 100;
        -ms-overflow-style: none; scrollbar-width: none;
    }
    .login-page::-webkit-scrollbar { display: none; }

    /* Logo 區塊 */
    .logo-section { text-align: center; margin-bottom: 25px; }
    .logo-box { 
        background-color: white; border-radius: 15px; padding: 15px 25px; 
        display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
    }
    .logo-box img { max-width: 110px; height: auto; display: block; margin: 0 auto; }
    .logo-box p { color: #666; font-size: 13px; margin-top: 8px; font-weight: bold; margin-bottom: 0; }

    /* 白色登入卡片 */
    .form-container {
        background: white; width: 100%; max-width: 320px;
        border-radius: 15px; padding: 35px 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .form-title { text-align: center; color: var(--fujen-blue, #002B5B); margin-top: 0; margin-bottom: 25px; font-size: 20px; letter-spacing: 1px;}
    
    .input-group { margin-bottom: 20px; }
    .input-group label { display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: bold; }
    .input-group input { 
        width: 100%; padding: 14px; border: 1px solid #ddd; 
        border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafafa; transition: 0.2s;
    }
    .input-group input:focus { outline: none; border-color: var(--fujen-blue, #002B5B); background: white; }
    
    .submit-btn { 
        width: 100%; background: var(--primary-orange, #FF8C42); color: white; 
        border: none; padding: 15px; border-radius: 8px; font-size: 16px; font-weight: bold; 
        cursor: pointer; transition: 0.2s; margin-top: 10px; box-shadow: 0 4px 10px rgba(255,140,66,0.3);
    }
    .submit-btn:active { transform: scale(0.97); }
    
    .error-msg { color: #ff4d4d; font-size: 13px; text-align: center; margin-bottom: 15px; font-weight: bold; background: #ffe6e6; padding: 10px; border-radius: 8px;}
    
    .footer-text { margin-top: 40px; font-size: 11px; color: rgba(255,255,255,0.5); text-align: center; line-height: 1.6; }
</style>

<div class="login-page">
    
    <div class="logo-section">
        <div class="logo-box">
            <img src="logo.png" alt="KaFu Logo">
            <p>輔大學餐熱量計算機</p>
        </div>
    </div>

    <div class="form-container">
        <h2 class="form-title">登入</h2>
        
        <?php if($error_msg): ?>
            <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label>帳號</label>
                <input type="text" name="accounts" placeholder="請輸入帳號" required autocomplete="off">
            </div>
            
            <div class="input-group">
                <label>密碼</label>
                <input type="password" name="password" placeholder="請輸入密碼" required>
            </div>
            
            <button type="submit" class="submit-btn">登入</button>
        </form>
    </div>

    <div class="footer-text">
        Fu Jen Catholic University • 輔仁大學<br>
        Information Management Sophomore Project
    </div>
</div>

</div> </body>
</html>