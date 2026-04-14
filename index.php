<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaFu - 輔大學餐熱量計算機</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'PingFang TC', 'Heiti TC', 'Noto Sans TC', sans-serif;
        }
        body {
            background-color: #002B5B;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
        }
        .main-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #FFD700;
        }
        .identity-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .identity-button {
            padding: 1rem 2rem;
            font-size: 1.2rem;
            border: none;
            border-radius: 8px;
            background-color: #FFD700;
            color: #002B5B;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .identity-button:hover {
            background-color: #FFC107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="main-title">請選擇您的身份</h2>
        <form class="identity-form" action="kafu_login.php" method="POST">
            <button type="submit" name="identity" value="user" class="identity-button">使用者</button>
            <button type="submit" name="identity" value="store" class="identity-button">店家</button>
            <button type="submit" name="identity" value="admin" class="identity-button">管理員</button>
        </form>
    </div>
</body>
</html>