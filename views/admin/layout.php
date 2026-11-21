<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'پنل مدیریت') ?> - VPN Master Bot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-950 border-l border-slate-800 flex flex-col justify-between hidden md:flex shrink-0">
        <div>
            <div class="p-5 border-b border-slate-800 flex items-center space-x-3 space-x-reverse">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-xl text-white shadow-lg shadow-blue-500/20">
                    ⚡
                </div>
                <div>
                    <h1 class="font-bold text-sm text-white">VPN Bot Manager</h1>
                    <span class="text-xs text-blue-400">سنایی & پاسارگاد</span>
                </div>
            </div>

            <nav class="p-3 space-y-1 text-xs">
                <a href="/admin/dashboard" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'dashboard' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>📊</span> داشبورد آمار
                </a>
                
                <a href="/admin/admins" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'admins' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>👥</span> ادمین‌ها و دسترسی‌ها
                </a>

                <a href="/admin/buttons" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'buttons' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>🔲</span> چیدمان و رنگ دکمه‌ها
                </a>

                <a href="/admin/panels" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'panels' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>🌐</span> مدیریت پنل‌ها (چند سروره)
                </a>

                <a href="/admin/plans" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'plans' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>📋</span> پلن‌ها و تعرفه‌ها
                </a>

                <a href="/admin/orders" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'orders' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>📥</span> سفارشات و فیش‌ها
                </a>

                <a href="/admin/cards" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'cards' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>💳</span> شماره کارت‌ها
                </a>

                <a href="/admin/customization" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'customization' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>🎨</span> تنظیمات، گزارشات و بک‌آپ
                </a>

                <a href="/admin/migration" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'migration' ? 'bg-amber-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>🚀</span> مهاجرت از میرزابوت
                </a>

                <a href="/admin/users" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'users' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>👤</span> کاربران ربات
                </a>

                <a href="/admin/broadcast" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($activeMenu ?? '') === 'broadcast' ? 'bg-blue-600 text-white font-medium shadow-md' : 'text-slate-400 hover:bg-slate-900 hover:text-white' ?>">
                    <span>📢</span> پیام همگانی
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-800">
            <a href="/admin/logout" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-xl text-red-400 bg-red-950/30 hover:bg-red-900/40 border border-red-800/40 transition text-xs">
                <span>🚪</span> خروج از پنل
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <header class="h-16 border-b border-slate-800 bg-slate-950/60 backdrop-blur px-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-base font-semibold text-white"><?= htmlspecialchars($title ?? 'مدیریت') ?></h2>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <a href="/miniapp" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-600/30 transition flex items-center gap-1.5">
                    <span>📱</span> پیش‌نمایش مینی‌اپ
                </a>
                <span class="text-slate-600">|</span>
                <div class="flex items-center gap-2 text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span><?= htmlspecialchars($_SESSION['admin_user']['name'] ?? 'مدیر') ?></span>
                    <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-400">
                        <?= ($_SESSION['admin_user']['role'] ?? '') === 'super_admin' ? 'مدیر کل' : 'اپراتور' ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Page Body -->
        <main class="flex-1 p-6 overflow-y-auto">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-950/60 border border-emerald-600/40 text-emerald-200 text-xs flex items-center gap-2">
                    <span>✅</span>
                    <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-600/40 text-rose-200 text-xs flex items-center gap-2">
                    <span>❌</span>
                    <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <?= $pageContent ?? '' ?>
        </main>
    </div>

</body>
</html>
