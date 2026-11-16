<?php ob_start(); ?>

<?php
$panelLabels = \App\Panels\PanelManager::getAllPanelTypes();
?>

<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">مدیریت پنل‌ها و سرورها (چند سروره)</h3>
            <p class="text-xs text-slate-400 mt-1">پشتیبانی کامل از ۱۳ نوع پنل مختلف (سنایی، پاسارگاد، مرزبان، وایرگارد و...) با ارسال خودکار فایل .conf</p>
        </div>
        <button onclick="openPanelModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition shadow-lg shadow-blue-600/20 flex items-center gap-2">
            <span>➕</span> افزودن پنل جدید
        </button>
    </div>

    <!-- Panels Grid / Table -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($panels)): ?>
            <div class="col-span-full bg-slate-950/60 border border-slate-800 rounded-2xl p-12 text-center text-slate-400">
                <div class="text-4xl mb-3">🌐</div>
                <div class="font-medium text-white mb-1">هنوز هیچ پنلی اضافه نکرده‌اید!</div>
                <div class="text-xs text-slate-500 mb-4">برای ساخت و اتصال کانفیگ‌ها، یکی از پنل‌های زیر را اضافه کنید.</div>
                <button onclick="openPanelModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium">افزودن اولین پنل</button>
            </div>
        <?php else: ?>
            <?php foreach ($panels as $p): ?>
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-950 text-blue-300 border border-blue-800/50">
                                    <?= htmlspecialchars($panelLabels[$p['type']] ?? $p['type']) ?>
                                </span>
                                <h4 class="font-bold text-white text-base mt-2"><?= htmlspecialchars($p['name']) ?></h4>
                            </div>
                            <span class="w-3 h-3 rounded-full <?= $p['is_active'] ? 'bg-emerald-500' : 'bg-rose-500' ?>" title="<?= $p['is_active'] ? 'فعال' : 'غیرفعال' ?>"></span>
                        </div>

                        <div class="space-y-2 text-xs text-slate-400 mb-6">
                            <div class="flex justify-between">
                                <span>آدرس:</span>
                                <span class="font-mono text-slate-300 truncate max-w-[180px]"><?= htmlspecialchars($p['url']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>نام کاربری:</span>
                                <span class="font-mono text-slate-300"><?= htmlspecialchars($p['username']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>ظرفیت:</span>
                                <span><?= $p['current_clients'] ?> از <?= $p['capacity'] ?> کاربر</span>
                            </div>
                            <!-- Capacity Bar -->
                            <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                <?php 
                                    $pct = ($p['capacity'] > 0) ? min(100, round(($p['current_clients'] / $p['capacity']) * 100)) : 0;
                                ?>
                                <div class="bg-blue-500 h-full rounded-full" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-900 flex items-center justify-between gap-2">
                        <button onclick="testConnection(<?= $p['id'] ?>)" id="test-btn-<?= $p['id'] ?>" class="flex-1 py-2 px-3 bg-slate-900 hover:bg-slate-800 text-blue-400 rounded-xl text-xs font-medium border border-slate-800 transition">
                            ⚡ تست اتصال
                        </button>
                        <button onclick='editPanel(<?= json_encode($p) ?>)' class="py-2 px-3 bg-slate-900 hover:bg-slate-800 text-slate-300 rounded-xl text-xs border border-slate-800 transition">
                            ✏️ ویرایش
                        </button>
                        <a href="/admin/panels/delete/<?= $p['id'] ?>" onclick="return confirm('آیا از حذف این پنل اطمینان دارید؟')" class="py-2 px-3 bg-rose-950/30 hover:bg-rose-900/40 text-rose-400 rounded-xl text-xs border border-rose-900/30 transition">
                            🗑️
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create / Edit Panel -->
<div id="panelModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-xl p-6 shadow-2xl my-8">
        <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-800">
            <h3 id="modalTitle" class="font-bold text-white text-base">افزودن پنل جدید</h3>
            <button onclick="closePanelModal()" class="text-slate-400 hover:text-white text-xl">&times;</button>
        </div>

        <form action="/admin/panels/save" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="panel_id" value="">
            <input type="hidden" name="type" id="panel_type" value="sanaei">

            <!-- Panel Type Grid Selector (Exact layout from user screenshot) -->
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">📌 نوع پنل خود را انتخاب نمایید</label>
                <div class="grid grid-cols-2 gap-2 p-3 bg-slate-950 rounded-2xl border border-slate-800/80">
                    <!-- Row 1: Top 2 Panels -->
                    <button type="button" onclick="selectType('sanaei')" id="btn-sanaei" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">3x-ui (سنایی)</button>
                    <button type="button" onclick="selectType('marzban')" id="btn-marzban" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">مرزبان</button>

                    <!-- Row 2 -->
                    <button type="button" onclick="selectType('pasargad')" id="btn-pasargad" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">پاسارگاد</button>
                    <button type="button" onclick="selectType('hiddify')" id="btn-hiddify" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">هیدیفای</button>

                    <!-- Row 3 -->
                    <button type="button" onclick="selectType('marzneshin')" id="btn-marzneshin" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">مرزنشین</button>
                    <button type="button" onclick="selectType('manual')" id="btn-manual" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">فروش دستی (کانفیگ آماده)</button>

                    <!-- Row 4: WireGuard Specialists -->
                    <button type="button" onclick="selectType('wgdashboard')" id="btn-wgdashboard" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">WGDashboard</button>
                    <button type="button" onclick="selectType('wgmate')" id="btn-wgmate" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">WG-Mate</button>

                    <!-- Row 5: Router & Accounting -->
                    <button type="button" onclick="selectType('mikrotik')" id="btn-mikrotik" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">میکروتیک</button>
                    <button type="button" onclick="selectType('ibsng')" id="btn-ibsng" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">ibsng</button>

                    <!-- Row 6 -->
                    <button type="button" onclick="selectType('nexora')" id="btn-nexora" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">Nexora</button>
                    <button type="button" onclick="selectType('rebka')" id="btn-rebka" class="type-btn p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-xs font-medium text-center transition">ربکا</button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">عنوان دلخواه پنل</label>
                <input type="text" name="name" id="panel_name" required placeholder="مثلاً: سرور ۱ آلمان" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">آدرس پنل (URL کامل با پورت یا دامنه)</label>
                <input type="text" name="url" id="panel_url" required placeholder="https://panel.example.com:2053" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs font-mono focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">نام کاربری (یا توکن)</label>
                    <input type="text" name="username" id="panel_username" required placeholder="admin" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">رمز عبور (یا API Key)</label>
                    <input type="password" name="password" id="panel_password" required placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">حداکثر ظرفیت کاربر</label>
                    <input type="number" name="capacity" id="panel_capacity" value="500" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">شناسه اینباندها (اختیاری)</label>
                    <input type="text" name="inbounds_json" id="panel_inbounds" placeholder="[1, 2] یا خالی" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-white text-xs font-mono focus:border-blue-500">
                </div>
            </div>

            <!-- Custom Service Settings for this Panel -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="custom_service_enabled" id="panel_custom_enabled" value="1" class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-800">
                        <label for="panel_custom_enabled" class="text-xs font-bold text-white">فعالسازی «ساخت سرویس دلخواه» برای این سرور</label>
                    </div>
                    <span class="text-[10px] text-amber-400">تنظیمات اختصاصی قیمت و حجم</span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">قیمت هر ۱ گیگابایت (تومان)</label>
                        <input type="number" name="price_per_gb" id="panel_price_gb" value="4000" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">قیمت هر ۱ روز (تومان)</label>
                        <input type="number" name="price_per_day" id="panel_price_day" value="1000" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-white text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2 pt-1 text-[11px]">
                    <div>
                        <label class="block text-slate-400 mb-1">حداقل حجم (GB)</label>
                        <input type="number" name="min_gb" id="panel_min_gb" value="5" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">حداکثر حجم (GB)</label>
                        <input type="number" name="max_gb" id="panel_max_gb" value="500" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">حداقل روز</label>
                        <input type="number" name="min_days" id="panel_min_days" value="1" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">حداکثر روز</label>
                        <input type="number" name="max_days" id="panel_max_days" value="365" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-white text-xs">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="panel_is_active" value="1" checked class="w-4 h-4 rounded text-blue-600 bg-slate-950 border-slate-800">
                <label for="panel_is_active" class="text-xs text-slate-300">پنل فعال باشد و در صدور کانفیگ استفاده شود</label>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closePanelModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs">انصراف</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium shadow-md shadow-blue-600/30">ذخیره پنل</button>
            </div>
        </form>
    </div>
</div>

<script>
function selectType(type) {
    document.getElementById('panel_type').value = type;
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-500', 'shadow-md');
        btn.classList.add('bg-slate-900', 'text-slate-300', 'border-slate-800');
    });

    const activeBtn = document.getElementById('btn-' + type);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-900', 'text-slate-300', 'border-slate-800');
        activeBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-500', 'shadow-md');
    }
}

