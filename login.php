<?php
    $role = $_GET['role'] ?? '使用者';
    $role_name = ($role == 'store') ? '店家' : (($role == 'admin') ? '管理員' : '使用者');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo $role_name; ?>登入 - KaFu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#003B71] flex flex-col items-center pt-16 text-white">
    <div class="bg-white px-8 py-4 rounded-2xl mb-12">
        <h1 class="text-[#003B71] text-4xl font-black">KaFu</h1>
    </div>

    <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-lg text-gray-900">
        <a href="index.php" class="text-gray-500 mb-6 inline-block">← 返回選擇身份</a>
        
        <div class="flex flex-col items-center mb-10">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center text-3xl mb-4">👤</div>
            <h2 class="text-3xl font-bold"><?php echo $role_name; ?>登入</h2>
        </div>

        <form action="auth.php" method="POST" class="space-y-6">
            <input type="hidden" name="role" value="<?php echo $role; ?>">
            <div>
                <label class="block font-bold mb-2">學號 / 帳號</label>
                <input type="text" name="username" class="w-full p-4 rounded-xl border bg-gray-50" placeholder="請輸入帳號" required>
            </div>
            <div>
                <label class="block font-bold mb-2">密碼</label>
                <input type="password" name="password" class="w-full p-4 rounded-xl border bg-gray-50" placeholder="請輸入密碼" required>
            </div>
            <button type="submit" class="w-full bg-[#003B71] text-white p-4 rounded-xl font-bold text-xl hover:bg-blue-900">登入</button>
        </form>
    </div>
</body>
</html>