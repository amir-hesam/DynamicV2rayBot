<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-lg">مدیریت شماره کارت‌ها جهت واریز</h3>
            <p class="text-xs text-slate-400 mt-1">شماره کارت‌های فعال برای نمایش به کاربران هنگام خرید در ربات و مینی‌اپ</p>
        </div>
        <button onclick="openCardModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition shadow-lg shadow-blue-600/20 flex items-center gap-2">
            <span>➕</span> افزودن شماره کارت جدید
        </button>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($cards)): ?>
            <div class="col-span-full bg-slate-950/60 border border-slate-800 rounded-2xl p-10 text-center text-slate-500 text-sm">
                هیچ شماره کارتی تعریف نشده است.
            </div>
        <?php else: ?>
            <?php foreach ($cards as $c): ?>
                <div class="bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-blue-500/10 rounded-full blur-xl pointer-events-none"></div>

                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-sm font-semibold text-slate-300"><?= htmlspecialchars($c['bank_name']) ?></span>
                            <span class="px-2.5 py-1 rounded-full text-xs <?= $c['is_active'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-slate-800 text-slate-400' ?>">
                                <?= $c['is_active'] ? 'فعال' : 'غیرفعال' ?>
                            </span>
                        </div>

                        <div class="font-mono text-xl text-white tracking-widest text-center mb-6 dir-ltr select-all">
                            <?= chunk_split($c['card_number'], 4, ' ') ?>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>صاحب حساب:</span>
                            <span class="font-semibold text-slate-200"><?= htmlspecialchars($c['holder_name']) ?></span>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-800/80 flex items-center justify-end gap-2 mt-4">
                        <button onclick='editCard(<?= json_encode($c) ?>)' class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs">ویرایش</button>
                        <a href="/admin/cards/delete/<?= $c['id'] ?>" onclick="return confirm('حذف کارت؟')" class="px-3 py-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 text-xs border border-rose-900/40">حذف</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Card -->
<div id="cardModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-800">
            <h3 id="cardModalTitle" class="font-bold text-white text-base">افزودن کارت</h3>
            <button onclick="closeCardModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="/admin/cards/save" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="card_id" value="">

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">شماره کارت ۱۶ رقمی</label>
                <input type="text" name="card_number" id="card_number_input" required placeholder="60379974..." maxlength="19" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm font-mono tracking-wider focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">نام صاحب حساب</label>
                <input type="text" name="holder_name" id="card_holder" required placeholder="علی محمدی" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">نام بانک</label>
                <input type="text" name="bank_name" id="card_bank" required placeholder="بانک ملی / ملت / سامان" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="card_is_active" value="1" checked class="w-4 h-4 rounded text-blue-600 bg-slate-950 border-slate-800">
                <label for="card_is_active" class="text-xs text-slate-300">کارت فعال باشد</label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" onclick="closeCardModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs">انصراف</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium">ذخیره کارت</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCardModal() {
    document.getElementById('cardModalTitle').innerText = 'افزودن کارت بانکی جدید';
    document.getElementById('card_id').value = '';
    document.getElementById('card_number_input').value = '';
    document.getElementById('card_holder').value = '';
    document.getElementById('card_bank').value = '';
    document.getElementById('card_is_active').checked = true;
    document.getElementById('cardModal').classList.remove('hidden');
    document.getElementById('cardModal').classList.add('flex');
}

function editCard(data) {
    document.getElementById('cardModalTitle').innerText = 'ویرایش کارت بانکی';
    document.getElementById('card_id').value = data.id;
    document.getElementById('card_number_input').value = data.card_number;
    document.getElementById('card_holder').value = data.holder_name;
    document.getElementById('card_bank').value = data.bank_name;
    document.getElementById('card_is_active').checked = data.is_active == 1;
    document.getElementById('cardModal').classList.remove('hidden');
    document.getElementById('cardModal').classList.add('flex');
}

function closeCardModal() {
    document.getElementById('cardModal').classList.add('hidden');
    document.getElementById('cardModal').classList.remove('flex');
}
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
