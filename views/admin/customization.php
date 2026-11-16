<?php ob_start(); ?>

<div class="space-y-6">
    <div>
        <h3 class="font-bold text-white text-lg">شخصی‌سازی دکمه‌ها، ظاهر و تنظیمات ربات</h3>
        <p class="text-xs text-slate-400 mt-1">تغییر متن دکمه‌های کیبورد ربات، پالت رنگی دکمه‌ها و استایل مینی‌اپ، توکن ربات و متون سیستم</p>
    </div>

    <form action="/admin/customization/save" method="POST" class="space-y-8">
        
        <!-- Section 1: Button Colors & Mini App Styles -->
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
            <h4 class="font-bold text-white text-base mb-2 flex items-center gap-2">
                <span>🎨</span> رنگ‌بندی و استایل دکمه‌های مینی‌اپ
            </h4>
            <p class="text-xs text-slate-400 mb-6">رنگ دکمه‌ها، فونت و استایل ظاهری در مینی‌اپ تلگرام بلافاصله بر اساس تنظیمات زیر اعمال می‌شود.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">رنگ اصلی دکمه‌ها (Primary Color)</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="color_primary_picker" value="<?= htmlspecialchars($settings['miniapp_primary_color'] ?? '#2563eb') ?>" class="w-12 h-10 rounded-xl bg-transparent border-0 cursor-pointer" oninput="updateColor('miniapp_primary_color', this.value)">
                        <input type="text" name="miniapp_primary_color" id="miniapp_primary_color" value="<?= htmlspecialchars($settings['miniapp_primary_color'] ?? '#2563eb') ?>" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-sm font-mono" oninput="updateColor('color_primary_picker', this.value)">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">رنگ دوم و تاکیدی (Accent Color)</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="color_accent_picker" value="<?= htmlspecialchars($settings['miniapp_accent_color'] ?? '#10b981') ?>" class="w-12 h-10 rounded-xl bg-transparent border-0 cursor-pointer" oninput="updateColor('miniapp_accent_color', this.value)">
                        <input type="text" name="miniapp_accent_color" id="miniapp_accent_color" value="<?= htmlspecialchars($settings['miniapp_accent_color'] ?? '#10b981') ?>" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-sm font-mono" oninput="updateColor('color_accent_picker', this.value)">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">رنگ متن داخل دکمه‌ها</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="color_text_picker" value="<?= htmlspecialchars($settings['miniapp_btn_text_color'] ?? '#ffffff') ?>" class="w-12 h-10 rounded-xl bg-transparent border-0 cursor-pointer" oninput="updateColor('miniapp_btn_text_color', this.value)">
                        <input type="text" name="miniapp_btn_text_color" id="miniapp_btn_text_color" value="<?= htmlspecialchars($settings['miniapp_btn_text_color'] ?? '#ffffff') ?>" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-sm font-mono" oninput="updateColor('color_text_picker', this.value)">
                    </div>
                </div>
            </div>

            <!-- Live Preview Card -->
            <div class="mt-6 p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between flex-wrap gap-4">
                <span class="text-xs text-slate-400">پیش‌نمایش زنده دکمه مینی‌اپ:</span>
                <div class="flex items-center gap-3">
                    <button type="button" id="preview_btn_1" class="px-6 py-2.5 rounded-xl font-medium text-sm transition shadow-lg" style="background-color: <?= $settings['miniapp_primary_color'] ?? '#2563eb' ?>; color: <?= $settings['miniapp_btn_text_color'] ?? '#ffffff' ?>;">
                        🛍 خرید اشتراک (دکمه اصلی)
                    </button>
                    <button type="button" id="preview_btn_2" class="px-6 py-2.5 rounded-xl font-medium text-sm transition shadow-lg" style="background-color: <?= $settings['miniapp_accent_color'] ?? '#10b981' ?>; color: <?= $settings['miniapp_btn_text_color'] ?? '#ffffff' ?>;">
                        ✅ پرداخت موفق (دکمه دوم)
                    </button>
                </div>
            </div>
        </div>

        <!-- Section 2: Customizable Button Texts in Telegram Bot -->
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
            <h4 class="font-bold text-white text-base mb-2 flex items-center gap-2">
                <span>⌨️</span> تنظیم متن دکمه‌های منوی ربات تلگرام
            </h4>
            <p class="text-xs text-slate-400 mb-6">متن هر یک از دکمه‌های کیبورد ربات را به دلخواه خود بنویسید (شامل ایموجی):</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه خرید اشتراک</label>
                    <input type="text" name="btn_buy" value="<?= htmlspecialchars($settings['btn_buy'] ?? '🛍 خرید اشتراک جدید') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه سرویس‌های فعال من</label>
                    <input type="text" name="btn_my_services" value="<?= htmlspecialchars($settings['btn_my_services'] ?? '📱 سرویس‌های فعال من') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه تعرفه‌ها و پلن‌ها</label>
                    <input type="text" name="btn_plans" value="<?= htmlspecialchars($settings['btn_plans'] ?? '📋 تعرفه و پلن‌ها') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه کیف پول و کارت‌ها</label>
                    <input type="text" name="btn_wallet" value="<?= htmlspecialchars($settings['btn_wallet'] ?? '💳 کارت به کارت و شارژ') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه راهنمای اتصال</label>
                    <input type="text" name="btn_help" value="<?= htmlspecialchars($settings['btn_help'] ?? '📖 راهنمای اتصال') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه پشتیبانی تلگرام</label>
                    <input type="text" name="btn_support" value="<?= htmlspecialchars($settings['btn_support'] ?? '📞 پشتیبانی تلگرام') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه اکانت تست رایگان</label>
                    <input type="text" name="btn_test_account" value="<?= htmlspecialchars($settings['btn_test_account'] ?? '🎁 اکانت تست رایگان') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دکمه باز کردن مینی‌اپ</label>
                    <input type="text" name="btn_miniapp" value="<?= htmlspecialchars($settings['btn_miniapp'] ?? '🚀 ورود به مینی‌اپ') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Section 3: Telegram Bot Credentials & IDs -->
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
            <h4 class="font-bold text-white text-base mb-2 flex items-center gap-2">
                <span>🤖</span> اطلاعات تلگرام و اتصال
            </h4>
            <p class="text-xs text-slate-400 mb-6">توکن دریافتی از BotFather و آیدی عددی ادمین جهت دریافت فیش‌های واریزی</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">توکن ربات تلگرام (Bot Token)</label>
                    <input type="text" name="bot_token" id="bot_token_input" value="<?= htmlspecialchars($settings['bot_token'] ?? '') ?>" placeholder="1234567890:ABCdefGhIJKlmNoPQRstuvWXyz" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm font-mono focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">آیدی عددی ادمین (Admin Telegram ID)</label>
                    <input type="text" name="admin_telegram_id" value="<?= htmlspecialchars($settings['admin_telegram_id'] ?? '') ?>" placeholder="مثلا: 123456789" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm font-mono focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">آدرس URL مینی‌اپ (Mini App WebApp URL)</label>
                    <input type="text" name="miniapp_url" value="<?= htmlspecialchars($settings['miniapp_url'] ?? '') ?>" placeholder="https://your-domain.com/miniapp" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm font-mono focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Section 3.5: cPanel Shared Hosting & 1-Click Webhook Registration -->
        <div class="bg-gradient-to-br from-slate-950 to-blue-950/20 border border-blue-900/40 rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h4 class="font-bold text-white text-base flex items-center gap-2">
                        <span>⚡</span> راه‌اندازی ربات روی هاست اشتراکی cPanel (بدون نیاز به SSH)
                    </h4>
                    <p class="text-xs text-slate-400 mt-1">
                        روی هاست‌های اشتراکی سی‌پنل نیازی به ترمینال SSH ندارید! کافیست وبهوک تلگرام را با یک کلیک فعال کنید.
                    </p>
                </div>
                <span class="px-2.5 py-1 rounded-lg text-xs bg-emerald-950 text-emerald-400 border border-emerald-800/50">
                    ویژه cPanel & DirectAdmin
                </span>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">آدرس وبهوک شما (به صورت خودکار شناسایی شده):</label>
                    <?php
                        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "https://";
                        $detectedUrl = $proto . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . '/webhook';
                    ?>
                    <input type="text" id="webhook_url_input" value="<?= $detectedUrl ?>" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-emerald-400 text-xs font-mono">
                </div>

                <div class="flex items-center gap-2 flex-wrap pt-2">
                    <button type="button" onclick="setTelegramWebhook()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-medium shadow-md transition flex items-center gap-1.5">
                        <span>⚡</span> فعال‌سازی آنی وبهوک روی هاست
                    </button>
                    <button type="button" onclick="checkWebhookInfo()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-medium transition">
                        🔍 استعلام وضعیت وبهوک
                    </button>
                    <a href="/admin/webhook/delete" onclick="return confirm('حذف وبهوک؟')" class="px-3 py-2 bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 rounded-xl text-xs border border-rose-900/40 transition">
                        ❌ حذف وبهوک
                    </a>
                </div>

                <div id="webhook_status_box" class="hidden p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300 whitespace-pre-wrap"></div>
            </div>
        </div>

        <!-- Section 4: Texts & Messages -->
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
            <h4 class="font-bold text-white text-base mb-2 flex items-center gap-2">
                <span>📝</span> پیام‌ها و متون ربات
            </h4>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">پیام خوش‌آمدگویی استارت (/start)</label>
                    <textarea name="welcome_msg" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs leading-relaxed"><?= htmlspecialchars($settings['welcome_msg'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">متن راهنمای اتصال</label>
                    <textarea name="help_msg" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs leading-relaxed"><?= htmlspecialchars($settings['help_msg'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">متن پشتیبانی</label>
                    <textarea name="support_text" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs leading-relaxed"><?= htmlspecialchars($settings['support_text'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 5: Web Admin Password -->
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
            <h4 class="font-bold text-white text-base mb-2 flex items-center gap-2">
                <span>🔐</span> تغییر رمز عبور وب پنل
            </h4>
            <div class="max-w-md">
                <label class="block text-xs font-medium text-slate-300 mb-1.5">رمز عبور جدید (در صورت خالی بودن تغییر نمی‌کند)</label>
                <input type="password" name="new_password" placeholder="کلمه عبور جدید..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium shadow-lg shadow-blue-600/30 transition">
                💾 ذخیره کلیه تنظیمات
            </button>
        </div>
    </form>
