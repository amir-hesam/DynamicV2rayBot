<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">مهاجرت و انتقال اطلاعات از ربات میرزا (MirzaBot)</h3>
            <p class="text-xs text-slate-400 mt-1">انتقال آنی کاربران، موجودی کیف پول‌ها، پنل‌ها، پلن‌ها، سرویس‌های فعال و استعلام زنده</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl">
            🚀
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-sm flex items-center gap-3">
            <span class="text-xl">✅</span>
            <span><?= htmlspecialchars($flash_success) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-sm flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <span><?= htmlspecialchars($flash_error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Migration Instructions Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <div class="text-amber-400 font-bold text-sm flex items-center gap-2">
                <span>1️⃣</span> فایل دیتابیس را انتخاب کنید
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">
                خروجی دیتابیس MySQL ربات میرزا (فایل <code>.sql</code> از طریق phpMyAdmin یا بک‌آپ خود میرزا) و یا فایل دیتابیس SQLite (فایل <code>.sqlite</code> یا <code>.db</code>) را آماده کنید.
            </p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <div class="text-blue-400 font-bold text-sm flex items-center gap-2">
                <span>2️⃣</span> نگاشت خودکار و هوشمند
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">
                سیستم تمام جداول <code>user</code>, <code>marzban_panel</code>, <code>product</code>, <code>invoice</code> را خوانده و با موجودی کیف پول و مشخصات در دیتابیس جدید بازنشانی می‌کند.
            </p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <div class="text-emerald-400 font-bold text-sm flex items-center gap-2">
                <span>3️⃣</span> استعلام زنده آنی (Live Sync)
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">
                تمامی سرویس‌های خریداری شده توسط کاربران به طور خودکار به پنل‌های مربوطه وصل می‌شوند و سیستم استعلام زنده مصرف و انقضا برای تک‌تک آن‌ها فعال خواهد بود.
            </p>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="p-6 md:p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-6">
        <form action="/admin/migration/upload" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="border-2 border-dashed border-slate-700 hover:border-blue-500 rounded-3xl p-8 text-center transition bg-slate-950/60 space-y-4">
                <div class="w-16 h-16 rounded-2xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center mx-auto text-3xl">
                    📁
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm">فایل دیتابیس میرزا را انتخاب یا به اینجا بکشید</h4>
                    <p class="text-xs text-slate-400 mt-1">فرمت‌های مجاز: <code>.sql</code>, <code>.sqlite</code>, <code>.db</code></p>
                </div>
                <div class="max-w-md mx-auto">
                    <input type="file" name="db_file" accept=".sql,.sqlite,.db" required class="w-full text-xs text-slate-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                </div>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-amber-500/20 transition flex items-center gap-2">
                    <span>⚡</span> شروع عملیات مهاجرت و استخراج اطلاعات
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
