<?php
session_start();
include('db.php');
include('header.php');

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $acc = $_POST['accounts'];
    $pwd = $_POST['password'];
    $role_id = 3; // 預設為一般學生
    $goal_cal = 2000; // 預設目標熱量
    if (!isset($_POST['agree'])) {
        $error_msg = '您必須勾選同意隱私條款與免責聲明才能註冊。';
    } 
    // 2. 帳號格式驗證
    elseif (!preg_match('/^[a-zA-Z0-9]{1,10}$/', $_POST['accounts'])) {
        $error_msg = '帳號格式錯誤：僅限英文與數字，且長度不得超過 10 個字元。';
    }
    
    // 接著才是密碼驗證與註冊邏輯
    elseif (!preg_match('/^[a-zA-Z0-9]{1,20}$/', $pwd)) {
        $error_msg = '密碼格式錯誤：僅限英文與數字，且長度不得超過 20 個字元。';
    } 
    else {
        // 2. 檢查帳號是否已存在
        $check_sql = "SELECT * FROM accounts WHERE accounts = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $acc);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_msg = '此帳號已經註冊過！請直接登入。';
        } else {
            // 3. 🌟 關鍵安全性更新：密碼雜湊加密 (Hash)
            $hashed_password = password_hash($pwd, PASSWORD_DEFAULT);

            // 4. 將加密後的密碼寫入資料庫
            $insert_sql = "INSERT INTO accounts (name, accounts, password, role_id, goal_cal) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssii", $name, $acc, $hashed_password, $role_id, $goal_cal);
            
            if ($insert_stmt->execute()) {
                echo "<script>alert('註冊成功！請使用新帳號登入。'); window.location.href='login.php';</script>";
                exit();
            } else {
                $error_msg = '註冊失敗，請稍後再試。';
            }
        }
    }
}
?>

<style>
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

    .logo-section { 
    text-align: center; 
    margin-top: 30px;
    }
    .logo-box { background-color: transparent; border-radius: 0; padding: 10px; display: inline-block; box-shadow: none; }
    .logo-box img { max-width: 140px; height: auto; display: block; margin: 0 auto; }
    .logo-box p { color: white; font-size: 15px; margin-top: 12px; font-weight: bold; letter-spacing: 1px; }
    
    .form-container {
        background: white; width: 100%; max-width: 320px;
        border-radius: 15px; padding: 35px 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
        width: 100%; background: var(--fujen-blue, #002B5B); color: white; 
        border: none; padding: 15px; border-radius: 8px; font-size: 16px; font-weight: bold; 
        cursor: pointer; transition: 0.2s; margin-top: 10px; box-shadow: 0 4px 10px rgba(0,43,91,0.2);
    }
    .submit-btn:active { transform: scale(0.97); }
    
    .error-msg { color: #ff4d4d; font-size: 13px; text-align: center; margin-bottom: 15px; font-weight: bold; background: #ffe6e6; padding: 10px; border-radius: 8px;}
    
    .google-btn {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: white; color: #555; border: 1px solid #ddd; padding: 12px;
        border-radius: 8px; font-size: 14px; font-weight: bold; text-decoration: none;
        transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-top: 20px;
    }
    .google-btn:active { background: #f9f9f9; transform: scale(0.98); }
    .google-btn img { width: 18px; height: 18px; }

    .footer-text { margin-top: 40px; font-size: 11px; color: rgba(255,255,255,0.5); text-align: center; line-height: 1.6; }
</style>

<div class="login-page">
    
    <a href="login.php" class="back-btn-login">❮ 返回登入</a>
    
    <div class="logo-section">
        <div class="logo-box">
            <img src="logo.png" alt="KaFu Logo">
            <p>建立您的個人帳號</p>
        </div>
    </div>

    <div class="form-container">
        <h2 class="form-title">註冊</h2>
        
        <?php if($error_msg): ?>
            <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label>姓名 / 暱稱</label>
                <input type="text" name="name" placeholder="請輸入姓名" required autocomplete="off">
            </div>

            <div class="input-group">
    <label>帳號 (限英文與數字，最多10字)</label>
    <input type="text" name="accounts" 
           placeholder="請輸入帳號" 
           pattern="[a-zA-Z0-9]{1,10}" 
           maxlength="10" 
           title="帳號僅限英文與數字，長度不能超過 10 個字元"
           required autocomplete="off">
</div>
            
            <div class="input-group">
    <label>設定密碼 (限英文與數字，最多20字)</label>
    <input type="password" name="password" 
           placeholder="請輸入密碼" 
           pattern="[a-zA-Z0-9]{1,20}" 
           maxlength="20"
           title="密碼僅限英文與數字，長度不能超過 20 個字元"
           required autocomplete="off">
</div>
<div class="input-group" style="margin: 15px 0; font-size: 13px;">
    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; color: #666;">
        <input type="checkbox" name="agree" required style="width: auto; margin-top: 3px;">
        <span>
            我已閱讀並同意 <a href="privacy.php" target="_blank" style="color: var(--fujen-blue, #002B5B);">隱私權條款</a>
            且了解本系統提供的熱量數值「僅供參考」。
        </span>
    </label>
</div>
            
            <button type="submit" class="submit-btn">完成註冊</button>

        </form>
    </div>

    <div class="footer-text">
        Fu Jen Catholic University • 輔仁大學<br>
        Precision Nutrition & Calorie Tracking
    </div>
</div>