function openPanelModal() {
    document.getElementById('modalTitle').innerText = 'افزودن پنل جدید';
    document.getElementById('panel_id').value = '';
    document.getElementById('panel_name').value = '';
    document.getElementById('panel_url').value = '';
    document.getElementById('panel_username').value = '';
    document.getElementById('panel_password').value = '';
    document.getElementById('panel_inbounds').value = '[]';
    document.getElementById('panel_capacity').value = '500';
    document.getElementById('panel_custom_enabled').checked = false;
    document.getElementById('panel_price_gb').value = '4000';
    document.getElementById('panel_price_day').value = '1000';
    document.getElementById('panel_min_gb').value = '5';
    document.getElementById('panel_max_gb').value = '500';
    document.getElementById('panel_min_days').value = '1';
    document.getElementById('panel_max_days').value = '365';
    document.getElementById('panel_is_active').checked = true;
    selectType('sanaei');

    document.getElementById('panelModal').classList.remove('hidden');
    document.getElementById('panelModal').classList.add('flex');
}

function editPanel(data) {
    document.getElementById('modalTitle').innerText = 'ویرایش پنل: ' + data.name;
    document.getElementById('panel_id').value = data.id;
    document.getElementById('panel_name').value = data.name;
    document.getElementById('panel_url').value = data.url;
    document.getElementById('panel_username').value = data.username;
    document.getElementById('panel_password').value = data.password;
    document.getElementById('panel_inbounds').value = data.inbounds_json || '[]';
    document.getElementById('panel_capacity').value = data.capacity || 500;
    document.getElementById('panel_custom_enabled').checked = data.custom_service_enabled == 1;
    document.getElementById('panel_price_gb').value = data.price_per_gb || 4000;
    document.getElementById('panel_price_day').value = data.price_per_day || 1000;
    document.getElementById('panel_min_gb').value = data.min_gb || 5;
    document.getElementById('panel_max_gb').value = data.max_gb || 500;
    document.getElementById('panel_min_days').value = data.min_days || 1;
    document.getElementById('panel_max_days').value = data.max_days || 365;
    document.getElementById('panel_is_active').checked = data.is_active == 1;
    selectType(data.type || 'sanaei');

    document.getElementById('panelModal').classList.remove('hidden');
    document.getElementById('panelModal').classList.add('flex');
}

function closePanelModal() {
    document.getElementById('panelModal').classList.add('hidden');
    document.getElementById('panelModal').classList.remove('flex');
}

function testConnection(panelId) {
    const btn = document.getElementById('test-btn-' + panelId);
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ در حال تست...';
    btn.disabled = true;

    fetch('/admin/panels/test/' + panelId)
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('❌ خطا در برقراری ارتباط با سرور.');
        });
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
