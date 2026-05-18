<?php
session_start();
include('db.php');
include('header.php');

$error_msg = '';

// 檢查是否有「記住帳密」的 Cookie，有就讀取出來預填
$saved_account = isset($_COOKIE['saved_account']) ? $_COOKIE['saved_account'] : '';
$saved_password = '';

if (isset($_COOKIE['saved_password'])) {
    $saved_password = base64_decode($_COOKIE['saved_password']);
}

$cookie_checked = (!empty($saved_account) && !empty($saved_password)) ? 'checked' : '';

// 當表單送出時執行登入驗證
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acc = $_POST['accounts'];
    $pwd = $_POST['password'];
    $remember = isset($_POST['remember_me']); 

    $sql = "SELECT * FROM accounts WHERE accounts = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $acc, $pwd);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        $_SESSION['u_id'] = $user['u_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role_id'] = $user['role_id'];

        if ($remember) {
            setcookie('saved_account', $acc, time() + (30 * 24 * 60 * 60), "/", "", false, true);
            setcookie('saved_password', base64_encode($pwd), time() + (30 * 24 * 60 * 60), "/", "", false, true);
        } else {
            if (isset($_COOKIE['saved_account'])) {
                setcookie('saved_account', '', time() - 3600, "/");
            }
            if (isset($_COOKIE['saved_password'])) {
                setcookie('saved_password', '', time() - 3600, "/");
            }
        }

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
    
    /* 🚀 關鍵修改：強制移除所有瀏覽器預設生成的眼睛與清除按鈕 */
    .input-pill input::-ms-reveal,
    .input-pill input::-ms-clear {
        display: none !important;
    }
    
    /* 密碼顯示/隱藏按鈕樣式 */
    .toggle-password {
        background: none; border: none; padding: 0; margin-left: 10px;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .toggle-password svg { margin-right: 0; fill: #888; transition: 0.2s; }
    .toggle-password:hover svg { fill: var(--fujen-blue, #002B5B); }
    
    /* 記住我與註冊排版樣式 */
    .remember-container {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px; padding: 0 5px;
    }
    .checkbox-label {
        display: flex; align-items: center; gap: 6px; color: #666; font-size: 13px; cursor: pointer;
    }
    .checkbox-label input { cursor: pointer; }
    .register-link { color: #666; font-size: 13px; text-decoration: none; transition: 0.2s; }
    .register-link:hover { color: var(--primary-orange, #FF8C42); }

    /* 圓潤的登入按鈕 */
    .submit-btn { 
        width: 100%; background: var(--primary-orange, #FF8C42); color: white; 
        border: none; padding: 14px; border-radius: 30px; font-size: 16px; font-weight: bold; 
        cursor: pointer; transition: 0.2s; margin-top: 5px; letter-spacing: 2px;
        box-shadow: 0 4px 10px rgba(255,140,66,0.3);
    }
    .submit-btn:active { transform: scale(0.97); }
    
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

    /* 長條形 Google 登入按鈕 */
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
            
            <div class="input-pill">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <input type="text" name="accounts" placeholder="帳號" value="<?php echo htmlspecialchars($saved_account); ?>" required autocomplete="off">
            </div>
            
            <div class="input-pill">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                <input type="password" name="password" id="password-input" placeholder="請輸入您的密碼" value="<?php echo htmlspecialchars($saved_password); ?>" required>
                
                <button type="button" class="toggle-password" id="toggle-password-btn" title="顯示/隱藏密碼">
                    <svg id="eye-icon" viewBox="0 0 24 24">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                    </svg>
                </button>
            </div>
            
            <div class="remember-container">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember_me" <?php echo $cookie_checked; ?>> 記住我
                </label>
                <a href="register.php" class="register-link">註冊帳號</a>
            </div>
            
            <button type="submit" class="submit-btn">登入</button>

            <div class="divider">
                <span>其他登入方式</span>
            </div>

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

<script>
    const passwordInput = document.getElementById('password-input');
    const togglePasswordBtn = document.getElementById('toggle-password-btn');
    const eyeIcon = document.getElementById('eye-icon');

    // 定義眼睛打開與閉合的 SVG Path 
    const eyeOpenPath = "M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z";
    const eyeClosePath = "M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.82l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.74-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 2.2 0 4.26-.6 6-1.64l.43.43 2.3 2.3 1.27-1.27L3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15c.01-.11.01-.22.01-.33 0-1.66-1.34-3-3-3-.11 0-.22 0-.33.01z";

    togglePasswordBtn.addEventListener('click', function() {
        // 檢查當前的 input type
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            // 將圖示換成閉眼 (含有斜線) 的路徑
            eyeIcon.innerHTML = `<path d="${eyeClosePath}"/>`;
        } else {
            passwordInput.type = 'password';
            // 將圖示換成開眼的路徑
            eyeIcon.innerHTML = `<path d="${eyeOpenPath}"/>`;
        }
    });
</script>