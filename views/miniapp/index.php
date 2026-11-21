<!DOCTYPE html>
<html lang="fa" dir="rtl" class="theme-dark dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>VPN Master Mini App</title>
    <!-- Telegram WebApp SDK (Local first for Iran network, then official CDN) -->
    <script src="/assets/js/telegram-web-app.js"></script>
    <script>
        if (typeof window.Telegram === 'undefined') {
            document.write('<script src="https://telegram.org/js/telegram-web-app.js"><\/script>');
        }
    </script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vazirmatn Font -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <!-- QRCode JS -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            user-select: none;
            -webkit-user-select: none;
            touch-action: pan-y;
            overflow-x: hidden;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        /* --- THEME STYLES (Neutral Zinc / Pasargad Theme) --- */
        /* Dark Theme - Pure Obsidian / Zinc (No Blue) */
        html.theme-dark body, html.dark body {
            background-color: #09090b;
            color: #f4f4f5;
        }
        html.theme-dark .app-header, html.dark .app-header {
            background: rgba(9, 9, 11, 0.88);
            border-bottom: 1px solid #27272a;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        html.theme-dark .app-card, html.dark .app-card {
            background: #141417;
            border: 1px solid #27272a;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #f4f4f5;
        }
        html.theme-dark .app-card-solid, html.dark .app-card-solid {
            background: #1c1c21;
            border: 1px solid #2e2e35;
        }
        html.theme-dark .app-input, html.dark .app-input {
            background: #121215;
            border: 1px solid #323238;
            color: #f4f4f5;
        }
        html.theme-dark .app-input:focus, html.dark .app-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }
        html.theme-dark .app-navbar, html.dark .app-navbar {
            background: rgba(14, 14, 17, 0.94);
            border-top: 1px solid #27272a;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        html.theme-dark .text-muted, html.dark .text-muted { color: #a1a1aa; }
        html.theme-dark .bg-subtle, html.dark .bg-subtle { background-color: rgba(255, 255, 255, 0.05); }

        /* Light Theme - Clean Crisp Slate / White */
        html.theme-light body {
            background-color: #f8fafc;
            color: #0f172a;
        }
        html.theme-light .app-header {
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid #e2e8f0;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        html.theme-light .app-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);
            color: #0f172a;
        }
        html.theme-light .app-card-solid {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }
        html.theme-light .app-input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        html.theme-light .app-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
        }
        html.theme-light .app-navbar {
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid #e2e8f0;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.04);
        }
        html.theme-light .text-muted { color: #64748b; }
        html.theme-light .bg-subtle { background-color: #f1f5f9; }

        /* Tab Button Ripple & Transition matching Pasargad */
        .tab-btn {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tab-btn.active {
            transform: translateY(-2px);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { display: none; }

        /* Custom Modern Range Slider with Animated Track & Glowing Thumb matching Pasargad */
        input[type=range], #custom-traffic-slider, #custom-days-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 9999px;
            outline: none;
            cursor: pointer;
            transition: background 0.15s ease-out;
            background: linear-gradient(to left, #6366f1 0%, #8b5cf6 20%, rgba(30, 41, 59, 0.7) 20%, rgba(30, 41, 59, 0.7) 100%);
        }
        input[type=range]::-webkit-slider-thumb, #custom-traffic-slider::-webkit-slider-thumb, #custom-days-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 0 14px rgba(99, 102, 241, 0.8), 0 2px 6px rgba(0, 0, 0, 0.35);
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
        }
        input[type=range]:active::-webkit-slider-thumb, #custom-traffic-slider:active::-webkit-slider-thumb, #custom-days-slider:active::-webkit-slider-thumb {
            transform: scale(1.3);
            box-shadow: 0 0 22px rgba(129, 140, 248, 1), 0 4px 10px rgba(0, 0, 0, 0.45);
        }
        input[type=range]::-moz-range-thumb, #custom-traffic-slider::-moz-range-thumb, #custom-days-slider::-moz-range-thumb {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 0 14px rgba(99, 102, 241, 0.8), 0 2px 6px rgba(0, 0, 0, 0.35);
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
        }
        input[type=range]:active::-moz-range-thumb, #custom-traffic-slider:active::-moz-range-thumb, #custom-days-slider:active::-moz-range-thumb {
            transform: scale(1.3);
            box-shadow: 0 0 22px rgba(129, 140, 248, 1), 0 4px 10px rgba(0, 0, 0, 0.45);
        }
    </style>
