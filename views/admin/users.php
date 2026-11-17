<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">کاربران ثبت شده در ربات</h3>
            <p class="text-xs text-slate-400 mt-1">لیست تمام کاربرانی که تا کنون ربات را استارت زده‌اند</p>
        </div>
        <span class="text-xs px-3 py-1.5 rounded-xl bg-slate-800 text-slate-300">مجموع: <?= count($users) ?> کاربر</span>
    </div>

    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden">
        <table class="w-full text-right text-sm">
            <thead class="bg-slate-900/60 text-xs text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="p-4 font-medium">ردیف</th>
                    <th class="p-4 font-medium">نام کاربر</th>
                    <th class="p-4 font-medium">نام کاربری</th>
                    <th class="p-4 font-medium">شناسه عددی (Telegram ID)</th>
                    <th class="p-4 font-medium">نقش</th>
                    <th class="p-4 font-medium">موجودی</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4 font-medium">تاریخ عضویت</th>
                    <th class="p-4 font-medium">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-500 text-sm">کاربری یافت نشد.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $adminTgId = \App\Models\Setting::get('admin_telegram_id');
                    foreach ($users as $idx => $user): 
                        $isAdminUser = !empty($user['is_admin']) || ((string)$user['telegram_id'] === (string)$adminTgId);
                    ?>
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="p-4 font-mono text-xs text-slate-500"><?= $idx + 1 ?></td>
                            <td class="p-4 font-semibold text-white"><?= htmlspecialchars($user['first_name'] ?: '-') ?></td>
                            <td class="p-4 font-mono text-xs text-blue-400"><?= $user['username'] ? '@' . htmlspecialchars($user['username']) : '-' ?></td>
                            <td class="p-4 font-mono text-xs text-slate-400"><?= $user['telegram_id'] ?></td>
                            <td class="p-4">
                                <?php if ($isAdminUser): ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-amber-950/60 text-amber-400 border border-amber-800/40 font-bold">
                                        👑 مدیر کل
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-slate-800 text-slate-400 border border-slate-700">
                                        کاربر عادی
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-mono text-xs text-emerald-400"><?= number_format($user['balance'] ?? 0) ?> ت</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-xs <?= $user['is_banned'] ? 'bg-rose-950 text-rose-400 border border-rose-800/40' : 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' ?>">
                                    <?= $user['is_banned'] ? 'مسدود شده' : 'فعال' ?>
                                </span>
                            </td>
                            <td class="p-4 text-xs text-slate-500"><?= $user['created_at'] ?></td>
                            <td class="p-4">
                                <a href="/admin/users/toggle-ban/<?= $user['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs <?= $user['is_banned'] ? 'bg-emerald-950/40 text-emerald-300 border border-emerald-800/40' : 'bg-rose-950/40 text-rose-300 border border-rose-800/40' ?>">
                                    <?= $user['is_banned'] ? 'رفع مسدودی' : 'مسدود کردن' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
