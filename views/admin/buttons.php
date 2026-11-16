<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h3 class="font-bold text-white text-lg">چیدمان، جایگاه، رنگ و حذف/برداشتن دکمه‌ها</h3>
            <p class="text-xs text-slate-400 mt-1">امکان حذف کامل یک دکمه، مخفی‌سازی، افزودن دکمه جدید، تغییر ردیف (بالا/پایین) و تغییر رنگ با پیش‌نمایش زنده</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/buttons/restore" onclick="return confirm('آیا از بازنشانی چیدمان تمام دکمه‌ها به حالت پیش‌فرض اطمینان دارید؟')" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium transition border border-slate-700">
                🔄 بازنشانی پیش‌فرض
            </a>
            <button onclick="openNewButtonModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium transition shadow-lg shadow-blue-600/20 flex items-center gap-1.5">
                <span>➕</span> افزودن دکمه جدید
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Buttons Configuration List -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-900/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-4 font-medium">ردیف (Row)</th>
                            <th class="p-4 font-medium">جایگاه</th>
                            <th class="p-4 font-medium">متن و آیکون دکمه</th>
                            <th class="p-4 font-medium">رنگ دکمه</th>
                            <th class="p-4 font-medium">وضعیت</th>
                            <th class="p-4 font-medium text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php if (empty($buttons)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500 text-xs">هیچ دکمه‌ای وجود ندارد! می‌توانید دکمه‌های پیش‌فرض را بازنشانی کنید.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($buttons as $btn): ?>
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-950 text-blue-300 border border-blue-800/40 font-mono font-bold">
                                            ردیف <?= $btn['row_num'] ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-[11px] bg-slate-800 text-slate-400 font-mono">
                                            جایگاه <?= $btn['order_in_row'] ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-medium text-white">
                                        <?= htmlspecialchars($btn['title']) ?>
                                        <div class="text-[10px] text-slate-500 font-mono"><?= $btn['key'] ?></div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-md shadow-sm border border-slate-700 inline-block" style="background-color: <?= $btn['color'] ?>;"></span>
                                            <span class="font-mono text-[11px] text-slate-400"><?= $btn['color'] ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] <?= $btn['is_active'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-rose-950 text-rose-400 border border-rose-800/40' ?>">
                                            <?= $btn['is_active'] ? 'نمایش در ربات' : 'مخفی' ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick='openButtonModal(<?= json_encode($btn) ?>)' class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium shadow-md shadow-blue-600/20">
                                                ✏️ ویرایش جا/رنگ
                                            </button>
                                            <a href="/admin/buttons/delete/<?= $btn['id'] ?>" onclick="return confirm('آیا از برداشتن و حذف این دکمه اطمینان دارید؟')" class="px-2.5 py-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 text-xs border border-rose-900/40" title="برداشتن دکمه">
                                                🗑️ حذف
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Live Telegram Mockup Preview -->
        <div>
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 sticky top-20">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-800">
                    <h4 class="font-bold text-white text-xs flex items-center gap-2">
                        <span>📱</span> پیش‌نمایش چیدمان زنده تلگرام
                    </h4>
                    <span class="text-[10px] text-slate-500">Live Mockup</span>
                </div>

                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 shadow-xl space-y-3">
                    <!-- Telegram Chat Mock Bubble -->
                    <div class="bg-blue-600/10 border border-blue-500/20 rounded-2xl rounded-tr-none p-3 text-[11px] text-slate-200 leading-relaxed">
                        سلام! لطفاً جهت خرید یا مدیریت اشتراک، یکی از گزینه‌های زیر را انتخاب فرمایید:
                    </div>

                    <!-- Bot Keyboard Buttons Grid -->
                    <div class="space-y-2 pt-2 border-t border-slate-800/80">
                        <?php 
                            $grouped = [];
                            foreach ($buttons as $b) {
                                if (!$b['is_active']) continue;
                                $grouped[$b['row_num']][] = $b;
                            }
                            ksort($grouped);
                        ?>
                        <?php if (empty($grouped)): ?>
                            <div class="text-center py-4 text-[11px] text-slate-500">هیچ دکمه فعالی برای نمایش در کیبورد وجود ندارد.</div>
                        <?php else: ?>
                            <?php foreach ($grouped as $rowIdx => $rowBtns): ?>
                                <?php usort($rowBtns, fn($x, $y) => $x['order_in_row'] <=> $y['order_in_row']); ?>
                                <div class="grid grid-cols-<?= count($rowBtns) ?> gap-2">
                                    <?php foreach ($rowBtns as $b): ?>
                                        <div class="py-2.5 px-2 rounded-xl text-center text-xs font-medium shadow-sm transition border truncate select-none" style="background-color: <?= $b['color'] ?>; color: <?= $b['text_color'] ?>; border-color: rgba(255,255,255,0.1);">
                                            <?= htmlspecialchars($b['title']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-[10px] text-slate-500 mt-3 text-center">
                    دکمه‌ها در چت تلگرام به همین ردیف‌ها و ترتیبی که در بالا می‌بینید چیده می‌شوند.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit / Add Button -->
<div id="btnModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="font-bold text-white text-sm" id="btnModalTitle">تنظیم جایگاه و رنگ دکمه</h3>
            <button onclick="closeButtonModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="/admin/buttons/save" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="edit_btn_id" value="">

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">کلید عملکرد دکمه (شناسه یکتا)</label>
                <select name="key" id="edit_btn_key" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs font-mono focus:border-blue-500">
                    <option value="btn_buy">btn_buy (خرید اشتراک جدید)</option>
                    <option value="btn_my_services">btn_my_services (سرویس‌های من)</option>
                    <option value="btn_plans">btn_plans (تعرفه‌ها و پلن‌ها)</option>
                    <option value="btn_wallet">btn_wallet (شماره کارت‌ها و شارژ)</option>
                    <option value="btn_help">btn_help (راهنمای اتصال)</option>
                    <option value="btn_support">btn_support (پشتیبانی)</option>
                    <option value="btn_test_account">btn_test_account (اکانت تست)</option>
                    <option value="btn_miniapp">btn_miniapp (باز کردن مینی‌اپ)</option>
                    <option value="btn_custom">btn_custom (دکمه سفارشی متنی)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">متن روی دکمه (شامل ایموجی)</label>
                <input type="text" name="title" id="edit_btn_title" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">شماره ردیف (از بالا به پایین)</label>
                    <select name="row_num" id="edit_btn_row" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                        <option value="1">ردیف ۱ (بالاترین)</option>
                        <option value="2">ردیف ۲</option>
                        <option value="3">ردیف ۳</option>
                        <option value="4">ردیف ۴</option>
                        <option value="5">ردیف ۵</option>
                        <option value="6">ردیف ۶ (پایین‌ترین)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">ترتیب در همان ردیف</label>
                    <select name="order_in_row" id="edit_btn_order" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                        <option value="1">جایگاه ۱ (راست/اول)</option>
                        <option value="2">جایگاه ۲ (چپ/دوم)</option>
                        <option value="3">جایگاه ۳ (سوم)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">رنگ پس‌زمینه دکمه</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="edit_btn_color_picker" class="w-10 h-8 rounded-lg bg-transparent border-0 cursor-pointer" oninput="document.getElementById('edit_btn_color').value = this.value">
                        <input type="text" name="color" id="edit_btn_color" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white text-xs font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">رنگ متن دکمه</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="edit_btn_textcolor_picker" class="w-10 h-8 rounded-lg bg-transparent border-0 cursor-pointer" oninput="document.getElementById('edit_btn_textcolor').value = this.value">
                        <input type="text" name="text_color" id="edit_btn_textcolor" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white text-xs font-mono">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="edit_btn_active" value="1" class="w-4 h-4 rounded text-blue-600 bg-slate-950 border-slate-800">
                <label for="edit_btn_active" class="text-xs text-slate-300">دکمه در ربات نمایش داده شود (فعال باشد)</label>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeButtonModal()" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs">انصراف</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium shadow-md shadow-blue-600/30">ذخیره دکمه</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewButtonModal() {
    document.getElementById('btnModalTitle').innerText = 'افزودن دکمه جدید به کیبورد ربات';
    document.getElementById('edit_btn_id').value = '';
    document.getElementById('edit_btn_title').value = '';
    document.getElementById('edit_btn_key').value = 'btn_custom';
    document.getElementById('edit_btn_row').value = '1';
    document.getElementById('edit_btn_order').value = '1';
    document.getElementById('edit_btn_color').value = '#2563eb';
    document.getElementById('edit_btn_color_picker').value = '#2563eb';
    document.getElementById('edit_btn_textcolor').value = '#ffffff';
    document.getElementById('edit_btn_textcolor_picker').value = '#ffffff';
    document.getElementById('edit_btn_active').checked = true;

    document.getElementById('btnModal').classList.remove('hidden');
    document.getElementById('btnModal').classList.add('flex');
}

function openButtonModal(btn) {
    document.getElementById('btnModalTitle').innerText = 'تنظیم جایگاه، رنگ و متن دکمه';
    document.getElementById('edit_btn_id').value = btn.id;
    document.getElementById('edit_btn_title').value = btn.title;
    document.getElementById('edit_btn_key').value = btn.key || 'btn_custom';
    document.getElementById('edit_btn_row').value = btn.row_num;
    document.getElementById('edit_btn_order').value = btn.order_in_row;
    document.getElementById('edit_btn_color').value = btn.color || '#2563eb';
    document.getElementById('edit_btn_color_picker').value = btn.color || '#2563eb';
    document.getElementById('edit_btn_textcolor').value = btn.text_color || '#ffffff';
    document.getElementById('edit_btn_textcolor_picker').value = btn.text_color || '#ffffff';
    document.getElementById('edit_btn_active').checked = btn.is_active == 1;

    document.getElementById('btnModal').classList.remove('hidden');
    document.getElementById('btnModal').classList.add('flex');
}

function closeButtonModal() {
    document.getElementById('btnModal').classList.add('hidden');
    document.getElementById('btnModal').classList.remove('flex');
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
