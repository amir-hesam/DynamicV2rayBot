<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">سفارشات و تایید فیش‌ها</h3>
            <p class="text-xs text-slate-400 mt-1">با تایید هر فیش، کانفیگ اختصاصی با محدودیت‌های تعیین شده روی پنل ساخته و برای خریدار ارسال می‌شود.</p>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-2 bg-slate-950 p-1.5 rounded-xl border border-slate-800 text-xs">
            <a href="/admin/orders" class="px-3 py-1.5 rounded-lg <?= empty($currentStatus) ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white' ?>">همه</a>
            <a href="/admin/orders?status=pending" class="px-3 py-1.5 rounded-lg <?= ($currentStatus === 'pending') ? 'bg-amber-600 text-white' : 'text-slate-400 hover:text-white' ?>">در انتظار بررسی</a>
            <a href="/admin/orders?status=approved" class="px-3 py-1.5 rounded-lg <?= ($currentStatus === 'approved') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white' ?>">تایید شده</a>
            <a href="/admin/orders?status=rejected" class="px-3 py-1.5 rounded-lg <?= ($currentStatus === 'rejected') ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-white' ?>">رد شده</a>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden">
        <table class="w-full text-right text-sm">
            <thead class="bg-slate-900/60 text-xs text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="p-4 font-medium">کد</th>
                    <th class="p-4 font-medium">کاربر خریدار</th>
                    <th class="p-4 font-medium">پلن درخواستی</th>
                    <th class="p-4 font-medium">مبلغ</th>
                    <th class="p-4 font-medium">فیش / کد پیگیری</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4 font-medium">تاریخ ثبت</th>
                    <th class="p-4 font-medium">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500 text-sm">هیچ سفارشی یافت نشد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="p-4 font-mono text-xs text-slate-400">#<?= $order['id'] ?></td>
                            <td class="p-4">
                                <div class="font-medium text-white"><?= htmlspecialchars($order['first_name'] ?: 'کاربر') ?></div>
                                <div class="text-xs text-slate-500 font-mono">آیدی: <?= $order['telegram_id'] ?></div>
                            </td>
                            <td class="p-4">
                                <div class="font-medium text-slate-200"><?= htmlspecialchars($order['plan_name'] ?? 'نامشخص') ?></div>
                                <div class="text-xs text-slate-500"><?= $order['traffic_gb'] ?>GB - <?= $order['days'] ?> روزه</div>
                            </td>
                            <td class="p-4 font-medium text-emerald-400"><?= number_format($order['amount']) ?> ت</td>
                            <td class="p-4">
                                <?php if (!empty($order['receipt_photo'])): ?>
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-xs bg-blue-950 text-blue-300 border border-blue-800/40 font-mono">
                                        📷 عکس فیش در تلگرام
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($order['receipt_text'])): ?>
                                    <div class="text-xs text-slate-300 font-mono mt-1 max-w-[200px] truncate" title="<?= htmlspecialchars($order['receipt_text']) ?>">
                                        <?= htmlspecialchars($order['receipt_text']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($order['status'] === 'approved'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-emerald-950 text-emerald-400 border border-emerald-800/50">تایید شده</span>
                                <?php elseif ($order['status'] === 'pending'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-amber-950 text-amber-400 border border-amber-800/50">در انتظار بررسی</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-rose-950 text-rose-400 border border-rose-800/50">رد شده</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-xs text-slate-400"><?= $order['created_at'] ?></td>
                            <td class="p-4">
                                <?php if ($order['status'] === 'pending'): ?>
                                    <div class="flex items-center gap-2">
                                        <a href="/admin/orders/approve/<?= $order['id'] ?>" onclick="return confirm('با تایید این سفارش، کانفیگ اختصاصی ساخته و برای کاربر ارسال می‌شود. ادامه می‌دهید؟')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium shadow-md shadow-emerald-600/20">
                                            ✅ تایید و صدور
                                        </a>
                                        <button onclick="rejectModal(<?= $order['id'] ?>)" class="px-3 py-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 text-xs border border-rose-900/40">
                                            ❌ رد
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">انجام شده</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h3 class="font-bold text-white text-base mb-4">رد فیش سفارش</h3>
        <form id="rejectForm" action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">دلیل رد سفارش (به کاربر پیام ارسال می‌شود)</label>
                <textarea name="reason" rows="3" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs focus:outline-none focus:border-rose-500">مبلغ واریزی مغایرت دارد یا فیش ارسالی نامعتبر است.</textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs">انصراف</button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-medium">تایید و رد سفارش</button>
            </div>
        </form>
    </div>
</div>

<script>
function rejectModal(orderId) {
    document.getElementById('rejectForm').action = '/admin/orders/reject/' + orderId;
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
