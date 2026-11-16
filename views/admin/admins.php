<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">مدیریت ادمین‌ها و سطوح دسترسی (RBAC)</h3>
            <p class="text-xs text-slate-400 mt-1">تعریف مدیران و اپراتورهای مختلف با دسترسی‌های تفکیک‌شده (مالی، سرورها، تایید فیش، پشتیبانی)</p>
        </div>
        <button onclick="openAdminModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium transition shadow-lg shadow-blue-600/20 flex items-center gap-2">
            <span>➕</span> افزودن ادمین جدید
        </button>
    </div>

    <!-- Admins Table -->
    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl overflow-hidden">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-900/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="p-4 font-medium">نام</th>
                    <th class="p-4 font-medium">نام کاربری</th>
                    <th class="p-4 font-medium">نقش</th>
                    <th class="p-4 font-medium">دسترسی‌ها</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4 font-medium">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <?php foreach ($admins as $adm): ?>
                    <?php 
                        $perms = json_decode($adm['permissions'] ?? '[]', true) ?: [];
                        $isSuper = ($adm['role'] === 'super_admin');
                    ?>
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="p-4 font-semibold text-white"><?= htmlspecialchars($adm['name']) ?></td>
                        <td class="p-4 font-mono text-blue-400"><?= htmlspecialchars($adm['username']) ?></td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-[11px] <?= $isSuper ? 'bg-purple-950 text-purple-300 border border-purple-800/40' : 'bg-slate-800 text-slate-300' ?>">
                                <?= $isSuper ? 'مدیر کل (Super Admin)' : 'اپراتور (Operator)' ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <?php if ($isSuper || in_array('*', $perms)): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800/40">دسترسی کامل به همه بخش‌ها</span>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-1 max-w-sm">
                                    <?php 
                                        $labels = [
                                            'orders' => 'سفارشات',
                                            'panels' => 'سرورها',
                                            'plans' => 'پلن‌ها',
                                            'cards' => 'کارت‌ها',
                                            'customization' => 'دکمه‌ها و رنگ',
                                            'users' => 'کاربران',
                                            'broadcast' => 'پیام همگانی',
                                            'admins' => 'ادمین‌ها'
                                        ];
                                        foreach ($perms as $p):
                                    ?>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300"><?= $labels[$p] ?? $p ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] <?= $adm['is_active'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-rose-950 text-rose-400 border border-rose-800/40' ?>">
                                <?= $adm['is_active'] ? 'فعال' : 'غیرفعال' ?>
                            </span>
                        </td>
                        <td class="p-4 flex items-center gap-2">
                            <button onclick='editAdmin(<?= json_encode($adm) ?>)' class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs border border-slate-800">ویرایش</button>
                            <?php if (!$isSuper): ?>
                                <a href="/admin/admins/delete/<?= $adm['id'] ?>" onclick="return confirm('حذف این ادمین؟')" class="px-2.5 py-1.5 rounded-lg bg-rose-950/40 text-rose-300 text-xs border border-rose-900/40 hover:bg-rose-900/60">حذف</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add / Edit Admin -->
<div id="adminModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 id="adminModalTitle" class="font-bold text-white text-sm">افزودن ادمین جدید</h3>
            <button onclick="closeAdminModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="/admin/admins/save" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="admin_id" value="">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">نام ادمین</label>
                    <input type="text" name="name" id="admin_name" required placeholder="مثلا: علی پشتیبانی" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">نام کاربری ورود</label>
                    <input type="text" name="username" id="admin_username" required placeholder="support1" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs font-mono focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">رمز عبور (خالی بگذارید تا تغییر نکند)</label>
                    <input type="password" name="password" id="admin_password" placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">نقش کاربری</label>
                    <select name="role" id="admin_role" onchange="togglePerms(this.value)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                        <option value="operator">اپراتور با دسترسی‌های انتخابی</option>
                        <option value="super_admin">مدیر کل (دسترسی نامحدود)</option>
                    </select>
                </div>
            </div>

            <div id="permissions-container" class="space-y-2 pt-2 border-t border-slate-800/80">
                <label class="block text-xs font-medium text-slate-300 mb-2">انتخاب دسترسی‌های مجاز:</label>
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-300">
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="orders" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>📥 سفارشات و تایید فیش</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="panels" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>🌐 سرورها و پنل‌های سنایی/پاسارگاد</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="plans" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>📋 تعرفه‌ها و پلن‌ها</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="cards" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>💳 تنظیم شماره کارت‌ها</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="customization" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>🔲 چیدمان، متن و رنگ دکمه‌ها</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="users" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>👤 کاربران ربات</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="broadcast" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>📢 ارسال پیام همگانی</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-950 border border-slate-800/80">
                        <input type="checkbox" name="permissions[]" value="admins" class="perm-cb rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>👥 مدیریت ادمین‌ها</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="admin_is_active" value="1" checked class="w-4 h-4 rounded text-blue-600 bg-slate-950 border-slate-800">
                <label for="admin_is_active" class="text-xs text-slate-300">حساب کاربری فعال باشد و اجازه ورود داشته باشد</label>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeAdminModal()" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs">انصراف</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium">ذخیره ادمین</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdminModal() {
    document.getElementById('adminModalTitle').innerText = 'افزودن ادمین جدید';
    document.getElementById('admin_id').value = '';
    document.getElementById('admin_name').value = '';
    document.getElementById('admin_username').value = '';
    document.getElementById('admin_password').value = '';
    document.getElementById('admin_role').value = 'operator';
    document.getElementById('admin_is_active').checked = true;
    document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = false);
    togglePerms('operator');
    document.getElementById('adminModal').classList.remove('hidden');
    document.getElementById('adminModal').classList.add('flex');
}

function editAdmin(data) {
    document.getElementById('adminModalTitle').innerText = 'ویرایش ادمین: ' + data.name;
    document.getElementById('admin_id').value = data.id;
    document.getElementById('admin_name').value = data.name;
    document.getElementById('admin_username').value = data.username;
    document.getElementById('admin_password').value = '';
    document.getElementById('admin_role').value = data.role;
    document.getElementById('admin_is_active').checked = data.is_active == 1;

    let perms = [];
    try {
        perms = JSON.parse(data.permissions || '[]');
    } catch(e) {}

    document.querySelectorAll('.perm-cb').forEach(cb => {
        cb.checked = perms.includes('*') || perms.includes(cb.value);
    });

    togglePerms(data.role);
    document.getElementById('adminModal').classList.remove('hidden');
    document.getElementById('adminModal').classList.add('flex');
}

function togglePerms(role) {
    const container = document.getElementById('permissions-container');
    if (role === 'super_admin') {
        container.style.opacity = '0.4';
        container.style.pointerEvents = 'none';
    } else {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
    }
}

function closeAdminModal() {
    document.getElementById('adminModal').classList.add('hidden');
    document.getElementById('adminModal').classList.remove('flex');
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
