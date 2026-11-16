<?php ob_start(); ?>

<div class="space-y-6 max-w-2xl">
    <div>
        <h3 class="font-bold text-white text-lg">ارسال پیام همگانی به تمام کاربران</h3>
        <p class="text-xs text-slate-400 mt-1">ارسال پیام اطلاعیه، تغییر آدرس سرور یا تخفیف‌ها به تمام کاربران ربات</p>
    </div>

    <form action="/admin/broadcast" method="POST" class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 space-y-4">
        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">متن پیام ارسالی (پشتیبانی از تگ‌های HTML مثل &lt;b&gt;, &lt;code&gt;)</label>
            <textarea name="message" rows="6" required placeholder="متن پیام همگانی خود را اینجا بنویسید..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm focus:border-blue-500 leading-relaxed"></textarea>
        </div>

        <div class="flex items-center justify-between pt-2">
            <span class="text-xs text-slate-500">ارسال با مکث ۵۰ میلی‌ثانیه‌ای جهت عدم مسدودی توسط تلگرام انجام می‌شود.</span>
            <button type="submit" onclick="return confirm('آیا از ارسال این پیام به تمام کاربران اطمینان دارید؟')" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium shadow-lg shadow-blue-600/30 transition">
                🚀 ارسال به همه
            </button>
        </div>
    </form>
</div>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
