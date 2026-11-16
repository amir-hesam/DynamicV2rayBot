<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 text-sm mb-2">
                <span>کل کاربران ربات</span>
                <span class="text-xl">👥</span>
            </div>
            <div class="text-3xl font-bold text-white"><?= number_format($stats['users']) ?></div>
            <div class="text-xs text-slate-500 mt-2">عضو در دیتابیس</div>
        </div>

        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 text-sm mb-2">
                <span>سفارشات در انتظار تایید</span>
                <span class="text-xl">⏳</span>
            </div>
            <div class="text-3xl font-bold text-amber-400"><?= number_format($stats['pending']) ?></div>
            <div class="text-xs text-amber-500/80 mt-2">نیازمند بررسی فیش کارت به کارت</div>
        </div>

        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 text-sm mb-2">
                <span>مجموع فروش موفق</span>
                <span class="text-xl">💰</span>
            </div>
            <div class="text-3xl font-bold text-emerald-400"><?= number_format($stats['revenue']) ?> <span class="text-xs font-normal text-slate-400">تومان</span></div>
            <div class="text-xs text-emerald-500/80 mt-2"><?= number_format($stats['orders']) ?> کل سفارش‌ها</div>
        </div>

        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 text-sm mb-2">
                <span>سرورها و کانفیگ‌ها</span>
                <span class="text-xl">⚡</span>
            </div>
            <div class="text-3xl font-bold text-blue-400"><?= $stats['panels'] ?> <span class="text-xs font-normal text-slate-400">پنل فعال</span></div>
            <div class="text-xs text-blue-500/80 mt-2"><?= $stats['services'] ?> کانفیگ فعال صادر شده</div>
        </div>
    </div>

    <!-- Quick Actions & Links -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-white text-base">آخرین سفارشات ثبت شده</h3>
                <a href="/admin/orders" class="text-xs text-blue-400 hover:underline">مشاهده همه سفارشات &larr;</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div class="text-center py-10 text-slate-500 text-sm">هیچ سفارشی هنوز ثبت نشده است.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="text-xs text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="pb-3 font-medium">کد</th>
                                <th class="pb-3 font-medium">کاربر</th>
                                <th class="pb-3 font-medium">پلن</th>
                                <th class="pb-3 font-medium">مبلغ</th>
                                <th class="pb-3 font-medium">وضعیت</th>
                                <th class="pb-3 font-medium">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td class="py-3 font-mono text-xs text-slate-400">#<?= $order['id'] ?></td>
                                    <td class="py-3">
                                        <?= htmlspecialchars($order['first_name'] ?: 'کاربر') ?>
                                        <span class="text-xs text-slate-500 font-mono">(<?= $order['telegram_id'] ?>)</span>
                                    </td>
                                    <td class="py-3 text-slate-200"><?= htmlspecialchars($order['plan_name'] ?? '-') ?></td>
                                    <td class="py-3 font-medium"><?= number_format($order['amount']) ?> ت</td>
                                    <td class="py-3">
                                        <?php if ($order['status'] === 'approved'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs bg-emerald-950 text-emerald-400 border border-emerald-800/50">تایید شده</span>
                                        <?php elseif ($order['status'] === 'pending'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs bg-amber-950 text-amber-400 border border-amber-800/50">در انتظار</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs bg-rose-950 text-rose-400 border border-rose-800/50">رد شده</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <a href="/admin/orders/approve/<?= $order['id'] ?>" class="text-xs text-emerald-400 hover:underline ml-2">تایید</a>
                                        <?php endif; ?>
                                        <a href="/admin/orders" class="text-xs text-blue-400 hover:underline">جزئیات</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <!-- Quick Links Card -->
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                <h3 class="font-bold text-white text-base mb-4">دسترسی سریع</h3>
                <div class="space-y-2.5">
                    <a href="/admin/panels" class="block p-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800/80 transition flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2"><span>➕</span> افزودن پنل سنایی یا پاسارگاد</span>
                        <span class="text-slate-500">&rarr;</span>
                    </a>
                    <a href="/admin/customization" class="block p-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800/80 transition flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2"><span>🎨</span> تنظیم رنگ دکمه‌ها و متون ربات</span>
                        <span class="text-slate-500">&rarr;</span>
                    </a>
                    <a href="/admin/cards" class="block p-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800/80 transition flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2"><span>💳</span> تنظیم شماره کارت بانکی</span>
                        <span class="text-slate-500">&rarr;</span>
                    </a>
                    <a href="/miniapp" target="_blank" class="block p-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800/80 transition flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2"><span>🚀</span> تست مینی‌اپ در مرورگر</span>
                        <span class="text-emerald-400">باز کردن</span>
                    </a>
                </div>
            </div>

            <!-- Bot Status Info -->
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                <h3 class="font-bold text-white text-base mb-3">وضعیت سیستم</h3>
                <div class="text-xs text-slate-400 space-y-2">
                    <div class="flex justify-between py-1 border-b border-slate-900">
                        <span>نسخه PHP:</span>
                        <span class="font-mono text-slate-300"><?= phpversion() ?></span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-900">
                        <span>دیتابیس:</span>
                        <span class="text-slate-300">SQLite (فعال)</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>پشتیبانی پنل‌ها:</span>
                        <span class="text-blue-400">سنایی (3x-ui) + پاسارگاد</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
