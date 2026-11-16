<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VPN Mini App</title>
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
            --app-radius: <?= htmlspecialchars($btnRadius ?? '12px') ?>;
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
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen pb-24 flex flex-col">

    <!-- Top App Bar -->
    <header class="p-4 border-b border-slate-900 bg-slate-950/80 backdrop-blur sticky top-0 z-30 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold shadow-md shadow-blue-500/20 btn-custom-primary">
                ⚡
            </div>
            <div>
                <h1 class="font-bold text-sm text-white">فروشگاه اختصاصی VPN</h1>
                <p class="text-[10px] text-slate-400" id="user-greeting">در حال اتصال...</p>
            </div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full bg-slate-900 border border-slate-800 text-emerald-400 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> سرورها آنلاین
        </span>
    </header>

    <!-- Main Container -->
    <main class="flex-1 p-4 max-w-lg mx-auto w-full">

        <!-- TAB 1: PLANS (SHOP) -->
        <section id="tab-plans" class="space-y-4">
            <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-950 border border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-sm text-white">خرید اشتراک پرسرعت</h2>
                    <p class="text-xs text-slate-400 mt-0.5">پروتکل‌های ضد فیلتر VLESS Reality و Trojan</p>
                </div>
                <span class="text-2xl">🚀</span>
            </div>

            <div id="plans-container" class="space-y-3">
                <div class="text-center py-8 text-xs text-slate-500">در حال بارگذاری پلن‌ها...</div>
            </div>
        </section>

        <!-- TAB 2: MY SERVICES -->
        <section id="tab-services" class="space-y-4 hidden">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-sm text-white">سرویس‌های فعال من</h2>
                <button onclick="loadAppData()" class="text-xs text-blue-400">🔄 بروزرسانی</button>
            </div>

            <div id="services-container" class="space-y-3">
                <div class="text-center py-8 text-xs text-slate-500">در حال دریافت سرویس‌ها...</div>
            </div>
        </section>

        <!-- TAB 3: WALLET & CARDS -->
        <section id="tab-wallet" class="space-y-4 hidden">
            <div>
                <h2 class="font-bold text-sm text-white">اطلاعات حساب و واریز کارت به کارت</h2>
                <p class="text-xs text-slate-400 mt-1">شماره کارت‌های فعال فروشگاه جهت انتقال وجه:</p>
            </div>

            <div id="cards-container" class="space-y-3"></div>
        </section>

        <!-- TAB 4: HELP -->
        <section id="tab-help" class="space-y-4 hidden">
            <div>
                <h2 class="font-bold text-sm text-white">راهنمای اتصال به نرم‌افزارها</h2>
                <p class="text-xs text-slate-400 mt-1">نرم‌افزارهای پیشنهادی متناسب با سیستم‌عامل شما</p>
            </div>

            <div class="space-y-3 text-xs">
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <div class="font-bold text-white flex items-center gap-2"><span>🤖</span> اندروید (Android)</div>
                    <p class="text-slate-400">نرم‌افزار <b>v2rayNG</b> یا <b>Hiddify</b> را از گوگل پلی نصب کرده، سپس لینک سابسکریپشن را کپی و در نرم‌افزار روی علامت + بزنید و گزینه Import config from clipboard را انتخاب کنید.</p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <div class="font-bold text-white flex items-center gap-2"><span>🍎</span> آیفون و آیپد (iOS)</div>
                    <p class="text-slate-400">نرم‌افزار <b>v2box</b> یا <b>Streisand</b> یا <b>Foxray</b> را از اپ استور نصب کنید و لینک را در بخش Configs اضافه کنید.</p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <div class="font-bold text-white flex items-center gap-2"><span>💻</span> ویندوز (Windows)</div>
                    <p class="text-slate-400">برنامه <b>v2rayN</b> یا <b>Nekoray</b> را اجرا کنید و با کلیدهای میانبر Ctrl + V لینک را اضافه فرمایید.</p>
                </div>
            </div>
        </section>

    </main>

    <!-- Bottom Navigation Bar -->
    <nav class="fixed bottom-0 inset-x-0 bg-slate-950/95 backdrop-blur border-t border-slate-900 px-4 py-2 z-40 flex items-center justify-around max-w-lg mx-auto">
        <button onclick="switchTab('plans')" id="nav-plans" class="flex flex-col items-center gap-1 text-blue-400 text-[10px]">
            <span class="text-xl">🛍</span>
            <span>خرید پلن</span>
        </button>
        <button onclick="switchTab('services')" id="nav-services" class="flex flex-col items-center gap-1 text-slate-400 text-[10px]">
            <span class="text-xl">📱</span>
            <span>سرویس‌های من</span>
        </button>
        <button onclick="switchTab('wallet')" id="nav-wallet" class="flex flex-col items-center gap-1 text-slate-400 text-[10px]">
            <span class="text-xl">💳</span>
            <span>شماره کارت‌ها</span>
        </button>
        <button onclick="switchTab('help')" id="nav-help" class="flex flex-col items-center gap-1 text-slate-400 text-[10px]">
            <span class="text-xl">📖</span>
            <span>راهنما</span>
        </button>
    </nav>

    <!-- Modal: Checkout Order -->
    <div id="checkoutModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-end sm:items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="font-bold text-white text-sm" id="checkout-plan-name">تکمیل خرید پلن</h3>
                <button onclick="closeCheckoutModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800/80 text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400">مبلغ قابل پرداخت:</span>
                    <span class="font-bold text-emerald-400" id="checkout-price">۰ تومان</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">حجم و مدت:</span>
                    <span class="text-slate-200" id="checkout-spec">-</span>
                </div>
            </div>

            <!-- Card Info -->
            <div id="checkout-card-info" class="p-4 rounded-2xl bg-blue-950/20 border border-blue-900/30 text-xs space-y-2"></div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">شماره پیگیری فیش یا ۴ رقم آخر کارت واریزی</label>
                <input type="text" id="receipt_text" placeholder="مثلا: پیگیری ۱۲۳۴۵۶ یا واریز از کارت صادرات" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-blue-500">
            </div>

            <button onclick="submitMiniAppOrder()" id="btn-submit-order" class="w-full py-3 px-4 font-medium text-xs shadow-lg transition btn-custom-accent">
                ثبت و ارسال فیش برای فعال‌سازی
            </button>
        </div>
    </div>

    <!-- Modal: QR Code & Config -->
    <div id="qrModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-sm p-6 text-center space-y-4 shadow-2xl">
            <h3 class="font-bold text-white text-sm">اسکن QR Code اتصال</h3>
            <div id="qrcode" class="p-4 bg-white rounded-2xl inline-block mx-auto shadow-inner"></div>
            <p class="text-[11px] text-slate-400">با دوربین یا بخش QR در نرم‌افزار وی‌پی‌ان خود اسکن نمایید.</p>
            <button onclick="closeQrModal()" class="w-full py-2.5 bg-slate-800 text-slate-300 rounded-xl text-xs">بستن</button>
        </div>
    </div>

    <script>
        // Init Telegram WebApp
        let tg = window.Telegram?.WebApp;
        if (tg) {
            tg.ready();
            tg.expand();
        }

        let currentTgUser = tg?.initDataUnsafe?.user || { id: 12345678, first_name: 'کاربر تستی' };
        let appData = { plans: [], services: [], cards: [] };
        let selectedPlan = null;

        document.getElementById('user-greeting').innerText = 'کاربر: ' + (currentTgUser.first_name || 'کاربر گرامی');

        function switchTab(tab) {
            ['plans', 'services', 'wallet', 'help'].forEach(t => {
                document.getElementById('tab-' + t).classList.add('hidden');
                document.getElementById('nav-' + t).classList.remove('text-blue-400');
                document.getElementById('nav-' + t).classList.add('text-slate-400');
            });

            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.getElementById('nav-' + tab).classList.remove('text-slate-400');
            document.getElementById('nav-' + tab).classList.add('text-blue-400');
        }

        function loadAppData() {
            const url = '/api/miniapp/data?telegram_id=' + (currentTgUser.id || '');
            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        appData = res;
                        renderPlans(res.plans);
                        renderServices(res.services);
                        renderCards(res.cards);
                    }
                })
                .catch(err => {
                    console.error('Error fetching data', err);
                });
        }

        function renderPlans(plans) {
            const container = document.getElementById('plans-container');
            if (!plans || plans.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-xs text-slate-500">هیچ پلنی یافت نشد.</div>';
                return;
            }

            container.innerHTML = plans.map(p => `
                <div class="p-4 rounded-2xl bg-slate-900/70 border border-slate-800/80 flex items-center justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="font-bold text-white text-sm">${p.name}</div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                            <span class="text-blue-400 font-mono">${p.traffic_gb} گیگابایت</span>
                            <span>•</span>
                            <span>${p.days} روزه</span>
                            <span>•</span>
                            <span>${p.ip_limit > 0 ? (p.ip_limit + ' کاربر') : 'نامحدود'}</span>
                        </div>
                    </div>
                    <div class="text-left flex flex-col items-end gap-2">
                        <div class="font-bold text-emerald-400 text-sm">${Number(p.price).toLocaleString()} <span class="text-[10px] text-slate-400 font-normal">تومان</span></div>
                        <button onclick="openCheckout(${p.id})" class="px-3.5 py-1.5 text-xs font-medium shadow-md transition btn-custom-primary">
                            خرید اشتراک
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function renderServices(services) {
            const container = document.getElementById('services-container');
            if (!services || services.length === 0) {
                container.innerHTML = '<div class="text-center py-12 text-xs text-slate-500">شما هیچ سرویس فعالی ندارید.</div>';
                return;
            }

            container.innerHTML = services.map(s => {
                const total = s.traffic_gb || 1;
                const used = s.used_gb || 0;
                const pct = Math.min(100, Math.round((used / total) * 100));

                return `
                    <div class="p-4 rounded-2xl bg-slate-900/70 border border-slate-800/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-white text-xs">اشتراک #${s.id} (${s.panel_name || 'سرور'})</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800/40">فعال</span>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs text-slate-400 mb-1">
                                <span>مصرف: ${used} گیگ از ${total} گیگ</span>
                                <span>${pct}%</span>
                            </div>
                            <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" style="width: ${pct}%"></div>
                            </div>
                        </div>

                        <div class="flex justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/60">
                            <span>انقضا: ${s.expires_at || 'نامحدود'}</span>
                            <span>اتصال همزمان: ${s.ip_limit > 0 ? (s.ip_limit + ' کاربر') : 'نامحدود'}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <button onclick="copyToClipboard('${s.sub_url || s.config_link}')" class="py-2 px-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-medium transition flex items-center justify-center gap-1">
                                <span>📋</span> کپی لینک
                            </button>
                            <button onclick="showQrModal('${s.sub_url || s.config_link}')" class="py-2 px-3 bg-blue-950/40 hover:bg-blue-900/50 text-blue-300 border border-blue-900/40 rounded-xl text-xs font-medium transition flex items-center justify-center gap-1">
                                <span>📷</span> بارکد QR
                            </button>
                        </div>
                        ${s.wireguard_conf ? `
                        <div class="pt-2">
                            <button onclick="downloadConfFile(decodeURIComponent('${encodeURIComponent(s.wireguard_conf)}'), 'wireguard_${s.id}')" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-medium transition flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20">
                                <span>📁</span> دانلود فایل کانفیگ وایرگارد (.conf)
                            </button>
                        </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }

        function renderCards(cards) {
            const container = document.getElementById('cards-container');
            if (!cards || cards.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-xs text-slate-500">شماره کارتی یافت نشد.</div>';
                return;
            }

            container.innerHTML = cards.map(c => `
                <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 space-y-3">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>${c.bank_name}</span>
                        <span class="text-white font-medium">${c.holder_name}</span>
                    </div>
                    <div class="text-center font-mono text-lg text-white tracking-widest dir-ltr select-all">
                        ${c.card_number}
                    </div>
                    <button onclick="copyToClipboard('${c.card_number}')" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium transition">
                        کپی شماره کارت
                    </button>
                </div>
            `).join('');
        }

        function openCheckout(planId) {
            selectedPlan = appData.plans.find(p => p.id == planId);
            if (!selectedPlan) return;

            document.getElementById('checkout-plan-name').innerText = selectedPlan.name;
            document.getElementById('checkout-price').innerText = Number(selectedPlan.price).toLocaleString() + ' تومان';
            document.getElementById('checkout-spec').innerText = selectedPlan.traffic_gb + ' گیگ - ' + selectedPlan.days + ' روزه';

            const card = (appData.cards && appData.cards.length > 0) ? appData.cards[0] : null;
            if (card) {
                document.getElementById('checkout-card-info').innerHTML = `
                    <div class="font-bold text-white mb-1">شماره کارت جهت واریز:</div>
                    <div class="flex justify-between text-slate-300">
                        <span>${card.bank_name} (به نام ${card.holder_name})</span>
                    </div>
                    <div class="font-mono text-sm text-yellow-300 tracking-wider dir-ltr mt-1 select-all">${card.card_number}</div>
                `;
            } else {
                document.getElementById('checkout-card-info').innerHTML = '<span class="text-rose-400">شماره کارتی ثبت نشده است.</span>';
            }

            document.getElementById('checkoutModal').classList.remove('hidden');
            document.getElementById('checkoutModal').classList.add('flex');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
            document.getElementById('checkoutModal').classList.remove('flex');
        }

        function submitMiniAppOrder() {
            if (!selectedPlan) return;
            const receipt = document.getElementById('receipt_text').value.trim();
            if (!receipt) {
                alert('لطفا شماره پیگیری فیش یا ۴ رقم آخر کارت را وارد فرمایید.');
                return;
            }

            const btn = document.getElementById('btn-submit-order');
            btn.innerText = 'در حال ثبت سفارش...';
            btn.disabled = true;

            fetch('/api/miniapp/order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    telegram_id: currentTgUser.id,
                    first_name: currentTgUser.first_name,
                    username: currentTgUser.username,
                    plan_id: selectedPlan.id,
                    receipt_text: receipt,
                })
            })
            .then(r => r.json())
            .then(res => {
                btn.innerText = 'ثبت و ارسال فیش برای فعال‌سازی';
                btn.disabled = false;
                if (res.success) {
                    if (tg?.HapticFeedback) {
                        tg.HapticFeedback.notificationOccurred('success');
                    }
                    alert('✅ ' + res.message);
                    closeCheckoutModal();
                    document.getElementById('receipt_text').value = '';
                    switchTab('services');
                    loadAppData();
                } else {
                    alert('❌ خطا: ' + (res.error || 'ناشناخته'));
                }
            })
            .catch(err => {
                btn.innerText = 'ثبت و ارسال فیش برای فعال‌سازی';
                btn.disabled = false;
                alert('خطا در ارتباط با سرور.');
            });
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ کپی شد!');
            }).catch(() => {
                prompt('متن زیر را کپی کنید:', text);
            });
        }

        function downloadConfFile(content, filename) {
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = (filename || 'wireguard') + '.conf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function showQrModal(text) {
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
                text: text,
                width: 200,
                height: 200,
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

        // Initial fetch
        loadAppData();
    </script>

</body>
</html>
