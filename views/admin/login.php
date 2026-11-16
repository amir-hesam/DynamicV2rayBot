<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به مدیریت - VPN Master Bot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-36 h-36 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-36 h-36 bg-emerald-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl shadow-lg shadow-blue-500/30">
                ⚡
            </div>
            <h1 class="text-2xl font-bold text-white">ورود به وب پنل ادمین</h1>
            <p class="text-sm text-slate-400 mt-1">مدیریت ربات، پنل‌های سنایی و پاسارگاد</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-200 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/admin/login" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">نام کاربری</label>
                <input type="text" name="username" required value="admin" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">رمز عبور</label>
                <input type="password" name="password" required value="admin123" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition text-sm">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-medium shadow-lg shadow-blue-600/30 transition text-sm">
                ورود به پنل
            </button>
        </form>

        <p class="text-center text-xs text-slate-500 mt-6">
            پیش‌فرض: نام کاربری: <code>admin</code> / رمز عبور: <code>admin123</code>
        </p>
    </div>

</body>
</html>