</head>
<body class="min-h-screen pb-32 flex flex-col antialiased">

    <!-- ============================================================== -->
    <!-- TELEGRAM CLIENT ACCESS GATE ERROR OVERLAY                     -->
    <!-- ============================================================== -->
    <div id="telegram-gate-overlay" class="fixed inset-0 z-50 bg-slate-950/95 backdrop-blur-2xl hidden flex flex-col items-center justify-center p-6 text-center text-white">
        <div class="w-20 h-20 rounded-3xl bg-indigo-600/20 border border-indigo-500/40 flex items-center justify-center text-4xl mb-5 shadow-2xl shadow-indigo-600/30">
            📱
        </div>
        <h2 class="text-xl font-bold mb-2">دسترسی مستقیم از مرورگر مسدود است</h2>
        <p class="text-xs text-slate-300 max-w-sm leading-relaxed mb-6">
            این مینی‌اپ اختصاصی است و جهت شناسایی امن حساب کاربری شما باید مستقیماً از طریق دکمه «ورود به مینی‌اپ» درون ربات تلگرام باز شود.
        </p>
        <a id="telegram-bot-link" href="#" class="px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xl shadow-indigo-600/40 transition active:scale-95 flex items-center gap-2">
            <span>🚀</span>
            <span>ورود از طریق ربات تلگرام</span>
        </a>
    </div>

    <!-- ============================================================== -->
    <!-- TOP STICKY HEADER                                              -->
    <!-- ============================================================== -->
    <header class="app-header px-4 py-3 sticky top-0 z-40 flex items-center justify-between transition-colors">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-brand-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/25">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h1 class="font-bold text-sm leading-tight tracking-tight flex items-center gap-1.5">
                    <span>اینترنت آزاد و پرسرعت</span>
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </h1>
                <p class="text-[11px] text-muted flex items-center gap-1 mt-0.5" id="header-user-text">
                    در حال اتصال...
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Quick Balance Pill -->
            <div onclick="switchTab('wallet')" class="cursor-pointer flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 border border-indigo-500/20 text-xs font-semibold hover:bg-indigo-500/20 transition active:scale-95">
                <span class="text-xs">💰</span>
                <span id="header-balance" class="font-mono">0</span>
                <span class="text-[10px] font-normal text-muted">تومان</span>
            </div>

            <!-- Modern Theme Switcher matching Pasargad panel -->
            <div class="theme-switcher-box flex flex-col p-0.5 rounded-xl bg-slate-100 dark:bg-[#18181b] border border-slate-200 dark:border-zinc-800 text-[11px] select-none shadow-sm">
                <button type="button" onclick="setTheme('light')" id="themeBtnLight" class="flex items-center gap-1.5 px-2 py-0.5 rounded-lg transition-all font-semibold text-slate-800 dark:text-zinc-400 hover:text-black dark:hover:text-white">
                    <i class="fa-regular fa-sun text-[10px]"></i>
                    <span>Light</span>
                </button>
                <button type="button" onclick="setTheme('dark')" id="themeBtnDark" class="flex items-center gap-1.5 px-2 py-0.5 rounded-lg transition-all font-semibold text-slate-500 dark:text-white hover:text-black dark:hover:text-white">
                    <i class="fa-regular fa-moon text-[10px]"></i>
                    <span>Dark</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ============================================================== -->
    <!-- MAIN CONTAINER                                                 -->
    <!-- ============================================================== -->
    <main class="flex-1 p-4 max-w-md mx-auto w-full space-y-6">

        <!-- ============================================================== -->
        <!-- TAB 1: حساب کاربری (PROFILE) [Rightmost Tab 1]                  -->
        <!-- ============================================================== -->
        <section id="tab-profile" class="space-y-4">
            <!-- Profile Hero Card (Pasargad Style) -->
            <div class="rounded-3xl p-5 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-indigo-500/20 backdrop-blur-xl relative overflow-hidden shadow-sm dark:shadow-xl space-y-4 transition-colors">
                <div class="absolute -left-6 -bottom-6 w-32 h-32 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
                <div class="absolute -right-6 -top-6 w-32 h-32 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div id="profile-avatar" class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white flex items-center justify-center text-2xl font-bold shadow-lg shadow-indigo-500/20">
                            👤
                        </div>
                        <div>
                            <h2 class="font-bold text-base leading-tight" id="profile-name">کاربر گرامی</h2>
                            <p class="text-xs text-indigo-500 dark:text-indigo-400 font-mono mt-0.5 inline-block" dir="ltr" id="profile-username">@username</p>
                            <div class="flex items-center gap-1.5 mt-1 cursor-pointer" onclick="copyText(document.getElementById('profile-tgid').innerText.replace('ID: ', ''), 'شناسه عددی کپی شد')">
                                <span class="text-[11px] font-mono text-muted" dir="ltr" id="profile-tgid">ID: 00000000</span>
                                <svg class="w-3.5 h-3.5 text-muted hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <span id="profile-role-badge" class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-[11px] font-semibold">
                        عضو عادی
                    </span>
                </div>

                <!-- Balance Showcase -->
                <div class="app-card-solid rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-muted">موجودی کیف پول شما</div>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="text-2xl font-extrabold font-mono text-emerald-500" id="profile-balance">0</span>
                            <span class="text-xs text-muted">تومان</span>
                        </div>
                    </div>
                    <button onclick="switchTab('wallet')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 hover:opacity-90 active:scale-95 transition">
                        + شارژ موجودی
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="app-card rounded-2xl p-4 text-center">
                    <div class="text-2xl mb-1">🚀</div>
                    <div class="text-lg font-bold font-mono" id="profile-active-services">0</div>
                    <div class="text-xs text-muted mt-0.5">سرویس‌های فعال من</div>
                </div>
                <div class="app-card rounded-2xl p-4 text-center">
                    <div class="text-2xl mb-1">📅</div>
                    <div class="text-xs font-medium" id="profile-joined">امروز</div>
                    <div class="text-xs text-muted mt-0.5">تاریخ عضویت در ربات</div>
                </div>
            </div>

            <!-- Profile Menu Items -->
            <div class="app-card rounded-3xl divide-y divide-slate-100 dark:divide-slate-800/60 overflow-hidden">
                <div onclick="switchTab('shop')" class="p-4 flex items-center justify-between cursor-pointer hover:bg-subtle transition">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-sm">🛍</span>
                        <span class="text-xs font-medium">خرید اشتراک VPN جدید</span>
                    </div>
                    <span class="text-muted text-xs">←</span>
                </div>
                <div onclick="switchTab('services')" class="p-4 flex items-center justify-between cursor-pointer hover:bg-subtle transition">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-sm">📱</span>
                        <span class="text-xs font-medium">مشاهده کانفیگ‌ها و اکانت‌ها</span>
                    </div>
                    <span class="text-muted text-xs">←</span>
                </div>
                <div onclick="switchTab('wallet')" class="p-4 flex items-center justify-between cursor-pointer hover:bg-subtle transition">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-sm">💳</span>
                        <span class="text-xs font-medium">کیف پول و کارت به کارت</span>
                    </div>
                    <span class="text-muted text-xs">←</span>
                </div>
                <div onclick="showSupportModal()" class="p-4 flex items-center justify-between cursor-pointer hover:bg-subtle transition">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-sm">📞</span>
                        <span class="text-xs font-medium">ارتباط با پشتیبانی ۲۴ ساعته</span>
                    </div>
                    <span class="text-muted text-xs">←</span>
                </div>
            </div>
        </section>


        <!-- ============================================================== -->
        <!-- TAB 2: خرید سرویس جدید (SHOP WIZARD) [Tab 2]                   -->
        <!-- ============================================================== -->
        <section id="tab-shop" class="space-y-5 hidden">
            <!-- Wizard Step 1: Select Server -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-brand-500 text-white text-[11px] flex items-center justify-center">۱</span>
                        <span>مرحله اول: انتخاب لوکیشن و سرور</span>
                    </label>
                    <span class="text-[11px] text-muted" id="server-count-badge">0 سرور فعال</span>
                </div>
                <div id="servers-container" class="grid grid-cols-2 gap-2.5">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Wizard Step 2: Select Category -->
            <div id="categories-wrapper" class="space-y-2.5 hidden">
                <label class="text-xs font-bold flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full bg-brand-500 text-white text-[11px] flex items-center justify-center">۲</span>
                    <span>مرحله دوم: انتخاب دوره / دسته‌بندی</span>
                </label>
                <div id="categories-container" class="flex gap-2 overflow-x-auto pb-1.5">
                    <!-- Categories pills populated dynamically -->
                </div>
            </div>

            <!-- Wizard Step 3: Ready Plans vs Custom Service -->
            <div id="plans-wrapper" class="space-y-3.5 hidden">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-brand-500 text-white text-[11px] flex items-center justify-center">۳</span>
                        <span>مرحله سوم: انتخاب یا شخصی‌سازی پلن</span>
                    </label>
                    
                    <!-- Toggle Ready vs Custom -->
                    <div id="custom-service-toggle-wrap" class="flex items-center gap-1 p-0.5 rounded-xl bg-subtle text-xs border border-slate-200 dark:border-slate-800 hidden">
                        <button id="toggle-ready-plans" onclick="setPlanMode('ready')" class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition bg-brand-500 text-white shadow-sm">
                            پلن‌های آماده
                        </button>
                        <button id="toggle-custom-plan" onclick="setPlanMode('custom')" class="px-2.5 py-1 rounded-lg font-medium text-[11px] transition text-muted hover:text-brand-500">
                            سرویس دلخواه ⚙️
                        </button>
                    </div>
                </div>

                <!-- Mode A: Ready Plans List -->
                <div id="ready-plans-container" class="space-y-3">
                    <!-- Populated dynamically -->
                </div>

                <!-- Mode B: Custom Service Builder (Pasargad Style) -->
                <div id="custom-plan-container" class="rounded-3xl p-5 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-indigo-500/20 backdrop-blur-xl relative overflow-hidden shadow-sm dark:shadow-xl space-y-5 hidden transition-colors">
                    <div class="absolute -left-6 -bottom-6 w-32 h-32 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
                    <div class="text-center pb-2 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-bold text-sm">ساخت سرویس سفارشی شما</h3>
                        <p class="text-[11px] text-muted mt-1">حجم و زمان دقیق اشتراک را طبق نیاز خود تنظیم کنید</p>
                    </div>

                    <!-- Traffic Slider -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-700 dark:text-slate-300">حجم ترافیک مورد نظر (گیگابایت):</span>
                            <span class="font-mono font-bold text-sm text-indigo-600 dark:text-indigo-400" id="custom-traffic-val">20 GB</span>
                        </div>
                        <input type="range" id="custom-traffic-slider" min="5" max="300" step="5" value="20" class="w-full h-2 cursor-pointer" oninput="updateCustomCalculation()">
                        <div class="flex justify-between text-[10px] text-muted font-mono">
                            <span id="custom-traffic-min">5 GB</span>
                            <span id="custom-traffic-max">300 GB</span>
                        </div>

                        <!-- Direct Preset Buttons (Pasargad Style) -->
                        <div class="grid grid-cols-4 gap-2 pt-1">
                            <button type="button" onclick="setCustomTraffic(10)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">10 GB</button>
                            <button type="button" onclick="setCustomTraffic(25)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">25 GB</button>
                            <button type="button" onclick="setCustomTraffic(50)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">50 GB</button>
                            <button type="button" onclick="setCustomTraffic(100)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">100 GB</button>
                        </div>
                    </div>

                    <!-- Days Slider -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-700 dark:text-slate-300">مدت زمان اعتبار:</span>
                            <span class="font-mono font-bold text-sm text-indigo-600 dark:text-indigo-400" id="custom-days-val">30 روز</span>
                        </div>
                        <input type="range" id="custom-days-slider" min="1" max="180" step="1" value="30" class="w-full h-2 cursor-pointer" oninput="updateCustomCalculation()">
                        <div class="flex justify-between text-[10px] text-muted font-mono">
                            <span id="custom-days-min">1 روز</span>
                            <span id="custom-days-max">180 روز</span>
                        </div>

                        <!-- Direct Preset Buttons (Pasargad Style) -->
                        <div class="grid grid-cols-4 gap-2 pt-1">
                            <button type="button" onclick="setCustomDays(15)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">۱۵ روز</button>
                            <button type="button" onclick="setCustomDays(30)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">۳۰ روز</button>
                            <button type="button" onclick="setCustomDays(60)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">۶۰ روز</button>
                            <button type="button" onclick="setCustomDays(90)" class="py-1.5 px-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs font-semibold hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition-all">۹۰ روز</button>
                        </div>
                    </div>

                    <!-- Limitations summary -->
                    <div class="app-card-solid rounded-2xl p-3.5 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 text-muted">
                            <i class="fa-solid fa-users text-indigo-500"></i>
                            <span>محدودیت کاربر همزمان:</span>
                        </div>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">۱ کاربر فعال</span>
                    </div>

                    <!-- Price Breakdown & Action Button (Pasargad Style) -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 backdrop-blur-xl space-y-3 shadow-sm transition-colors">
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-200 dark:border-slate-800">
                            <span class="text-slate-500 dark:text-slate-400">مبلغ قابل پرداخت:</span>
                            <div class="flex items-baseline gap-1 font-mono">
                                <span class="text-xl font-black text-indigo-600 dark:text-indigo-400" id="custom-price-display">0</span>
                                <span class="text-[11px] text-muted">تومان</span>
                            </div>
                        </div>
                        <button onclick="openCheckoutModal(null, true)" class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                            <i class="fa-solid fa-cart-check"></i>
                            <span>ثبت و پرداخت سفارش</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>


        <!-- ============================================================== -->
        <!-- TAB 3: سرویس‌های من (MY SERVICES) [Tab 3]                       -->
        <!-- ============================================================== -->
        <section id="tab-services" class="space-y-4 hidden">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-sm">سرویس‌ها و کانفیگ‌های من</h2>
                    <p class="text-[11px] text-muted mt-0.5">مشاهده حجم باقی‌مانده، لینک‌ها و دانلود کانفیگ WireGuard</p>
                </div>
                <button onclick="loadAppData()" class="px-3 py-1.5 rounded-xl bg-subtle text-xs text-muted hover:text-brand-500 flex items-center gap-1 transition active:scale-95">
                    <span>🔄</span>
                    <span>بروزرسانی</span>
                </button>
            </div>

            <!-- Services List Container -->
            <div id="services-container" class="space-y-3.5">
                <!-- Populated dynamically -->
            </div>
        </section>


        <!-- ============================================================== -->
        <!-- TAB 4: کیف پول (WALLET & RECHARGE) [Leftmost Tab 4]            -->
        <!-- ============================================================== -->
        <section id="tab-wallet" class="space-y-4 hidden">
            <!-- Wallet Balance Card -->
            <div class="app-card rounded-3xl p-6 bg-gradient-to-br from-indigo-600 via-indigo-700 to-brand-700 text-white shadow-xl relative overflow-hidden space-y-3">
                <div class="flex items-center justify-between text-xs opacity-90">
                    <span>موجودی فعلی کیف پول</span>
                    <span>VPN WALLET 💳</span>
                </div>
                <div class="flex items-baseline gap-2 py-1">
                    <span class="text-3xl font-black font-mono tracking-tight" id="wallet-balance-val">0</span>
                    <span class="text-xs font-normal opacity-80">تومان</span>
                </div>
                <p class="text-[11px] opacity-80 leading-relaxed">
                    با شارژ کیف پول خود، می‌توانید تمام خریدهای آینده را به‌صورت آنی و خودکار تحویل بگیرید.
                </p>
            </div>

            <!-- Bank Card Info Card -->
            <div class="app-card rounded-3xl p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-xs flex items-center gap-1.5">
                        <span>💳</span>
                        <span>شماره کارت فعال جهت واریز</span>
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-500 font-medium">مورد تایید</span>
                </div>

                <div class="app-card-solid rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted">بانک مقصد:</span>
                        <span class="text-xs font-bold" id="card-bank-name">بانک ملی ایران</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted">به نام:</span>
                        <span class="text-xs font-bold" id="card-holder-name">مدیریت سرور</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] text-muted">شماره کارت (لمس جهت کپی):</div>
                            <div class="font-mono font-bold text-sm tracking-wider mt-0.5" id="card-number-val">6037-9974-1234-5678</div>
                        </div>
                        <button onclick="copyCardNumber()" class="px-3 py-1.5 rounded-xl bg-brand-500 text-white text-xs font-semibold shadow hover:bg-brand-600 transition active:scale-95">
                            کپی کارت 📋
                        </button>
                    </div>
                </div>

                <!-- Upload Receipt Form -->
                <form id="wallet-recharge-form" onsubmit="handleWalletRecharge(event)" class="space-y-3 pt-2">
                    <div>
                        <label class="block text-xs text-muted mb-1 font-medium">مبلغ واریز شده (تومان):</label>
                        <input type="number" id="wallet-amount" required placeholder="مثلا: 100000" class="app-input w-full rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs text-muted mb-1 font-medium">تصویر رسید واریزی یا شماره پیگیری:</label>
                        <input type="file" id="wallet-receipt-file" accept="image/*" class="app-input w-full rounded-xl p-2 text-xs focus:outline-none file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-brand-500 file:text-white">
                        <input type="text" id="wallet-receipt-text" placeholder="یا کد پیگیری واریز را اینجا بنویسید" class="app-input w-full rounded-xl px-4 py-2 text-xs mt-2 focus:outline-none">
                    </div>

                    <button type="submit" id="btn-submit-wallet" class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>ارسال فیش واریز جهت تایید</span>
                    </button>
                </form>
            </div>
        </section>

    </main>


    <!-- ============================================================== -->
    <!-- FLOATING ROUNDED BOTTOM NAVIGATION BAR (PASARGAD STYLE)        -->
    <!-- ============================================================== -->
    <nav class="fixed bottom-4 left-4 right-4 max-w-md mx-auto z-50 rounded-3xl p-1.5 bg-white/95 dark:bg-slate-950/95 backdrop-blur-2xl border border-slate-200 dark:border-slate-800 shadow-2xl shadow-indigo-950/10 dark:shadow-black/60 transition-all">
        <div class="grid grid-cols-4 gap-1 text-center">
            <!-- Tab 1: Profile (Rightmost) -->
            <button onclick="switchTab('profile')" id="nav-btn-profile" class="tab-btn active flex flex-col items-center justify-center py-2 px-1 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                <i class="fa-solid fa-user text-base mb-1"></i>
                <span class="text-[11px] font-bold">حساب من</span>
            </button>

            <!-- Tab 2: Shop -->
            <button onclick="switchTab('shop')" id="nav-btn-shop" class="tab-btn flex flex-col items-center justify-center py-2 px-1 rounded-2xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white">
                <i class="fa-solid fa-cart-shopping text-base mb-1"></i>
                <span class="text-[11px] font-semibold">خرید سرویس</span>
            </button>

            <!-- Tab 3: My Services -->
            <button onclick="switchTab('services')" id="nav-btn-services" class="tab-btn flex flex-col items-center justify-center py-2 px-1 rounded-2xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white">
                <i class="fa-solid fa-shield-halved text-base mb-1"></i>
                <span class="text-[11px] font-semibold">سرویس‌های من</span>
            </button>

            <!-- Tab 4: Wallet (Leftmost) -->
            <button onclick="switchTab('wallet')" id="nav-btn-wallet" class="tab-btn flex flex-col items-center justify-center py-2 px-1 rounded-2xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white">
                <i class="fa-solid fa-wallet text-base mb-1"></i>
                <span class="text-[11px] font-semibold">کیف پول</span>
            </button>
        </div>
    </nav>


    <!-- ============================================================== -->
    <!-- CHECKOUT MODAL                                                 -->
    <!-- ============================================================== -->
    <div id="checkoutModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="app-card rounded-t-3xl sm:rounded-3xl w-full max-w-md p-5 space-y-4 animate-in slide-in-from-bottom duration-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-sm" id="checkout-title">تایید نهایی و پرداخت سفارش</h3>
                <button onclick="closeCheckoutModal()" class="w-7 h-7 rounded-full bg-subtle flex items-center justify-center text-muted hover:text-black dark:hover:text-white">✕</button>
            </div>

            <!-- Order Summary Details -->
            <div class="app-card-solid rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-muted">سرور انتخابی:</span>
                    <span class="font-bold" id="checkout-server-name">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">پلن / دوره:</span>
                    <span class="font-bold" id="checkout-plan-name">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">حجم و زمان:</span>
                    <span class="font-bold font-mono" id="checkout-traffic-days">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">محدودیت کاربر (IP / HWID):</span>
                    <span class="font-bold text-brand-500" id="checkout-limits">-</span>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-between items-baseline">
                    <span class="font-bold text-muted">مبلغ کل قابل پرداخت:</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-black font-mono text-emerald-500" id="checkout-price">-</span>
                        <span class="text-[10px] text-muted">تومان</span>
                    </div>
                </div>
            </div>

            <!-- Payment Method Choice -->
            <div class="space-y-2">
                <label class="text-xs text-muted font-medium">روش پرداخت:</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="setCheckoutPaymentMethod('wallet')" id="btn-pay-method-wallet" class="p-3 rounded-2xl border-2 border-brand-500 bg-brand-500/10 text-brand-500 text-xs font-bold flex flex-col items-center gap-1">
                        <span>⚡ پرداخت آنی با کیف پول</span>
                        <span class="text-[10px] font-normal opacity-80" id="checkout-wallet-balance-note">موجودی: 0 تومان</span>
                    </button>
                    <button type="button" onclick="setCheckoutPaymentMethod('card')" id="btn-pay-method-card" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 text-muted text-xs font-medium flex flex-col items-center gap-1">
                        <span>💳 کارت به کارت</span>
                        <span class="text-[10px] font-normal opacity-80">ارسال تصویر فیش</span>
                    </button>
                    <button type="button" onclick="setCheckoutPaymentMethod('admin_test')" id="btn-pay-method-admin" class="col-span-2 p-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 text-amber-500 text-xs font-bold items-center justify-center gap-1.5 hidden">
                        <span>👑 خرید تستی مدیر (تحویل فوری بدون کسر موجودی)</span>
                    </button>
                </div>
            </div>

            <!-- Card To Card Extra Inputs (Only when method is 'card') -->
            <div id="checkout-card-extra" class="space-y-3 pt-2 hidden">
                <div class="app-card-solid rounded-xl p-3 text-xs flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-muted" id="checkout-bank-holder">بانک ملی - مدیریت سرور</div>
                        <div class="font-mono font-bold mt-0.5" id="checkout-card-num">6037-9974-1234-5678</div>
                    </div>
                    <button type="button" onclick="copyCardNumber()" class="px-2.5 py-1 rounded-lg bg-brand-500 text-white text-[11px]">کپی</button>
                </div>
                <div>
                    <label class="block text-[11px] text-muted mb-1">تصویر رسید یا کد پیگیری:</label>
                    <input type="file" id="checkout-receipt-file" accept="image/*" class="app-input w-full rounded-xl p-1.5 text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-brand-500 file:text-white">
                    <input type="text" id="checkout-receipt-text" placeholder="یا متن پیگیری تراکنش" class="app-input w-full rounded-xl px-3 py-1.5 text-xs mt-1.5">
                </div>
            </div>

            <div class="pt-2">
                <button type="button" id="btn-confirm-checkout" onclick="confirmOrder()" class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                    <i class="fa-solid fa-cart-check"></i>
                    <span>تایید و ثبت نهایی سفارش</span>
                </button>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- QR CODE & WIREGUARD CONF MODAL                                 -->
    <!-- ============================================================== -->
    <div id="qrModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="app-card rounded-3xl w-full max-w-sm p-5 space-y-4 text-center">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-sm" id="qr-modal-title">اتصال سریع با کد QR</h3>
                <button onclick="closeQrModal()" class="w-7 h-7 rounded-full bg-subtle flex items-center justify-center text-muted hover:text-black dark:hover:text-white">✕</button>
            </div>

            <!-- QR Container -->
            <div class="p-4 bg-white rounded-2xl inline-block mx-auto shadow-inner">
                <div id="qrcode-box" class="flex items-center justify-center"></div>
            </div>

            <p class="text-xs text-muted leading-relaxed" id="qr-modal-desc">
                کد بالا را با نرم‌افزار v2rayNG یا Streisand یا Hiddify اسکن کنید.
            </p>

            <div class="flex flex-col gap-2 pt-2">
                <button type="button" id="btn-copy-from-modal" class="w-full py-2.5 rounded-xl bg-brand-600 text-white text-xs font-semibold hover:bg-brand-500 transition">
                    کپی لینک اتصال 📋
                </button>
                <button type="button" id="btn-download-conf-modal" class="w-full py-2.5 rounded-xl bg-purple-600 text-white text-xs font-semibold hover:bg-purple-500 transition hidden">
                    📥 دانلود فایل کانفیگ وایرگارد (.conf)
                </button>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- SUPPORT MODAL                                                  -->
    <!-- ============================================================== -->
    <div id="supportModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="app-card rounded-3xl w-full max-w-sm p-5 space-y-4 text-center">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center mx-auto text-2xl">
                📞
            </div>
            <h3 class="font-bold text-sm">ارتباط با پشتیبانی تلگرام</h3>
            <p class="text-xs text-muted leading-relaxed" id="support-modal-text">
                در صورت بروز هرگونه سوال یا مشکل در اتصال، کارشناسان ما آماده پاسخگویی هستند.
            </p>
            <div class="pt-2 flex flex-col gap-2">
                <a id="btn-open-support-tg" href="#" target="_blank" class="w-full py-2.5 rounded-xl bg-brand-600 text-white text-xs font-semibold hover:bg-brand-500 transition">
                    ارسال پیام به پشتیبان در تلگرام
                </a>
                <button type="button" onclick="closeSupportModal()" class="w-full py-2 rounded-xl bg-subtle text-muted text-xs hover:text-black dark:hover:text-white">
                    بستن
                </button>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- TOAST NOTIFICATION CONTAINER                                   -->
    <!-- ============================================================== -->
    <div id="toast" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-2xl text-xs font-bold shadow-2xl flex items-center gap-2 transition-all duration-300 pointer-events-none opacity-0 -translate-y-4">
        <span id="toast-icon">ℹ️</span>
        <span id="toast-text">پیام سیستم</span>
    </div>


    <!-- ============================================================== -->
    <!-- JAVASCRIPT APP LOGIC                                           -->
    <!-- ============================================================== -->
    <script>
        // State variables
        let tg = window.Telegram?.WebApp;
        let currentUser = null;
        let appData = { panels: [], plans: [], cards: [], services: [] };
        let selectedPanel = null;
        let selectedCategory = null;
        let selectedPlan = null;
        let planMode = 'ready'; // 'ready' or 'custom'
        let checkoutPayMethod = 'wallet';
        let isCustomCheckout = false;

        // Robust Telegram User identification
        function getTelegramUser() {
            // 1. Try URL parameters (?user_id=... or ?telegram_id=... or ?tg_id=... or ?id=...)
            const urlParams = new URLSearchParams(window.location.search);
            const tgId = urlParams.get('user_id') || urlParams.get('telegram_id') || urlParams.get('tg_id') || urlParams.get('id');
            if (tgId && tgId !== '0') {
                const u = {
                    id: tgId,
                    first_name: urlParams.get('first_name') || 'کاربر',
                    username: urlParams.get('username') || ''
                };
                try { localStorage.setItem('vpn_miniapp_user', JSON.stringify(u)); } catch(e) {}
                return u;
            }

            // 2. Try window.Telegram.WebApp.initDataUnsafe
            if (window.Telegram?.WebApp?.initDataUnsafe?.user?.id) {
                const u = window.Telegram.WebApp.initDataUnsafe.user;
                try { localStorage.setItem('vpn_miniapp_user', JSON.stringify(u)); } catch(e) {}
                return u;
            }

            // 3. Try parsing Telegram initData string
            if (window.Telegram?.WebApp?.initData) {
                try {
                    const p = new URLSearchParams(window.Telegram.WebApp.initData);
                    const userStr = p.get('user');
                    if (userStr) {
                        let u = null;
                        try { u = JSON.parse(userStr); } catch(e1) {
                            try { u = JSON.parse(decodeURIComponent(userStr)); } catch(e2) {}
                        }
                        if (u && u.id) {
                            try { localStorage.setItem('vpn_miniapp_user', JSON.stringify(u)); } catch(e) {}
                            return u;
                        }
                    }
                } catch(e) {}
            }

            // 4. Try parsing hash fragment (#tgWebAppData=...)
            if (window.location.hash) {
                try {
                    const hashParams = new URLSearchParams(window.location.hash.substring(1));
                    const rawData = hashParams.get('tgWebAppData');
                    if (rawData) {
                        const subParams = new URLSearchParams(rawData);
                        const userStr = subParams.get('user');
                        if (userStr) {
                            let u = null;
                            try { u = JSON.parse(userStr); } catch(e1) {
                                try { u = JSON.parse(decodeURIComponent(userStr)); } catch(e2) {}
                            }
                            if (u && u.id) {
                                try { localStorage.setItem('vpn_miniapp_user', JSON.stringify(u)); } catch(e) {}
                                return u;
                            }
                        }
                    }
                } catch(e) {}
            }

            // 5. Try sessionStorage initParams (saved by Telegram WebView)
            try {
                const sp = sessionStorage.getItem('initParams');
                if (sp) {
                    const parsedSp = JSON.parse(sp);
                    if (parsedSp?.user?.id) {
                        return parsedSp.user;
                    }
                    if (parsedSp?.tgWebAppData) {
                        const subParams = new URLSearchParams(parsedSp.tgWebAppData);
                        const userStr = subParams.get('user');
                        if (userStr) {
                            let u = null;
                            try { u = JSON.parse(userStr); } catch(e1) {
                                try { u = JSON.parse(decodeURIComponent(userStr)); } catch(e2) {}
                            }
                            if (u && u.id) return u;
                        }
                    }
                }
            } catch(e) {}

            // 6. Try cached user from localStorage
            try {
                const cached = localStorage.getItem('vpn_miniapp_user');
                if (cached) {
                    const u = JSON.parse(cached);
                    if (u && u.id && u.id !== '0') return u;
                }
            } catch(e) {}

            return null;
        }

        // Initialize Telegram WebApp
        if (tg) {
            try {
                tg.ready();
                tg.expand();
            } catch(e) {}
        }

        // Theme management
        const savedTheme = localStorage.getItem('vpn_miniapp_theme') || (tg?.colorScheme === 'light' ? 'light' : 'dark');
        setTheme(savedTheme);

        function setTheme(theme) {
            haptic('impact');
            if (theme === 'light') {
                document.documentElement.classList.remove('theme-dark', 'dark');
                document.documentElement.classList.add('theme-light');
                localStorage.setItem('vpn_miniapp_theme', 'light');
            } else {
                document.documentElement.classList.remove('theme-light');
                document.documentElement.classList.add('theme-dark', 'dark');
                localStorage.setItem('vpn_miniapp_theme', 'dark');
            }
            updateThemeSwitcherUI(theme);
            if (typeof updateSliderTrack === 'function') {
                updateSliderTrack('custom-traffic-slider');
                updateSliderTrack('custom-days-slider');
            }
        }

        function updateThemeSwitcherUI(mode) {
            const isDark = (mode === 'dark');
            const bLight = document.getElementById('themeBtnLight');
            const bDark = document.getElementById('themeBtnDark');
            if (bLight && bDark) {
                if (isDark) {
                    bLight.className = 'flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[11px] transition-all font-medium text-zinc-400 hover:text-white';
                    bDark.className = 'flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[11px] transition-all font-bold bg-zinc-800 text-white shadow-sm';
                } else {
                    bLight.className = 'flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[11px] transition-all font-bold bg-white text-slate-900 shadow-sm';
                    bDark.className = 'flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[11px] transition-all font-medium text-slate-500 hover:text-black';
                }
            }
        }

        function haptic(type = 'impact') {
            try {
                if (tg && tg.HapticFeedback) {
                    if (type === 'impact') tg.HapticFeedback.impactOccurred('medium');
                    else if (type === 'success') tg.HapticFeedback.notificationOccurred('success');
                    else if (type === 'error') tg.HapticFeedback.notificationOccurred('error');
                }
            } catch (e) {}
        }

        function showToast(message, type = 'info') {
            haptic(type === 'error' ? 'error' : 'success');
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const text = document.getElementById('toast-text');

            text.innerText = message;
            if (type === 'success') {
                icon.innerText = '✅';
                toast.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-2xl text-xs font-bold shadow-2xl flex items-center gap-2 transition-all duration-300 pointer-events-none bg-emerald-600 text-white opacity-100 translate-y-0';
            } else if (type === 'error') {
                icon.innerText = '⚠️';
                toast.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-2xl text-xs font-bold shadow-2xl flex items-center gap-2 transition-all duration-300 pointer-events-none bg-rose-600 text-white opacity-100 translate-y-0';
            } else {
                icon.innerText = 'ℹ️';
                toast.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-2xl text-xs font-bold shadow-2xl flex items-center gap-2 transition-all duration-300 pointer-events-none bg-indigo-600 text-white opacity-100 translate-y-0';
            }

            setTimeout(() => {
                toast.classList.add('opacity-0', '-translate-y-4');
                toast.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }

        function copyText(str, msg = 'کپی شد') {
            if (!str) return;
            navigator.clipboard.writeText(str).then(() => {
                showToast(msg, 'success');
            }).catch(() => {
                showToast(msg, 'success');
            });
        }

        // --- NAVIGATION TABS (PASARGAD STYLE) ---
        function switchTab(tab) {
            haptic('impact');
            const tabs = ['profile', 'shop', 'services', 'wallet'];
            tabs.forEach(t => {
                const el = document.getElementById('tab-' + t);
                const btn = document.getElementById('nav-btn-' + t);
                if (!el || !btn) return;
                if (t === tab) {
                    el.classList.remove('hidden');
                    btn.className = 'tab-btn active flex flex-col items-center justify-center py-2 px-1 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold';
                } else {
                    el.classList.add('hidden');
                    btn.className = 'tab-btn flex flex-col items-center justify-center py-2 px-1 rounded-2xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white font-semibold';
                }
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // --- LOAD DATA FROM API ---
        async function loadAppData() {
            let tgUser = getTelegramUser();
            let tgId = tgUser?.id || '';

            // Gate Check: If opened directly in a regular web browser without Telegram identification
            const hasTelegramContext = (window.Telegram?.WebApp?.initData && window.Telegram.WebApp.initData.length > 5) || 
                                       (window.Telegram?.WebApp?.initDataUnsafe?.user?.id) || 
                                       (tgId && tgId !== '' && tgId !== '0');

            if (!hasTelegramContext) {
                const overlay = document.getElementById('telegram-gate-overlay');
                if (overlay) {
                    overlay.classList.remove('hidden');
                    const botLink = document.getElementById('telegram-bot-link');
                    if (botLink) {
                        const botUser = '<?= htmlspecialchars($settings['bot_username'] ?? '') ?>';
                        botLink.href = botUser ? `https://t.me/${botUser}` : 'tg://resolve';
                    }
                }
                return;
            }

            // Immediate optimistic UI render to avoid placeholder flicker
            if (tgUser && tgUser.id) {
                const pName = document.getElementById('profile-name');
                const pUser = document.getElementById('profile-username');
                const pId   = document.getElementById('profile-tgid');
                const hText = document.getElementById('header-user-text');
                if (pName && tgUser.first_name) pName.innerText = tgUser.first_name;
                if (pUser) pUser.innerText = tgUser.username ? ('@' + tgUser.username) : '@user';
                if (pId) pId.innerText = 'ID: ' + tgUser.id;
                if (hText && tgUser.first_name) hText.innerText = tgUser.first_name + ' خوش آمدید';
            }

            try {
                const query = new URLSearchParams();
                const init_data = window.Telegram?.WebApp?.initData || '';
                if (init_data) query.set('init_data', init_data);
                if (tgId) query.set('telegram_id', tgId);
                if (tgUser?.first_name) query.set('first_name', tgUser.first_name);
                if (tgUser?.username) query.set('username', tgUser.username);

                const res = await fetch('/api/miniapp/data?' + query.toString());
                const data = await res.json();

                if (data.success) {
                    appData = data;
                    currentUser = data.user;

                    // If server did not recognize user, show access gate
                    if (!currentUser) {
                        const overlay = document.getElementById('telegram-gate-overlay');
                        if (overlay) {
                            overlay.classList.remove('hidden');
                        }
                        return;
                    }

                    // Header & User Info
                    const isAdmin = currentUser?.is_admin || appData.is_admin;

                    if (currentUser) {
                        document.getElementById('header-user-text').innerText = (isAdmin ? '👑 مدیر گرامی ' : '') + (currentUser.first_name || 'کاربر') + ' خوش آمدید';
                        document.getElementById('header-balance').innerText = Number(currentUser.balance).toLocaleString();
                        document.getElementById('profile-name').innerText = currentUser.first_name || (isAdmin ? 'مدیر ارشد' : 'کاربر گرامی');
                        document.getElementById('profile-username').innerText = currentUser.username ? ('@' + currentUser.username) : (isAdmin ? '@admin' : '@user');
                        document.getElementById('profile-tgid').innerText = 'ID: ' + currentUser.telegram_id;
                        document.getElementById('profile-balance').innerText = Number(currentUser.balance).toLocaleString();
                        document.getElementById('wallet-balance-val').innerText = Number(currentUser.balance).toLocaleString();
                        document.getElementById('checkout-wallet-balance-note').innerText = 'موجودی: ' + Number(currentUser.balance).toLocaleString() + ' تومان';
                        
                        const roleBadge = document.getElementById('profile-role-badge');
                        if (roleBadge) {
                            if (isAdmin) {
                                roleBadge.className = 'px-3 py-1 rounded-full bg-amber-500/15 text-amber-500 border border-amber-500/30 text-[11px] font-bold flex items-center gap-1 shadow-sm';
                                roleBadge.innerHTML = '👑 مدیر کل';
                            } else if (Number(currentUser.balance) > 0) {
                                roleBadge.className = 'px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 text-[11px] font-semibold';
                                roleBadge.innerHTML = '⭐ عضو ویژه';
                            } else {
                                roleBadge.className = 'px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-[11px] font-semibold';
                                roleBadge.innerHTML = 'عضو عادی';
                            }
                        }

                        if (currentUser.created_at) {
                            document.getElementById('profile-joined').innerText = currentUser.created_at.split(' ')[0];
                        }
                    } else if (tgUser) {
                        document.getElementById('header-user-text').innerText = (tgUser.first_name || 'کاربر') + ' خوش آمدید';
                        document.getElementById('profile-name').innerText = tgUser.first_name || 'کاربر گرامی';
                        document.getElementById('profile-username').innerText = tgUser.username ? ('@' + tgUser.username) : '@user';
                        document.getElementById('profile-tgid').innerText = 'ID: ' + tgUser.id;
                    } else if (data.auth === false) {
                        document.getElementById('header-user-text').innerText = 'حالت پیش‌نمایش';
                        document.getElementById('profile-name').innerText = 'کاربر مهمان';
                        document.getElementById('profile-username').innerText = '@user';
                        document.getElementById('profile-tgid').innerText = 'ID: -';
                        const roleBadge = document.getElementById('profile-role-badge');
                        if (roleBadge) {
                            roleBadge.className = 'px-3 py-1 rounded-full bg-slate-500/15 text-slate-400 border border-slate-500/30 text-[11px] font-bold flex items-center gap-1 shadow-sm';
                            roleBadge.innerHTML = '👀 پیش‌نمایش (ورود از ربات)';
                        }
                    }

                    // Active Services count
                    document.getElementById('profile-active-services').innerText = data.services?.length || 0;

                    // Card Info
                    if (data.cards && data.cards.length > 0) {
                        const activeCard = data.cards[0];
                        document.getElementById('card-bank-name').innerText = activeCard.bank_name || 'بانک عضو شتاب';
                        document.getElementById('card-holder-name').innerText = activeCard.holder_name || 'مدیریت سرور';
                        document.getElementById('card-number-val').innerText = formatCardNumber(activeCard.card_number);
                        document.getElementById('checkout-bank-holder').innerText = (activeCard.bank_name || '') + ' - ' + (activeCard.holder_name || '');
                        document.getElementById('checkout-card-num').innerText = formatCardNumber(activeCard.card_number);
                    }

                    // Render Servers
                    renderServers(data.panels || []);

                    // Render Services
                    renderServices(data.services || []);
                }
            } catch (err) {
                console.error("Data load failed", err);
            }
        }

        function formatCardNumber(num) {
            if (!num) return '';
            const cleaned = num.replace(/\D/g, '');
            return cleaned.match(/.{1,4}/g)?.join('-') || num;
        }

        function copyCardNumber() {
            const raw = document.getElementById('card-number-val').innerText.replace(/-/g, '');
            copyText(raw, 'شماره کارت جهت واریز کپی شد 📋');
        }

        // --- RENDER SERVERS (STEP 1) ---
        function renderServers(panels) {
            const container = document.getElementById('servers-container');
            document.getElementById('server-count-badge').innerText = panels.length + ' سرور فعال';
            container.innerHTML = '';

            if (panels.length === 0) {
                container.innerHTML = '<div class="col-span-2 p-6 text-center text-xs text-muted app-card rounded-2xl">در حال حاضر سرور فعالی موجود نیست.</div>';
                return;
            }

            panels.forEach((p, idx) => {
                const isSelected = selectedPanel?.id === p.id || (!selectedPanel && idx === 0);
                if (!selectedPanel && idx === 0) selectedPanel = p;

                const card = document.createElement('div');
                card.className = `app-card rounded-2xl p-3.5 cursor-pointer transition-all hover:scale-[1.02] active:scale-95 border-2 ${isSelected ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-transparent'}`;
                card.onclick = () => selectServer(p);

                // Detect flag / icon
                let flag = '🌐';
                const lower = p.name.toLowerCase();
                if (lower.includes('آلمان') || lower.includes('germany') || lower.includes('de')) flag = '🇩🇪';
                else if (lower.includes('هلند') || lower.includes('netherland') || lower.includes('nl')) flag = '🇳🇱';
                else if (lower.includes('فرانسه') || lower.includes('france') || lower.includes('fr')) flag = '🇫🇷';
                else if (lower.includes('فنلاند') || lower.includes('finland') || lower.includes('fi')) flag = '🇫🇮';
                else if (lower.includes('انگلیس') || lower.includes('uk') || lower.includes('london')) flag = '🇬🇧';
                else if (lower.includes('آمریکا') || lower.includes('usa') || lower.includes('us')) flag = '🇺🇸';
                else if (lower.includes('ترکیه') || lower.includes('turkey') || lower.includes('tr')) flag = '🇹🇷';

                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">${flag}</span>
                        <span class="flex items-center gap-1 text-[10px] text-emerald-500 font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span>آنلاین</span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <h4 class="font-bold text-xs leading-snug line-clamp-1">${escapeHtml(p.name)}</h4>
                        <div class="flex items-center gap-1 mt-1 text-[10px] text-muted">
                            <span>پروتکل:</span>
                            <span class="font-mono text-brand-500 uppercase">${p.type}</span>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });

            if (selectedPanel) {
                renderCategories(selectedPanel);
            }
        }

        function selectServer(panel) {
            haptic('impact');
            selectedPanel = panel;
            renderServers(appData.panels || []);
            renderCategories(panel);
        }

        // --- RENDER CATEGORIES (STEP 2) ---
        function renderCategories(panel) {
            const wrapper = document.getElementById('categories-wrapper');
            const container = document.getElementById('categories-container');
            wrapper.classList.remove('hidden');
            container.innerHTML = '';

            const cats = panel.categories || ['یک ماهه', 'دو ماهه', 'سه ماهه', 'نامحدود'];
            if (!selectedCategory || !cats.includes(selectedCategory)) {
                selectedCategory = cats[0] || 'یک ماهه';
            }

            cats.forEach(c => {
                const isSelected = selectedCategory === c;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition active:scale-95 ${isSelected ? 'bg-brand-600 text-white shadow-md shadow-indigo-500/20' : 'app-card text-muted hover:text-black dark:hover:text-white'}`;
                btn.innerText = c;
                btn.onclick = () => {
                    haptic('impact');
                    selectedCategory = c;
                    renderCategories(panel);
                };
                container.appendChild(btn);
            });

            // Handle Custom Service Toggle Button Visibility
            const customToggle = document.getElementById('custom-service-toggle-wrap');
            if (panel.custom_service_enabled) {
                customToggle.classList.remove('hidden');
                // Set slider limits
                document.getElementById('custom-traffic-slider').min = panel.min_gb || 5;
                document.getElementById('custom-traffic-slider').max = panel.max_gb || 500;
                document.getElementById('custom-traffic-min').innerText = (panel.min_gb || 5) + ' GB';
                document.getElementById('custom-traffic-max').innerText = (panel.max_gb || 500) + ' GB';

                document.getElementById('custom-days-slider').min = panel.min_days || 1;
                document.getElementById('custom-days-slider').max = panel.max_days || 365;
                document.getElementById('custom-days-min').innerText = (panel.min_days || 1) + ' روز';
                document.getElementById('custom-days-max').innerText = (panel.max_days || 365) + ' روز';

                updateCustomCalculation();
            } else {
                customToggle.classList.add('hidden');
                setPlanMode('ready');
            }

            renderPlans();
        }

        function setPlanMode(mode) {
            haptic('impact');
            planMode = mode;
            const btnReady = document.getElementById('toggle-ready-plans');
            const btnCustom = document.getElementById('toggle-custom-plan');
            const readyContainer = document.getElementById('ready-plans-container');
            const customContainer = document.getElementById('custom-plan-container');

            if (mode === 'custom') {
                btnCustom.className = 'px-2.5 py-1 rounded-lg font-medium text-[11px] transition bg-indigo-600 text-white shadow-sm';
                btnReady.className = 'px-2.5 py-1 rounded-lg font-medium text-[11px] transition text-muted hover:text-indigo-600';
                readyContainer.classList.add('hidden');
                customContainer.classList.remove('hidden');
                updateCustomCalculation();
            } else {
                btnReady.className = 'px-2.5 py-1 rounded-lg font-medium text-[11px] transition bg-indigo-600 text-white shadow-sm';
                btnCustom.className = 'px-2.5 py-1 rounded-lg font-medium text-[11px] transition text-muted hover:text-indigo-600';
                readyContainer.classList.remove('hidden');
                customContainer.classList.add('hidden');
            }
        }

        function updateSliderTrack(sliderId) {
            const slider = document.getElementById(sliderId);
            if (!slider) return;
            const min = Number(slider.min) || 1;
            const max = Number(slider.max) || 100;
            const val = Number(slider.value) || min;
            const pct = Math.max(0, Math.min(100, ((val - min) / (max - min)) * 100));

            const isDark = document.documentElement.classList.contains('dark') || document.documentElement.classList.contains('theme-dark');
            const uncolored = isDark ? 'rgba(30, 41, 59, 0.85)' : 'rgba(226, 232, 240, 0.9)';
            slider.style.background = `linear-gradient(to left, #6366f1 0%, #8b5cf6 ${pct}%, ${uncolored} ${pct}%, ${uncolored} 100%)`;
        }

        function setCustomTraffic(gb) {
            haptic('selection');
            const slider = document.getElementById('custom-traffic-slider');
            if (slider) {
                slider.value = gb;
                updateCustomCalculation();
            }
        }

        function setCustomDays(days) {
            haptic('selection');
            const slider = document.getElementById('custom-days-slider');
            if (slider) {
                slider.value = days;
                updateCustomCalculation();
            }
        }

        function updateCustomCalculation() {
            if (!selectedPanel) return;
            const vol = parseInt(document.getElementById('custom-traffic-slider').value);
            const days = parseInt(document.getElementById('custom-days-slider').value);

            document.getElementById('custom-traffic-val').innerText = vol + ' GB';
            document.getElementById('custom-days-val').innerText = days + ' روز';

            updateSliderTrack('custom-traffic-slider');
            updateSliderTrack('custom-days-slider');

            const pricePerGb = selectedPanel.price_per_gb || 4000;
            const pricePerDay = selectedPanel.price_per_day || 1000;
            const total = (vol * pricePerGb) + (days * pricePerDay);

            document.getElementById('custom-price-display').innerText = Number(total).toLocaleString();
        }

        // --- RENDER READY PLANS (STEP 3) ---
        function renderPlans() {
            const wrapper = document.getElementById('plans-wrapper');
            const container = document.getElementById('ready-plans-container');
            wrapper.classList.remove('hidden');
            container.innerHTML = '';

            const allPlans = appData.plans || [];
            // Filter plans matching category or panel
            const filtered = allPlans.filter(p => {
                const matchPanel = (p.panel_id == 0 || p.panel_id == selectedPanel?.id);
                const matchCat = (!p.category || p.category === selectedCategory || selectedCategory === 'همه');
                return matchPanel && matchCat;
            });

            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="app-card rounded-2xl p-6 text-center space-y-2">
                        <div class="text-2xl">📋</div>
                        <div class="text-xs font-semibold">پلن آماده‌ای در این دسته‌بندی یافت نشد</div>
                        <p class="text-[11px] text-muted">می‌توانید از بخش «سرویس دلخواه» پلن اختصاصی خود را بسازید.</p>
                    </div>
                `;
                return;
            }

            filtered.forEach(p => {
                const card = document.createElement('div');
                card.className = "app-card rounded-3xl p-4 transition-all hover:scale-[1.01] flex items-center justify-between gap-3";

                const ipLimitText = (p.ip_limit > 0) ? (p.ip_limit + ' کاربر') : 'نامحدود';
                const hwidLimitText = (p.hwid_limit > 0) ? (p.hwid_limit + ' دستگاه') : 'نامحدود';

                card.innerHTML = `
                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-xs text-brand-600 dark:text-brand-400">${escapeHtml(p.name)}</h4>
                            <span class="px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-500 text-[10px] font-mono">${p.traffic_gb} GB</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 text-[10px]">
                            <span class="px-2 py-0.5 rounded-md bg-subtle text-muted">⏳ ${p.days} روز اعتبار</span>
                            <span class="px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-500">🌐 IP: ${ipLimitText}</span>
                            <span class="px-2 py-0.5 rounded-md bg-purple-500/10 text-purple-500">📱 HWID: ${hwidLimitText}</span>
                        </div>
                    </div>
                    <div class="text-left space-y-1">
                        <div class="text-sm font-black font-mono text-emerald-500">${Number(p.price).toLocaleString()} <span class="text-[9px] font-normal text-muted">تومان</span></div>
                        <button onclick='openCheckoutModal(${JSON.stringify(p)}, false)' class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-xs shadow-md shadow-indigo-600/30 active:scale-95 transition">
                            خرید ⚡
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });
        }


        // --- RENDER MY SERVICES (TAB 3) ---
        function renderServices(services) {
            const container = document.getElementById('services-container');
            container.innerHTML = '';

            if (services.length === 0) {
                container.innerHTML = `
                    <div class="app-card rounded-3xl p-8 text-center space-y-3">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-brand-500 flex items-center justify-center mx-auto text-3xl">
                            🛰️
                        </div>
                        <h3 class="font-bold text-sm">هیچ اشتراک فعالی ندارید</h3>
                        <p class="text-xs text-muted leading-relaxed max-w-xs mx-auto">
                            شما هنوز سرویسی خریداری نکرده‌اید. با خرید اولین سرویس، کانفیگ اختصاصی شما فوراً اینجا نمایش داده می‌شود.
                        </p>
                        <button onclick="switchTab('shop')" class="px-5 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs shadow-lg shadow-indigo-500/25 active:scale-95 transition mt-2">
                            خرید اولین سرویس
                        </button>
                    </div>
                `;
                return;
            }

            services.forEach(s => {
                const card = document.createElement('div');
                card.className = "app-card rounded-3xl p-4.5 space-y-4";

                const isExpired = s.days_left !== null && s.days_left <= 0;
                const statusColor = s.is_active && !isExpired ? 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20' : 'text-rose-500 bg-rose-500/10 border-rose-500/20';
                const statusText = s.is_active && !isExpired ? 'فعال و متصل' : 'منقضی شده';

                const ipLimitText = (s.ip_limit > 0) ? (s.ip_limit + ' کاربر') : 'نامحدود';
                const hwidLimitText = (s.hwid_limit > 0) ? (s.hwid_limit + ' دستگاه') : 'نامحدود';

                const isWireGuard = !!s.wireguard_conf || (s.config_link && s.config_link.startsWith('wireguard://'));

                card.innerHTML = `
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/80">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-brand-500/10 text-brand-500 flex items-center justify-center font-bold text-sm">
                                ⚡
                            </span>
                            <div>
                                <h4 class="font-bold text-xs">${escapeHtml(s.panel_name || 'اشتراک اینترنت آزاد')}</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] text-muted">شناسه: #${s.id}</span>
                                    ${isWireGuard ? '<span class="px-1.5 py-0.2 rounded text-[9px] bg-purple-500/10 text-purple-400 font-bold">WireGuard 🛡️</span>' : ''}
                                </div>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full border text-[10px] font-semibold ${statusColor}">
                            ${statusText}
                        </span>
                    </div>

                    <!-- Usage Progress Bar -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted">مصرف ترافیک:</span>
                            <span class="font-mono font-bold text-[11px]">${s.used_gb} GB / ${s.traffic_gb} GB</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-indigo-500 transition-all duration-500" style="width: ${Math.min(100, s.progress_percent || 0)}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-muted font-mono">
                            <span>باقی‌مانده: ${s.remaining_gb} GB</span>
                            <span>اعتبار: ${s.days_left !== null ? (s.days_left + ' روز') : 'نامحدود'}</span>
                        </div>
                    </div>

                    <!-- Limits Pills -->
                    <div class="flex items-center gap-2 text-[10px]">
                        <span class="px-2 py-0.5 rounded-lg bg-blue-500/10 text-blue-500 border border-blue-500/20 font-medium">🌐 آی‌پی: ${ipLimitText}</span>
                        <span class="px-2 py-0.5 rounded-lg bg-purple-500/10 text-purple-500 border border-purple-500/20 font-medium">📱 سخت‌افزار (HWID): ${hwidLimitText}</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button onclick='copyServiceLink(${JSON.stringify(s)})' class="py-2.5 px-3 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition active:scale-95 flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-copy"></i>
                            <span>کپی لینک اتصال</span>
                        </button>
                        <button onclick='openQrModal(${JSON.stringify(s)})' class="py-2.5 px-3 rounded-xl app-card hover:bg-subtle text-xs font-semibold transition active:scale-95 flex items-center justify-center gap-1.5 border border-slate-200 dark:border-slate-800">
                            <i class="fa-solid fa-qrcode"></i>
                            <span>مشاهده کد QR</span>
                        </button>
                    </div>

                    ${isWireGuard ? `
                        <button onclick='downloadWireguardConf(${JSON.stringify(s)})' class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:opacity-90 text-white text-xs font-semibold shadow-md shadow-purple-600/30 transition active:scale-95 flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>دانلود مستقیم فایل کانفیگ وایرگارد (.conf)</span>
                        </button>
                    ` : ''}
                `;
                container.appendChild(card);
            });
        }

        function copyServiceLink(service) {
            const link = service.sub_url || service.config_link;
            if (link) {
                copyText(link, 'لینک اتصال اشتراک با موفقیت کپی شد! 📋');
            } else {
                showToast('لینک ارتباطی یافت نشد.', 'error');
            }
        }

        function openQrModal(service) {
            haptic('impact');
            const link = service.sub_url || service.config_link || '';
            if (!link) {
                showToast('لینک اتصال برای ساخت QR کد موجود نیست.', 'error');
                return;
            }

            document.getElementById('qr-modal-title').innerText = service.panel_name || 'کد QR اتصال';
            const qrBox = document.getElementById('qrcode-box');
            qrBox.innerHTML = '';

            new QRCode(qrBox, {
                text: link,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });

            document.getElementById('btn-copy-from-modal').onclick = () => {
                copyText(link, 'لینک اتصال کپی شد 📋');
            };

            const btnConf = document.getElementById('btn-download-conf-modal');
            if (service.wireguard_conf) {
                btnConf.classList.remove('hidden');
                btnConf.onclick = () => downloadWireguardConf(service);
            } else {
                btnConf.classList.add('hidden');
            }

            document.getElementById('qrModal').classList.remove('hidden');
            document.getElementById('qrModal').classList.add('flex');
        }

        function closeQrModal() {
            document.getElementById('qrModal').classList.add('hidden');
            document.getElementById('qrModal').classList.remove('flex');
        }

        function downloadWireguardConf(service) {
            haptic('impact');
            const confContent = service.wireguard_conf;
            if (!confContent) {
                showToast('فایل کانفیگ وایرگارد برای این سرویس تولید نشده است.', 'error');
                return;
            }

            const filename = `Wireguard_${service.id}.conf`;
            const blob = new Blob([confContent], { type: 'text/plain;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast('فایل .conf با موفقیت دانلود شد 📥', 'success');
        }


        // --- CHECKOUT & ORDER MODAL ---
        function openCheckoutModal(plan, isCustom = false) {
            haptic('impact');
            isCustomCheckout = isCustom;

            if (isCustom) {
                const vol = parseInt(document.getElementById('custom-traffic-slider').value);
                const days = parseInt(document.getElementById('custom-days-slider').value);
                const pricePerGb = selectedPanel?.price_per_gb || 4000;
                const pricePerDay = selectedPanel?.price_per_day || 1000;
                const total = (vol * pricePerGb) + (days * pricePerDay);

                document.getElementById('checkout-server-name').innerText = selectedPanel?.name || 'سرور اختصاصی';
                document.getElementById('checkout-plan-name').innerText = 'سرویس سفارشی کاربر';
                document.getElementById('checkout-traffic-days').innerText = `${vol} GB / ${days} روز`;
                document.getElementById('checkout-limits').innerText = '🌐 IP: 1 / 📱 HWID: نامحدود';
                document.getElementById('checkout-price').innerText = Number(total).toLocaleString();
            } else {
                selectedPlan = plan;
                const ipText = (plan.ip_limit > 0) ? (plan.ip_limit + ' کاربر') : 'نامحدود';
                const hwidText = (plan.hwid_limit > 0) ? (plan.hwid_limit + ' دستگاه') : 'نامحدود';

                document.getElementById('checkout-server-name').innerText = selectedPanel?.name || 'سرور اختصاصی';
                document.getElementById('checkout-plan-name').innerText = plan.name;
                document.getElementById('checkout-traffic-days').innerText = `${plan.traffic_gb} GB / ${plan.days} روز`;
                document.getElementById('checkout-limits').innerText = `🌐 IP: ${ipText} / 📱 HWID: ${hwidText}`;
                document.getElementById('checkout-price').innerText = Number(plan.price).toLocaleString();
            }

            const isAdmin = currentUser?.is_admin || appData.is_admin;
            const btnAdmin = document.getElementById('btn-pay-method-admin');
            if (btnAdmin) {
                if (isAdmin) {
                    btnAdmin.classList.remove('hidden');
                    btnAdmin.classList.add('flex');
                } else {
                    btnAdmin.classList.add('hidden');
                    btnAdmin.classList.remove('flex');
                }
            }

            setCheckoutPaymentMethod(isAdmin ? 'admin_test' : 'wallet');
            document.getElementById('checkoutModal').classList.remove('hidden');
            document.getElementById('checkoutModal').classList.add('flex');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
            document.getElementById('checkoutModal').classList.remove('flex');
        }

        function setCheckoutPaymentMethod(method) {
            haptic('impact');
            checkoutPayMethod = method;
            const btnWallet = document.getElementById('btn-pay-method-wallet');
            const btnCard = document.getElementById('btn-pay-method-card');
            const btnAdmin = document.getElementById('btn-pay-method-admin');
            const extra = document.getElementById('checkout-card-extra');
            const btnConfirm = document.getElementById('btn-confirm-checkout');

            // Reset base styles
            btnWallet.className = 'p-3 rounded-2xl border border-slate-200 dark:border-slate-800 text-muted text-xs font-medium flex flex-col items-center gap-1';
            btnCard.className = 'p-3 rounded-2xl border border-slate-200 dark:border-slate-800 text-muted text-xs font-medium flex flex-col items-center gap-1';
            if (btnAdmin) {
                btnAdmin.className = 'col-span-2 p-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 text-amber-500 text-xs font-bold flex items-center justify-center gap-1.5';
            }

            if (method === 'admin_test' && btnAdmin) {
                btnAdmin.className = 'col-span-2 p-3 rounded-2xl border-2 border-amber-500 bg-amber-500/20 text-amber-500 text-xs font-bold flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/10';
                extra.classList.add('hidden');
                btnConfirm.innerText = '⚡ تایید و صدور فوری کانفیگ تست روی سرور';
            } else if (method === 'wallet') {
                btnWallet.className = 'p-3 rounded-2xl border-2 border-brand-500 bg-brand-500/10 text-brand-500 text-xs font-bold flex flex-col items-center gap-1';
                extra.classList.add('hidden');
                btnConfirm.innerText = 'تایید و پرداخت از کیف پول';
            } else {
                btnCard.className = 'p-3 rounded-2xl border-2 border-brand-500 bg-brand-500/10 text-brand-500 text-xs font-bold flex flex-col items-center gap-1';
                extra.classList.remove('hidden');
                btnConfirm.innerText = 'ثبت فیش و ارسال به مدیریت';
            }
        }

        async function confirmOrder() {
            haptic('impact');
            const btn = document.getElementById('btn-confirm-checkout');
            btn.disabled = true;
            btn.innerText = 'در حال پردازش سفارش...';

            let tgUser = getTelegramUser();
            let tgId = currentUser?.telegram_id || tgUser?.id || '';
            const init_data = window.Telegram?.WebApp?.initData || '';

            if (!init_data) {
                showToast('لطفاً مینی‌اپ را از داخل ربات تلگرام باز کنید.', 'error');
                btn.disabled = false;
                btn.innerText = 'تایید و ثبت نهایی سفارش';
                return;
            }

            const formData = new FormData();
            formData.append('init_data', init_data);
            formData.append('telegram_id', tgId);
            formData.append('first_name', currentUser?.first_name || tgUser?.first_name || 'کاربر');
            formData.append('username', currentUser?.username || tgUser?.username || '');
            formData.append('payment_method', checkoutPayMethod);
            formData.append('panel_id', selectedPanel?.id || 0);

            if (isCustomCheckout) {
                formData.append('is_custom', '1');
                formData.append('custom_traffic_gb', document.getElementById('custom-traffic-slider').value);
                formData.append('custom_days', document.getElementById('custom-days-slider').value);
                formData.append('ip_limit', '1');
                formData.append('hwid_limit', '0');
            } else {
                formData.append('plan_id', selectedPlan.id);
            }

            if (checkoutPayMethod === 'card') {
                const receiptFile = document.getElementById('checkout-receipt-file').files[0];
                const receiptText = document.getElementById('checkout-receipt-text').value;
                if (receiptFile) formData.append('receipt_photo', receiptFile);
                if (receiptText) formData.append('receipt_text', receiptText);
            }

            try {
                const res = await fetch('/api/miniapp/order', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    closeCheckoutModal();
                    showToast(data.message || 'سفارش با موفقیت ثبت شد!', 'success');
                    await loadAppData();
                    if (data.instant) {
                        switchTab('services');
                    } else {
                        switchTab('wallet');
                    }
                } else {
                    showToast(data.error || 'خطا در ثبت سفارش', 'error');
                }
            } catch (err) {
                showToast('خطای شبکه در ثبت سفارش', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'تایید و ثبت نهایی سفارش';
            }
        }

        // --- WALLET RECHARGE ---
        async function handleWalletRecharge(e) {
            e.preventDefault();
            haptic('impact');

            const amount = document.getElementById('wallet-amount').value;
            const file = document.getElementById('wallet-receipt-file').files[0];
            const text = document.getElementById('wallet-receipt-text').value;

            if (!amount || amount <= 0) {
                showToast('لطفاً مبلغ واریزی را وارد کنید.', 'error');
                return;
            }

            let tgUser = getTelegramUser();
            let tgId = currentUser?.telegram_id || tgUser?.id || '';
            const init_data = window.Telegram?.WebApp?.initData || '';

            if (!init_data) {
                showToast('لطفاً مینی‌اپ را از داخل ربات تلگرام باز کنید.', 'error');
                return;
            }

            const btn = document.getElementById('btn-submit-wallet');
            btn.disabled = true;
            btn.innerText = 'در حال ارسال فیش...';

            const formData = new FormData();
            formData.append('init_data', init_data);
            formData.append('telegram_id', tgId);
            formData.append('amount', amount);
            formData.append('payment_method', 'card');
            formData.append('wallet_charge', '1');
            if (file) formData.append('receipt_photo', file);
            if (text) formData.append('receipt_text', text);

            try {
                const res = await fetch('/api/miniapp/order', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('رسید شما ثبت شد و پس از بررسی ادمین اعمال می‌گردد.', 'success');
                    document.getElementById('wallet-amount').value = '';
                    document.getElementById('wallet-receipt-file').value = '';
                    document.getElementById('wallet-receipt-text').value = '';
                } else {
                    showToast(data.error || 'خطا در ارسال فیش', 'error');
                }
            } catch (err) {
                showToast('خطای ارتباط با سرور', 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'ارسال فیش واریز جهت تایید';
            }
        }

        function showSupportModal() {
            haptic('impact');
            const supportText = appData.texts?.support || 'جهت ارتباط با پشتیبانی به آیدی ادمین پیام دهید.';
            document.getElementById('support-modal-text').innerText = supportText;
            
            // Extract telegram link if present
            const match = supportText.match(/@([a-zA-Z0-9_]+)/);
            if (match) {
                document.getElementById('btn-open-support-tg').href = 'https://t.me/' + match[1];
            } else {
                document.getElementById('btn-open-support-tg').href = 'https://t.me';
            }

            document.getElementById('supportModal').classList.remove('hidden');
            document.getElementById('supportModal').classList.add('flex');
        }

        function closeSupportModal() {
            document.getElementById('supportModal').classList.add('hidden');
            document.getElementById('supportModal').classList.remove('flex');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
            });
        }

        // Initial Load & Telegram WebApp deferred retry
        loadAppData();
        if (window.Telegram?.WebApp) {
            try {
                window.Telegram.WebApp.ready();
                window.Telegram.WebApp.expand();
            } catch(e) {}
        }
        setTimeout(loadAppData, 200);
        setTimeout(loadAppData, 600);
    </script>
</body>
</html>