</div>

<script>
function updateColor(targetId, val) {
    document.getElementById(targetId).value = val;
    const primary = document.getElementById('miniapp_primary_color').value;
    const accent = document.getElementById('miniapp_accent_color').value;
    const textColor = document.getElementById('miniapp_btn_text_color').value;

    const p1 = document.getElementById('preview_btn_1');
    const p2 = document.getElementById('preview_btn_2');
    if (p1) {
        p1.style.backgroundColor = primary;
        p1.style.color = textColor;
    }
    if (p2) {
        p2.style.backgroundColor = accent;
        p2.style.color = textColor;
    }
}

function setTelegramWebhook() {
    const url = document.getElementById('webhook_url_input').value.trim();
    if (!url) {
        alert('لطفا آدرس URL وبهوک را وارد کنید.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/webhook/set';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'webhook_url';
    input.value = url;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

function checkWebhookInfo() {
    const box = document.getElementById('webhook_status_box');
    box.classList.remove('hidden');
    box.innerText = 'در حال استعلام وضعیت وبهوک از سرورهای تلگرام...';

    fetch('/admin/webhook/info')
        .then(r => r.json())
        .then(data => {
            box.innerText = JSON.stringify(data, null, 2);
        })
        .catch(err => {
            box.innerText = 'خطا در ارتباط با تلگرام: ' + err;
        });
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
