<?php
session_start();
include('db.php');

require_once __DIR__ . '/google-api/vendor/autoload.php';

$clientID = '483866428221-g4onr1815ej7pq3gige485c31efe1reu.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/kafu/google_login.php';

$client = new \Google\Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

$client->setPrompt('select_account');

// ==========================================================
// 🌟 專業版：漂亮的過場動畫跳轉畫面
// ==========================================================
function showLoadingScreen($message, $target_url) {
    echo "
    <!DOCTYPE html>
    <html lang='zh-TW'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>請稍候...</title>
        <style>
            body {
                background-color: #f5f7fa;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                font-family: 'Microsoft JhengHei', sans-serif;
            }
            .loading-box {
                background: white;
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                text-align: center;
                width: 80%;
                max-width: 300px;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #4285F4; /* Google 的經典藍色 */
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            p { color: #333; font-size: 16px; font-weight: bold; margin: 0; }
        </style>
    </head>
    <body>
        <div class='loading-box'>
            <div class='spinner'></div>
            <p>{$message}</p>
        </div>
        <script>
            // 等待 1.5 秒後自動跳轉，讓使用者看清楚訊息
            setTimeout(function() {
                window.location.href = '{$target_url}';
            }, 1500);
        </script>
    </body>
    </html>
    ";
    exit();
}

// ==========================================================
// 情境 A：Google 驗證成功，跳轉回來了
// ==========================================================
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if(!isset($token['error'])){
        $client->setAccessToken($token['access_token']);
        
        $google_oauth = new \Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        $google_id = $google_account_info->id;
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        // 檢查 Google ID
        $check_sql = "SELECT * FROM accounts WHERE google_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $google_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // 👉 老用戶：直接登入，不用跳提示，直接轉址
            $user = $result->fetch_assoc();
            $_SESSION['u_id'] = $user['u_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            
            header("Location: profile.php");
            exit();
        } else {
            // 👉 新用戶：自動註冊
            $role_id = 3; 
            $goal_cal = 2000;
            $random_password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT); 

            $insert_sql = "INSERT INTO accounts (name, accounts, password, role_id, goal_cal, google_id, email) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssiiss", $name, $email, $random_password, $role_id, $goal_cal, $google_id, $email);
            
            if ($insert_stmt->execute()) {
                $_SESSION['u_id'] = $insert_stmt->insert_id;
                $_SESSION['name'] = $name;
                $_SESSION['role_id'] = $role_id;
                
                // 💡 呼叫新的過場動畫函數
                showLoadingScreen("註冊成功！準備進入首頁...", "index.php");
            } else {
                showLoadingScreen("註冊失敗，該 Email 可能已被使用。", "login.php");
            }
        }
    } else {
        showLoadingScreen("Google 授權失敗。", "login.php");
    }
    exit();
}

// ==========================================================
// 情境 B：產生登入網址並跳轉
// ==========================================================
$authUrl = $client->createAuthUrl();
header("Location: " . $authUrl);
exit();
?>