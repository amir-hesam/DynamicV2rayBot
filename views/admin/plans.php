<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">تعرفه‌ها و پلن‌های فروش</h3>
            <p class="text-xs text-slate-400 mt-1">تعریف پلن‌ها با محدودیت حجم (GB)، مدت زمان (روز) و محدودیت کاربر همزمان (IP Limit)</p>
        </div>
        <button onclick="openPlanModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition shadow-lg shadow-blue-600/20 flex items-center gap-2">
            <span>➕</span> افزودن پلن جدید
        </button>
    </div>

    <!-- Plans Table -->
    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden">
        <table class="w-full text-right text-sm">
            <thead class="bg-slate-900/60 text-xs text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="p-4 font-medium">ردیف</th>
                    <th class="p-4 font-medium">نام پلن</th>
                    <th class="p-4 font-medium">حجم</th>
                    <th class="p-4 font-medium">مدت اعتبار</th>
                    <th class="p-4 font-medium">محدودیت (IP / HWID)</th>
                    <th class="p-4 font-medium">قیمت (تومان)</th>
                    <th class="p-4 font-medium">سرور / پنل</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4 font-medium">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <?php if (empty($plans)): ?>
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-500 text-sm">پلنی یافت نشد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plans as $idx => $plan): ?>
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="p-4 font-mono text-xs text-slate-500"><?= $idx + 1 ?></td>
                            <td class="p-4 font-semibold text-white">
                                <?= htmlspecialchars($plan['name']) ?>
                                <?php if (!empty($plan['description'])): ?>
                                    <div class="text-xs font-normal text-slate-500 mt-0.5"><?= htmlspecialchars($plan['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-mono text-blue-400"><?= $plan['traffic_gb'] ?> GB</td>
                            <td class="p-4"><?= $plan['days'] ?> روز</td>
                            <td class="p-4">
                                <div class="flex flex-col gap-1 text-[11px]">
                                    <span class="px-2 py-0.5 rounded bg-blue-950/40 text-blue-300 border border-blue-800/30">
                                        🌐 IP: <?= ($plan['ip_limit'] > 0) ? $plan['ip_limit'] . ' کاربر' : 'نامحدود' ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-purple-950/40 text-purple-300 border border-purple-800/30">
                                        📱 HWID: <?= (!empty($plan['hwid_limit']) && $plan['hwid_limit'] > 0) ? $plan['hwid_limit'] . ' دستگاه' : 'نامحدود' ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 font-medium text-emerald-400"><?= number_format($plan['price']) ?></td>
                            <td class="p-4 text-xs text-slate-400">
                                <?= $plan['panel_name'] ? htmlspecialchars($plan['panel_name']) : '<span class="text-blue-400">انتخاب خودکار (هوشمند)</span>' ?>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-xs <?= $plan['is_active'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-slate-800 text-slate-400' ?>">
                                    <?= $plan['is_active'] ? 'فعال' : 'غیرفعال' ?>
                                </span>
                            </td>
                            <td class="p-4 flex items-center gap-2">
                                <button onclick='editPlan(<?= json_encode($plan) ?>)' class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs border border-slate-800">ویرایش</button>
                                <a href="/admin/plans/delete/<?= $plan['id'] ?>" onclick="return confirm('آیا از حذف این پلن مطمئن هستید؟')" class="px-2.5 py-1.5 rounded-lg bg-rose-950/30 hover:bg-rose-900/40 text-rose-400 text-xs border border-rose-900/30">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create / Edit Plan -->
<div id="planModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
            <h3 id="planModalTitle" class="font-bold text-white text-base">افزودن پلن جدید</h3>
            <button onclick="closePlanModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="/admin/plans/save" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="plan_id" value="">

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">عنوان پلن</label>
                <input type="text" name="name" id="plan_name_input" required placeholder="مثلا: پلن ۱ ماهه پرسرعت" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">توضیحات کوتاه (اختیاری)</label>
                <input type="text" name="description" id="plan_description" placeholder="مثلا: لوکیشن آلمان با پینگ پایین" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">حجم (گیگابایت GB)</label>
                    <input type="number" name="traffic_gb" id="plan_traffic_gb" required value="30" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">مدت اعتبار (روز)</label>
                    <input type="number" name="days" id="plan_days" required value="30" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">قیمت (تومان)</label>
                    <input type="number" name="price" id="plan_price" required value="89000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">محدودیت IP</label>
                    <input type="number" name="ip_limit" id="plan_ip_limit" value="1" placeholder="0 برای نامحدود" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                    <span class="text-[10px] text-slate-500">0 = نامحدود</span>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">محدودیت HWID</label>
                    <input type="number" name="hwid_limit" id="plan_hwid_limit" value="0" placeholder="0 برای نامحدود" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500">
                    <span class="text-[10px] text-slate-500">0 = نامحدود</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">سرور / پنل متصل</label>
                    <select name="panel_id" id="plan_panel_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
                        <option value="0">انتخاب خودکار (لود بالانسینگ)</option>
                        <?php foreach ($panels as $pn): ?>
                            <option value="<?= $pn['id'] ?>"><?= htmlspecialchars($pn['name']) ?> (<?= $pn['type'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">ترتیب نمایش</label>
                    <input type="number" name="sort_order" id="plan_sort_order" value="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="plan_is_active" value="1" checked class="w-4 h-4 rounded text-blue-600 bg-slate-950 border-slate-800">
                <label for="plan_is_active" class="text-xs text-slate-300">پلن فعال و در ربات و مینی‌اپ قابل خرید باشد</label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" onclick="closePlanModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs">انصراف</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium">ذخیره پلن</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPlanModal() {
    document.getElementById('planModalTitle').innerText = 'افزودن پلن جدید';
    document.getElementById('plan_id').value = '';
    document.getElementById('plan_name_input').value = '';
    document.getElementById('plan_description').value = '';
    document.getElementById('plan_traffic_gb').value = '30';
    document.getElementById('plan_days').value = '30';
    document.getElementById('plan_price').value = '89000';
    document.getElementById('plan_ip_limit').value = '1';
    document.getElementById('plan_hwid_limit').value = '0';
    document.getElementById('plan_panel_id').value = '0';
    document.getElementById('plan_sort_order').value = '0';
    document.getElementById('plan_is_active').checked = true;
    document.getElementById('planModal').classList.remove('hidden');
    document.getElementById('planModal').classList.add('flex');
}

function editPlan(data) {
    document.getElementById('planModalTitle').innerText = 'ویرایش پلن: ' + data.name;
    document.getElementById('plan_id').value = data.id;
    document.getElementById('plan_name_input').value = data.name;
    document.getElementById('plan_description').value = data.description || '';
    document.getElementById('plan_traffic_gb').value = data.traffic_gb;
    document.getElementById('plan_days').value = data.days;
    document.getElementById('plan_price').value = data.price;
    document.getElementById('plan_ip_limit').value = data.ip_limit;
    document.getElementById('plan_hwid_limit').value = data.hwid_limit || '0';
    document.getElementById('plan_panel_id').value = data.panel_id || '0';
    document.getElementById('plan_sort_order').value = data.sort_order || '0';
    document.getElementById('plan_is_active').checked = data.is_active == 1;
    document.getElementById('planModal').classList.remove('hidden');
    document.getElementById('planModal').classList.add('flex');
}

function closePlanModal() {
    document.getElementById('planModal').classList.add('hidden');
    document.getElementById('planModal').classList.remove('flex');
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
