<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>KaFu - 輔大餐廳熱量計算機</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #003B71; }
        .card { transition: transform 0.3s; }
        .card:hover { transform: scale(1.05); }
    </style>
</head>
<body class="flex flex-col items-center pt-16 text-white font-sans">
    <div class="bg-white px-8 py-4 rounded-2xl mb-12">
        <h1 class="text-[#003B71] text-5xl font-black">KaFu</h1>
        <p class="text-[#003B71] text-center text-sm font-bold">輔大學餐熱量計算機</p>
    </div>

    <h2 class="text-3xl mb-16">請選擇您的身份</h2>

    <div class="flex gap-10">
        <a href="login.php?role=user" class="card bg-white p-10 rounded-3xl shadow-xl flex flex-col items-center w-72 text-gray-900">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">👤</div>
            <h3 class="text-3xl font-bold mb-2">使用者</h3>
            <p class="text-gray-500">學生 / 教職員登入</p>
        </a>

        <a href="login.php?role=store" class="card bg-white p-10 rounded-3xl shadow-xl flex flex-col items-center w-72 text-gray-900">
            <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mb-6">🏪</div>
            <h3 class="text-3xl font-bold mb-2">店家</h3>
            <p class="text-gray-500">餐廳業者登入</p>
        </a>

        <a href="login.php?role=admin" class="card bg-white p-10 rounded-3xl shadow-xl flex flex-col items-center w-72 text-gray-900">
            <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mb-6">🛡️</div>
            <h3 class="text-3xl font-bold mb-2">管理員</h3>
            <p class="text-gray-500">系統管理者登入</p>
        </a>
    </div>

    <a href="main.php" class="mt-16 text-xl text-sky-200 underline">以訪客身分繼續</a>
</body>
</html>