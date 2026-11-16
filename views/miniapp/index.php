<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VPN Master Mini App</title>
    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <!-- QRCode library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        :root {
            --app-primary: <?= htmlspecialchars($primaryColor ?? '#2563eb') ?>;
            --app-accent: <?= htmlspecialchars($accentColor ?? '#10b981') ?>;
            --app-text: <?= htmlspecialchars($textColor ?? '#ffffff') ?>;
            --app-radius: <?= htmlspecialchars($btnRadius ?? '16px') ?>;
        }
        .btn-custom-primary {
            background-color: var(--app-primary) !important;
            color: var(--app-text) !important;
            border-radius: var(--app-radius) !important;
        }
        .btn-custom-accent {
            background-color: var(--app-accent) !important;
            color: var(--app-text) !important;
            border-radius: var(--app-radius) !important;
        }
        .text-custom-primary {
            color: var(--app-primary) !important;
        }
        .bg-custom-primary {
            background-color: var(--app-primary) !important;
        }
        .border-custom-primary {
            border-color: var(--app-primary) !important;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen pb-28 flex flex-col antialiased">

    <!-- Top Sticky Header -->
    <header class="p-3.5 border-b border-slate-900 bg-slate-950/80 backdrop-blur sticky top-0 z-30 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold shadow-md shadow-blue-500/20 btn-custom-primary">
                ⚡
            </div>
            <div>
                <h1 class="font-bold text-xs text-white">فروشگاه هوشمند VPN</h1>
                <p class="text-[10px] text-slate-400" id="header-user-text">در حال بارگذاری اطلاعات...</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 text-[11px] text-amber-300 font-mono">
                <span>💰</span>
                <span id="header-balance">0</span>
                <span class="text-[9px] text-slate-400 font-sans">تومان</span>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-4 max-w-lg mx-auto w-full">

        <!-- ============================================================== -->
        <!-- TAB 1: حساب کاربری (PROFILE) [Rightmost Tab 1]                  -->
        <!-- ============================================================== -->
        <section id="tab-profile" class="space-y-4">
            <!-- User Card -->
            <div class="p-5 rounded-3xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-2xl text-white shadow-lg shadow-blue-500/30">
                            👤
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-white" id="profile-name">کاربر تلگرام</h2>
                            <p class="text-xs text-blue-400 font-mono mt-0.5" id="profile-username">@username</p>
                            <span class="text-[10px] text-slate-500 font-mono" id="profile-tgid">ID: 00000000</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px]">
                        عضو ویژه
                    </span>
                </div>

                <!-- Balance Box -->
                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-slate-400">موجودی کیف پول شما</div>
                        <div class="text-lg font-bold text-white font-mono mt-0.5">
                            <span id="profile-balance">0</span> <span class="text-xs font-sans text-slate-400">تومان</span>
                        </div>
                    </div>
                    <button onclick="switchTab('wallet')" class="px-4 py-2 rounded-xl text-xs font-bold btn-custom-primary transition shadow-md">
                        + شارژ حساب
                    </button>
                </div>

                <!-- Account Stats Grid -->
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                        <div class="text-[10px] text-slate-400">اشتراک‌های فعال</div>
                        <div class="text-base font-bold text-blue-400 mt-1 font-mono" id="profile-active-services">0</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                        <div class="text-[10px] text-slate-400">تاریخ عضویت</div>
                        <div class="text-xs font-bold text-slate-300 mt-1.5" id="profile-reg-date">-</div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="space-y-2">
                <button onclick="switchTab('shop')" class="w-full p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500 transition flex items-center justify-between text-right">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🛍</span>
                        <div>
                            <div class="text-xs font-bold text-white">خرید اشتراک جدید</div>
                            <div class="text-[10px] text-slate-400">سنایی، پاسارگاد، مرزبان، وایرگارد و سرویس دلخواه</div>
                        </div>
                    </div>
                    <span class="text-slate-500 text-xs">←</span>
                </button>

                <button onclick="switchTab('services')" class="w-full p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-emerald-500 transition flex items-center justify-between text-right">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🔑</span>
                        <div>
                            <div class="text-xs font-bold text-white">مشاهده سرویس‌های من</div>
                            <div class="text-[10px] text-slate-400">استعلام زنده ترافیک، روزهای باقی‌مانده و کانفیگ‌ها</div>
                        </div>
                    </div>
                    <span class="text-slate-500 text-xs">←</span>
                </button>
            </div>
        </section>

        <!-- ============================================================== -->
        <!-- TAB 2: خرید سرویس جدید (SHOP) [2nd from Right]                 -->
        <!-- ============================================================== -->
        <section id="tab-shop" class="space-y-4 hidden">

            <!-- Step Progress Indicator -->
            <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span id="step-badge" class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs font-bold">۱</span>
                    <span id="step-title" class="font-bold text-white">انتخاب سرور (لوکیشن)</span>
                </div>
                <button id="shop-back-btn" onclick="prevShopStep()" class="hidden text-[11px] text-blue-400 hover:text-blue-300">
                    ← مرحله قبلی
                </button>
            </div>

            <!-- STEP 1: SELECT SERVER (PANEL) -->
            <div id="shop-step-1" class="space-y-3">
                <div class="text-xs text-slate-400 px-1">
                    📌 سرور یا لوکیشن مورد نظرتان را برای اتصال انتخاب نمایید:
                </div>
                <div id="panels-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="text-center py-8 text-xs text-slate-500 col-span-2">در حال بارگذاری سرورها...</div>
                </div>
            </div>

            <!-- STEP 2: SELECT CATEGORY -->
            <div id="shop-step-2" class="space-y-3 hidden">
                <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400">سرور انتخاب شده:</span>
                    <span class="font-bold text-white flex items-center gap-1.5" id="selected-server-name">سرور</span>
                </div>

                <div class="text-xs text-slate-400 px-1">
                    📌 دسته‌بندی اشتراک خود را انتخاب فرمایید:
                </div>

                <!-- Category Pills -->
                <div id="categories-container" class="flex flex-wrap gap-2">
                    <!-- Dynamic categories -->
                </div>
            </div>

            <!-- STEP 3: CHOOSE READY PLAN OR CUSTOM SERVICE -->
            <div id="shop-step-3" class="space-y-4 hidden">
                <!-- Mode Switcher (Ready Plans vs Custom Service) -->
                <div id="custom-switch-container" class="hidden p-1 bg-slate-900 rounded-2xl border border-slate-800 grid grid-cols-2 gap-1 text-xs">
                    <button id="btn-mode-ready" onclick="setPlanMode('ready')" class="py-2.5 rounded-xl font-bold transition bg-blue-600 text-white shadow-md">
                        📋 پلن‌های آماده
                    </button>
                    <button id="btn-mode-custom" onclick="setPlanMode('custom')" class="py-2.5 rounded-xl font-bold transition text-slate-400 hover:text-white">
                        ✨ ساخت سرویس دلخواه
                    </button>
                </div>

                <!-- Sub-view 3A: Ready Plans -->
                <div id="ready-plans-container" class="space-y-3">
                    <!-- Dynamic ready plans -->
                </div>

                <!-- Sub-view 3B: Custom Service Builder -->
                <div id="custom-builder-container" class="hidden space-y-4 p-5 rounded-3xl bg-slate-900 border border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div>
                            <h4 class="font-bold text-white text-xs">سفارشی‌سازی حجم و زمان</h4>
                            <p class="text-[10px] text-slate-400">قیمت به صورت آنی محاسبه می‌شود</p>
                        </div>
                        <span class="text-xl">🎛</span>
                    </div>

                    <!-- Volume Slider -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="text-slate-300">حجم اشتراک:</span>
                            <span class="font-bold text-blue-400 text-sm font-mono"><span id="custom-vol-val">30</span> گیگابایت</span>
                        </div>
                        <input type="range" id="custom-vol-slider" min="5" max="500" value="30" oninput="calculateCustomPrice()" class="w-full accent-blue-600 cursor-pointer">
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1">
                            <span id="custom-min-gb">5 GB</span>
                            <span id="custom-max-gb">500 GB</span>
                        </div>
                    </div>

                    <!-- Days Slider -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="text-slate-300">مدت اعتبار:</span>
                            <span class="font-bold text-emerald-400 text-sm font-mono"><span id="custom-days-val">30</span> روز</span>
                        </div>
                        <input type="range" id="custom-days-slider" min="1" max="365" value="30" oninput="calculateCustomPrice()" class="w-full accent-emerald-500 cursor-pointer">
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1">
                            <span id="custom-min-days">1 روز</span>
                            <span id="custom-max-days">365 روز</span>
                        </div>
                    </div>

                    <!-- Live Calculated Price Card -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400">مبلغ کل قابل پرداخت:</span>
                            <div class="text-base font-bold text-amber-400 font-mono mt-0.5">
                                <span id="custom-price-val">0</span> <span class="text-xs font-sans text-slate-400">تومان</span>
                            </div>
                        </div>
                        <button onclick="startCheckoutCustom()" class="px-5 py-2.5 rounded-xl font-bold text-xs btn-custom-primary shadow-lg shadow-blue-600/30">
                            خرید این پلن →
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 4: CHECKOUT & PAYMENT -->
            <div id="shop-step-4" class="space-y-4 hidden">
                <!-- Summary Card -->
                <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                    <h4 class="font-bold text-white text-xs pb-2 border-b border-slate-800 flex items-center justify-between">
                        <span>🧾 خلاصه فاکتور خرید</span>
                        <span class="text-amber-400 font-mono text-sm" id="checkout-price">0 تومان</span>
                    </h4>

                    <div class="space-y-2 text-xs text-slate-300">
                        <div class="flex justify-between">
                            <span class="text-slate-400">سرور متصل:</span>
                            <span class="font-bold text-white" id="checkout-server">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">عنوان اشتراک:</span>
                            <span class="font-bold text-white" id="checkout-title">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">حجم کل:</span>
                            <span class="font-mono text-blue-400" id="checkout-vol">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">مدت اعتبار:</span>
                            <span class="font-mono text-emerald-400" id="checkout-days">-</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="space-y-3">
                    <label class="block text-xs font-medium text-slate-300">روش پرداخت مورد نظر را انتخاب نمایید:</label>

                    <!-- Option A: Wallet Balance (Instant) -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-emerald-500 cursor-pointer transition">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="pay_method" value="wallet" checked onchange="togglePayMethod('wallet')" class="text-emerald-500">
                            <div>
                                <div class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <span>⚡ پرداخت از موجودی کیف پول</span>
                                    <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">تحویل آنی</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    موجودی فعلی: <span class="text-emerald-400 font-mono" id="checkout-user-balance">0</span> تومان
                                </div>
                            </div>
                        </div>
                        <span class="text-xl">💳</span>
                    </label>

                    <!-- Option B: Card to Card -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500 cursor-pointer transition">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="pay_method" value="card" onchange="togglePayMethod('card')" class="text-blue-500">
                            <div>
                                <div class="text-xs font-bold text-white">کارت به کارت بانکی</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">واریز به شماره کارت و ثبت شماره پیگیری</div>
                            </div>
                        </div>
                        <span class="text-xl">🏦</span>
                    </label>
                </div>

                <!-- Card Details & Receipt Field (Visible if Card-to-Card is selected) -->
                <div id="card-fields-container" class="hidden space-y-3 p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
                    <div id="checkout-cards-list" class="space-y-2">
                        <!-- Dynamic Bank Cards -->
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-300 mb-1">کد پیگیری یا شماره فیش واریزی:</label>
                        <input type="text" id="checkout-receipt-text" placeholder="مثال: 123456 یا شماره ارجاع" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white">
                    </div>
                </div>

                <!-- Final Action Button -->
                <button id="btn-final-pay" onclick="executeFinalPayment()" class="w-full py-3.5 rounded-2xl font-bold text-xs btn-custom-primary shadow-xl shadow-blue-500/30 flex items-center justify-center gap-2 transition">
                    <span>⚡</span> تکمیل خرید و دریافت اشتراک
                </button>
            </div>
        </section>

        <!-- ============================================================== -->
        <!-- TAB 3: سرویس‌های من (MY SERVICES) [3rd from Right]             -->
        <!-- ============================================================== -->
        <section id="tab-services" class="space-y-4 hidden">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-sm text-white">سرویس‌های من</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">استعلام زنده مصرف ترافیک و دانلود کانفیگ</p>
                </div>
                <button onclick="loadAppData(true)" class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-blue-400 hover:text-white flex items-center gap-1">
                    <span>🔄</span> بروزرسانی
                </button>
            </div>

            <div id="services-container" class="space-y-3">
                <div class="text-center py-8 text-xs text-slate-500">در حال دریافت و استعلام سرویس‌ها...</div>
            </div>
        </section>

        <!-- ============================================================== -->
        <!-- TAB 4: کیف پول (WALLET) [Leftmost Tab 4]                       -->
        <!-- ============================================================== -->
        <section id="tab-wallet" class="space-y-4 hidden">
            <!-- Balance Card -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-indigo-950 to-slate-950 border border-indigo-900/40 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs text-indigo-300">موجودی کیف پول شما</span>
                        <div class="text-2xl font-bold text-white font-mono mt-1">
                            <span id="wallet-balance-big">0</span> <span class="text-xs font-sans text-slate-400">تومان</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/30 border border-indigo-500/30 flex items-center justify-center text-2xl text-indigo-300">
                        💳
                    </div>
                </div>
                <p class="text-[10px] text-indigo-300/80 leading-relaxed">
                    با افزایش موجودی کیف پول، می‌توانید تمامی پلن‌ها و سرویس‌های دلخواه را به صورت آنی و بدون معطلی خریداری کنید.
                </p>
            </div>

            <!-- Active Bank Cards -->
            <div class="space-y-3">
                <h4 class="font-bold text-xs text-white">کارت‌های بانکی فروشگاه جهت شارژ حساب:</h4>
                <div id="wallet-cards-container" class="space-y-2.5">
                    <!-- Dynamic Cards -->
                </div>
            </div>

            <!-- Top-up Form -->
            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                <h4 class="font-bold text-xs text-white">ثبت فیش افزایش موجودی</h4>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">مبلغ واریز شده (تومان):</label>
                    <input type="number" id="topup-amount" placeholder="مثال: 100000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">کد پیگیری یا شماره ارجاع:</label>
                    <input type="text" id="topup-receipt" placeholder="کد رهگیری تراکنش..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white">
                </div>
                <button onclick="submitTopup()" class="w-full py-3 rounded-xl font-bold text-xs btn-custom-accent shadow-lg shadow-emerald-500/20 transition">
                    ارسال فیش جهت تایید حساب
                </button>
            </div>
        </section>

    </main>

    <!-- ============================================================== -->
    <!-- FIXED BOTTOM NAVIGATION BAR (RTL: 1.Profile, 2.Shop, 3.Services, 4.Wallet) -->
    <!-- ============================================================== -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-lg border-t border-slate-900 py-2 px-3">
        <div class="max-w-md mx-auto grid grid-cols-4 gap-1 text-center">

            <!-- 1. حساب کاربری (Rightmost) -->
            <button onclick="switchTab('profile')" id="nav-profile" class="nav-btn flex flex-col items-center justify-center py-1.5 rounded-2xl text-slate-400 transition">
                <span class="text-lg">👤</span>
                <span class="text-[10px] mt-0.5 font-medium">حساب من</span>
            </button>

            <!-- 2. خرید سرویس جدید (2nd from Right) -->
            <button onclick="switchTab('shop')" id="nav-shop" class="nav-btn flex flex-col items-center justify-center py-1.5 rounded-2xl text-blue-500 font-bold transition">
                <span class="text-lg">🛍</span>
                <span class="text-[10px] mt-0.5">خرید سرویس</span>
            </button>

            <!-- 3. سرویس‌های من (3rd from Right) -->
            <button onclick="switchTab('services')" id="nav-services" class="nav-btn flex flex-col items-center justify-center py-1.5 rounded-2xl text-slate-400 transition">
                <span class="text-lg">🔑</span>
                <span class="text-[10px] mt-0.5 font-medium">سرویس‌ها</span>
            </button>

            <!-- 4. کیف پول (4th from Right / Leftmost) -->
            <button onclick="switchTab('wallet')" id="nav-wallet" class="nav-btn flex flex-col items-center justify-center py-1.5 rounded-2xl text-slate-400 transition">
                <span class="text-lg">💳</span>
                <span class="text-[10px] mt-0.5 font-medium">کیف پول</span>
            </button>

        </div>
    </nav>

    <!-- QR Code & Config Modal -->
    <div id="qrModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-sm w-full space-y-4 text-center">
            <h3 class="font-bold text-white text-sm" id="qrModalTitle">بارکد اتصال مستقیم</h3>
            <div id="qrcode-box" class="bg-white p-3 rounded-2xl inline-block mx-auto"></div>
            <p class="text-[11px] text-slate-400">با دوربین نرم‌افزار v2rayNG یا WireGuard اسکن کنید</p>
            <button onclick="closeQrModal()" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold">بستن</button>
        </div>
    </div>

    <!-- JavaScript Application Logic -->
    <script>
    const tg = window.Telegram?.WebApp;
    if (tg) {
        tg.ready();
        tg.expand();
    }

    let appData = {
        user: null,
        panels: [],
        plans: [],
        cards: [],
        services: [],
    };

    let shopState = {
        step: 1,
        selectedPanel: null,
        selectedCategory: null,
        planMode: 'ready', // 'ready' or 'custom'
        selectedPlan: null,
        customTrafficGb: 30,
        customDays: 30,
        customPrice: 0,
        isCustom: false,
    };

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        loadAppData();
        // Default to Shop tab
        switchTab('shop');
    });

    function getTelegramUser() {
        if (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) {
            return tg.initDataUnsafe.user;
        }
        return {
            id: 123456789,
            first_name: 'کاربر آزمایشی',
            username: 'demo_user'
        };
    }

    function loadAppData(silent = false) {
        const u = getTelegramUser();
        fetch('/api/miniapp/data?telegram_id=' + u.id)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    appData = res;
                    renderUserProfile();
                    renderShopPanels();
                    renderServices();
                    renderWallet();
                }
            })
            .catch(err => {
                console.error('Data load error:', err);
            });
    }

    // Switch Navigation Tabs
    function switchTab(tabName) {
        const tabs = ['profile', 'shop', 'services', 'wallet'];
        tabs.forEach(t => {
            const el = document.getElementById('tab-' + t);
            const navBtn = document.getElementById('nav-' + t);
            if (t === tabName) {
                el.classList.remove('hidden');
                navBtn.classList.remove('text-slate-400');
                navBtn.classList.add('text-blue-500', 'font-bold');
            } else {
                el.classList.add('hidden');
                navBtn.classList.remove('text-blue-500', 'font-bold');
                navBtn.classList.add('text-slate-400');
            }
        });
    }

    // --- Tab 1: Render Profile ---
    function renderUserProfile() {
        const u = appData.user || getTelegramUser();
        const bal = u.balance || 0;
        const balFormatted = Number(bal).toLocaleString('fa-IR');

        document.getElementById('header-user-text').innerText = u.first_name || 'کاربر';
        document.getElementById('header-balance').innerText = balFormatted;

        document.getElementById('profile-name').innerText = u.first_name || 'کاربر';
        document.getElementById('profile-username').innerText = u.username ? '@' + u.username : '-';
        document.getElementById('profile-tgid').innerText = 'ID: ' + (u.telegram_id || u.id);
        document.getElementById('profile-balance').innerText = balFormatted;
        document.getElementById('profile-active-services').innerText = (appData.services || []).length;
        document.getElementById('profile-reg-date').innerText = (u.created_at || '').split(' ')[0] || '-';
    }

    // --- Tab 2: Shop Wizard Steps ---
    function renderShopPanels() {
        const container = document.getElementById('panels-grid');
        const panels = appData.panels || [];

        if (panels.length === 0) {
            container.innerHTML = '<div class="p-6 text-center text-xs text-slate-500 col-span-2">در حال حاضر سرور فعالی ثبت نشده است.</div>';
            return;
        }

        container.innerHTML = panels.map(p => `
            <div onclick="selectShopPanel(${p.id})" class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500 cursor-pointer transition space-y-2 group">
                <div class="flex items-center justify-between">
                    <span class="text-xl">🌐</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">آنلاین</span>
                </div>
                <div class="font-bold text-xs text-white group-hover:text-blue-400 transition">${escapeHtml(p.name)}</div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-800/60">
                    <span>${p.custom_service_enabled ? '✨ سرویس دلخواه فعال' : '📋 پلن‌های متنوع'}</span>
                    <span class="text-blue-400">انتخاب سرور ←</span>
                </div>
            </div>
        `).join('');
    }

    function selectShopPanel(panelId) {
        const panel = appData.panels.find(p => p.id === panelId);
        if (!panel) return;

        shopState.selectedPanel = panel;
        document.getElementById('selected-server-name').innerText = panel.name;

        // Render categories for this panel
        const catContainer = document.getElementById('categories-container');
        const categories = panel.categories || ['یک ماهه', 'دو ماهه', 'سه ماهه', 'نامحدود'];

        catContainer.innerHTML = categories.map((c, idx) => `
            <button type="button" onclick="selectShopCategory('${escapeHtml(c)}')" class="px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-blue-500 text-xs font-bold text-slate-300 hover:text-white transition">
                📦 ${escapeHtml(c)}
            </button>
        `).join('');

        setShopStep(2);
    }

    function selectShopCategory(catName) {
        shopState.selectedCategory = catName;

        const panel = shopState.selectedPanel;
        const customSwitch = document.getElementById('custom-switch-container');

        // Check if custom service is enabled for this panel
        if (panel && panel.custom_service_enabled) {
            customSwitch.classList.remove('hidden');
        } else {
            customSwitch.classList.add('hidden');
        }

        renderReadyPlans();
        initCustomSliders();
        setPlanMode('ready');
        setShopStep(3);
    }

    function setPlanMode(mode) {
        shopState.planMode = mode;
        const btnReady = document.getElementById('btn-mode-ready');
        const btnCustom = document.getElementById('btn-mode-custom');
        const readyContainer = document.getElementById('ready-plans-container');
        const customContainer = document.getElementById('custom-builder-container');

        if (mode === 'ready') {
            btnReady.className = 'py-2.5 rounded-xl font-bold transition bg-blue-600 text-white shadow-md';
            btnCustom.className = 'py-2.5 rounded-xl font-bold transition text-slate-400 hover:text-white';
            readyContainer.classList.remove('hidden');
            customContainer.classList.add('hidden');
        } else {
            btnCustom.className = 'py-2.5 rounded-xl font-bold transition bg-emerald-600 text-white shadow-md';
            btnReady.className = 'py-2.5 rounded-xl font-bold transition text-slate-400 hover:text-white';
            readyContainer.classList.add('hidden');
            customContainer.classList.remove('hidden');
            calculateCustomPrice();
        }
    }

    function renderReadyPlans() {
        const container = document.getElementById('ready-plans-container');
        const panel = shopState.selectedPanel;
        const cat = shopState.selectedCategory;

        // Filter plans by panel_id (0 or matching) and category
        const allPlans = appData.plans || [];
        let filtered = allPlans.filter(p => {
            const matchPanel = (p.panel_id == 0 || p.panel_id == panel.id);
            const matchCat = (!p.category || p.category == cat || cat == 'همه');
            return matchPanel && matchCat;
        });

        if (filtered.length === 0) {
            // Show any plans for this panel
            filtered = allPlans.filter(p => p.panel_id == 0 || p.panel_id == panel.id);
        }

        if (filtered.length === 0) {
            container.innerHTML = '<div class="p-6 text-center text-xs text-slate-500">هیچ پلن آماده‌ای برای این دسته‌بندی یافت نشد. می‌توانید از تب «ساخت سرویس دلخواه» استفاده کنید.</div>';
            return;
        }

        container.innerHTML = filtered.map(pl => `
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/60 transition space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-xs text-white">${escapeHtml(pl.name)}</h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">${escapeHtml(pl.description || '')}</p>
                    </div>
                    <div class="text-left font-mono">
                        <div class="text-sm font-bold text-amber-400">${Number(pl.price).toLocaleString('fa-IR')}</div>
                        <div class="text-[9px] text-slate-400 font-sans">تومان</div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-300 pt-2 border-t border-slate-800/80">
                    <span class="flex items-center gap-1">📊 ${pl.traffic_gb} GB</span>
                    <span class="flex items-center gap-1">⏳ ${pl.days} روز</span>
                    <span class="flex items-center gap-1">👥 ${pl.ip_limit ? pl.ip_limit + ' کاربر' : 'نامحدود'}</span>
                </div>

                <button onclick="startCheckoutReady(${pl.id})" class="w-full py-2.5 rounded-xl font-bold text-xs btn-custom-primary transition shadow-md">
                    خرید این پلن
                </button>
            </div>
        `).join('');
    }

    function initCustomSliders() {
        const panel = shopState.selectedPanel;
        if (!panel) return;

        const volSlider = document.getElementById('custom-vol-slider');
        const daysSlider = document.getElementById('custom-days-slider');

        volSlider.min = panel.min_gb || 5;
        volSlider.max = panel.max_gb || 500;
        volSlider.value = Math.max(panel.min_gb || 5, Math.min(30, panel.max_gb || 500));

        daysSlider.min = panel.min_days || 1;
        daysSlider.max = panel.max_days || 365;
        daysSlider.value = Math.max(panel.min_days || 1, Math.min(30, panel.max_days || 365));

        document.getElementById('custom-min-gb').innerText = volSlider.min + ' GB';
        document.getElementById('custom-max-gb').innerText = volSlider.max + ' GB';
        document.getElementById('custom-min-days').innerText = daysSlider.min + ' روز';
        document.getElementById('custom-max-days').innerText = daysSlider.max + ' روز';

        calculateCustomPrice();
    }

    function calculateCustomPrice() {
        const panel = shopState.selectedPanel;
        if (!panel) return;

        const vol = parseInt(document.getElementById('custom-vol-slider').value) || 10;
        const days = parseInt(document.getElementById('custom-days-slider').value) || 30;

        document.getElementById('custom-vol-val').innerText = vol;
        document.getElementById('custom-days-val').innerText = days;

        const pricePerGb = panel.price_per_gb || 4000;
        const pricePerDay = panel.price_per_day || 1000;
        const total = (vol * pricePerGb) + (days * pricePerDay);

        shopState.customTrafficGb = vol;
        shopState.customDays = days;
        shopState.customPrice = total;

        document.getElementById('custom-price-val').innerText = Number(total).toLocaleString('fa-IR');
    }

    function startCheckoutReady(planId) {
        const plan = appData.plans.find(p => p.id === planId);
        if (!plan) return;

        shopState.isCustom = false;
        shopState.selectedPlan = plan;

        renderCheckoutView(plan.name, plan.price, plan.traffic_gb + ' GB', plan.days + ' روز');
        setShopStep(4);
    }

    function startCheckoutCustom() {
        shopState.isCustom = true;
        shopState.selectedPlan = null;

        const title = `سرویس دلخواه (${shopState.customTrafficGb}GB - ${shopState.customDays} روز)`;
        renderCheckoutView(title, shopState.customPrice, shopState.customTrafficGb + ' GB', shopState.customDays + ' روز');
        setShopStep(4);
    }

    function renderCheckoutView(title, price, volStr, daysStr) {
        document.getElementById('checkout-title').innerText = title;
        document.getElementById('checkout-server').innerText = shopState.selectedPanel.name;
        document.getElementById('checkout-vol').innerText = volStr;
        document.getElementById('checkout-days').innerText = daysStr;
        document.getElementById('checkout-price').innerText = Number(price).toLocaleString('fa-IR') + ' تومان';

        const userBal = (appData.user && appData.user.balance) || 0;
        document.getElementById('checkout-user-balance').innerText = Number(userBal).toLocaleString('fa-IR');

        // Render bank cards
        const cards = appData.cards || [];
        const cardsList = document.getElementById('checkout-cards-list');
        cardsList.innerHTML = cards.map(c => `
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-bold text-white">${escapeHtml(c.holder_name)} (${escapeHtml(c.bank_name)})</div>
                    <div class="text-xs text-amber-400 font-mono mt-0.5">${escapeHtml(c.card_number)}</div>
                </div>
                <button type="button" onclick="copyText('${c.card_number}')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[10px] text-slate-300">
                    کپی کارت
                </button>
            </div>
        `).join('');
    }

    function togglePayMethod(method) {
        const cardFields = document.getElementById('card-fields-container');
        if (method === 'card') {
            cardFields.classList.remove('hidden');
        } else {
            cardFields.classList.add('hidden');
        }
    }

    function executeFinalPayment() {
        const u = getTelegramUser();
        const method = document.querySelector('input[name="pay_method"]:checked')?.value || 'wallet';
        const receiptText = document.getElementById('checkout-receipt-text')?.value.trim() || '';

        const payload = {
            telegram_id: u.id,
            first_name: u.first_name,
            username: u.username,
            payment_method: method,
            panel_id: shopState.selectedPanel.id,
            is_custom: shopState.isCustom,
            plan_id: shopState.selectedPlan ? shopState.selectedPlan.id : 0,
            custom_traffic_gb: shopState.customTrafficGb,
            custom_days: shopState.customDays,
            receipt_text: receiptText,
        };

        const btn = document.getElementById('btn-final-pay');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ در حال ثبت و پردازش...';
        btn.disabled = true;

        fetch('/api/miniapp/order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = originalText;
            btn.disabled = false;

            if (res.success) {
                alert('🎉 ' + res.message);
                loadAppData();
                switchTab('services');
                setShopStep(1);
            } else {
                alert('❌ ' + (res.error || 'خطا در ثبت سفارش'));
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('خطای اتصال به سرور: ' + err);
        });
    }

    function setShopStep(step) {
        shopState.step = step;
        const steps = [1, 2, 3, 4];
        steps.forEach(s => {
            const el = document.getElementById('shop-step-' + s);
            if (s === step) el.classList.remove('hidden');
            else el.classList.add('hidden');
        });

        const badge = document.getElementById('step-badge');
        const title = document.getElementById('step-title');
        const backBtn = document.getElementById('shop-back-btn');

        badge.innerText = step;
        if (step === 1) {
            title.innerText = 'انتخاب سرور (لوکیشن)';
            backBtn.classList.add('hidden');
        } else if (step === 2) {
            title.innerText = 'انتخاب دسته‌بندی';
            backBtn.classList.remove('hidden');
        } else if (step === 3) {
            title.innerText = 'انتخاب یا ساخت پلن';
            backBtn.classList.remove('hidden');
        } else if (step === 4) {
            title.innerText = 'تایید و پرداخت';
            backBtn.classList.remove('hidden');
        }
    }

    function prevShopStep() {
        if (shopState.step > 1) {
            setShopStep(shopState.step - 1);
        }
    }

    // --- Tab 3: Render Services ---
    function renderServices() {
        const container = document.getElementById('services-container');
        const services = appData.services || [];

        if (services.length === 0) {
            container.innerHTML = `
                <div class="p-8 text-center bg-slate-900 rounded-3xl border border-slate-800 space-y-3">
                    <span class="text-3xl">🔑</span>
                    <div class="text-xs font-bold text-white">شما هنوز هیچ سرویس فعالی ندارید.</div>
                    <p class="text-[10px] text-slate-400">جهت خرید و فعالسازی سریع، از تب خرید سرویس استفاده کنید.</p>
                    <button onclick="switchTab('shop')" class="px-5 py-2 rounded-xl text-xs font-bold btn-custom-primary">
                        خرید اشتراک جدید
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = services.map(s => {
            const isConfWireguard = Boolean(s.wireguard_conf);
            return `
                <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-3 shadow-lg">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full ${s.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'}"></span>
                            <h4 class="font-bold text-xs text-white">اشتراک #${s.id} (${escapeHtml(s.panel_name)})</h4>
                        </div>
                        <button onclick="syncSingleService(${s.id})" class="text-[10px] text-blue-400 hover:text-white flex items-center gap-1">
                            <span>🔄</span> استعلام زنده
                        </button>
                    </div>

                    <!-- Usage Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-[11px] text-slate-300">
                            <span>مصرف شده: <b class="font-mono text-white">${s.used_gb} GB</b></span>
                            <span>کل: <b class="font-mono text-blue-400">${s.traffic_gb} GB</b></span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-800">
                            <div class="bg-gradient-to-r from-blue-600 to-emerald-500 h-2.5 rounded-full" style="width: ${s.progress_percent}%;"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 pt-0.5">
                            <span>باقی‌مانده: ${s.remaining_gb} GB</span>
                            <span>انقضا: ${s.days_left !== null ? s.days_left + ' روز مانده' : (s.expires_at ? s.expires_at.split(' ')[0] : 'نامحدود')}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-800/80">
                        ${s.sub_url ? `
                            <button onclick="copyText('${escapeHtml(s.sub_url)}')" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-300 hover:text-white transition flex items-center justify-center gap-1">
                                <span>📋</span> کپی سابسکریپشن
                            </button>
                        ` : ''}

                        ${s.config_link ? `
                            <button onclick="copyText('${escapeHtml(s.config_link)}')" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-300 hover:text-white transition flex items-center justify-center gap-1">
                                <span>🔗</span> کپی کانفیگ مستقیم
                            </button>
                        ` : ''}

                        <button onclick="showQrModal('${escapeHtml(s.sub_url || s.config_link)}', 'اشتراک #${s.id}')" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-300 hover:text-white transition flex items-center justify-center gap-1">
                            <span>📱</span> بارکد QR
                        </button>

                        ${isConfWireguard ? `
                            <button onclick="downloadWireguardConf(${s.id})" class="py-2 px-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-[11px] text-amber-300 hover:text-white transition flex items-center justify-center gap-1">
                                <span>📁</span> دانلود فایل (.conf)
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    function syncSingleService(serviceId) {
        fetch('/api/miniapp/sync-service?id=' + serviceId)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('✅ اطلاعات مصرف اشتراک با سرور همگام‌سازی شد!');
                    loadAppData();
                } else {
                    alert('خطا در استعلام: ' + (res.error || ''));
                }
            });
    }

    function downloadWireguardConf(serviceId) {
        const s = appData.services.find(item => item.id === serviceId);
        if (!s || !s.wireguard_conf) {
            alert('فایل کانفیگ وایرگارد برای این سرویس موجود نیست.');
            return;
        }

        const blob = new Blob([s.wireguard_conf], { type: 'text/plain;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `wireguard_service_${serviceId}.conf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // --- Tab 4: Render Wallet ---
    function renderWallet() {
        const u = appData.user || {};
        const bal = u.balance || 0;
        document.getElementById('wallet-balance-big').innerText = Number(bal).toLocaleString('fa-IR');

        const cards = appData.cards || [];
        const container = document.getElementById('wallet-cards-container');

        if (cards.length === 0) {
            container.innerHTML = '<div class="text-xs text-slate-500">شماره کارتی ثبت نشده است.</div>';
            return;
        }

        container.innerHTML = cards.map(c => `
            <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-950 border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-white">${escapeHtml(c.holder_name)} - ${escapeHtml(c.bank_name)}</div>
                    <div class="text-sm font-bold text-amber-400 font-mono tracking-widest mt-1">${escapeHtml(c.card_number)}</div>
                </div>
                <button onclick="copyText('${c.card_number}')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs text-slate-200">
                    کپی کارت
                </button>
            </div>
        `).join('');
    }

    function submitTopup() {
        const u = getTelegramUser();
        const amount = parseInt(document.getElementById('topup-amount').value) || 0;
        const receipt = document.getElementById('topup-receipt').value.trim();

        if (amount < 10000) {
            alert('حداقل مبلغ شارژ ۱۰,۰۰۰ تومان است.');
            return;
        }
        if (!receipt) {
            alert('لطفاً کد رهگیری یا شماره پیگیری فیش را وارد کنید.');
            return;
        }

        fetch('/api/miniapp/topup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                telegram_id: u.id,
                amount: amount,
                receipt_text: receipt,
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('✅ ' + res.message);
                document.getElementById('topup-amount').value = '';
                document.getElementById('topup-receipt').value = '';
            } else {
                alert('❌ ' + (res.error || 'خطا در ثبت فیش'));
            }
        });
    }

    // Modal & QR Code
    function showQrModal(text, title) {
        if (!text) {
            alert('لینک کانفیگ موجود نیست.');
            return;
        }
        document.getElementById('qrModalTitle').innerText = title || 'بارکد اتصال';
        const box = document.getElementById('qrcode-box');
        box.innerHTML = '';
        new QRCode(box, {
            text: text,
            width: 180,
            height: 180,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        document.getElementById('qrModal').classList.remove('hidden');
        document.getElementById('qrModal').classList.add('flex');
    }

    function closeQrModal() {
        document.getElementById('qrModal').classList.add('hidden');
        document.getElementById('qrModal').classList.remove('flex');
    }

    function copyText(txt) {
        if (!txt) return;
        navigator.clipboard.writeText(txt).then(() => {
            alert('📋 با موفقیت کپی شد!');
        }).catch(() => {
            prompt('لینک زیر را کپی کنید:', txt);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    </script>
</body>
</html>
