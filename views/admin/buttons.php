<?php ob_start(); ?>

<!-- SortableJS CDN for Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h3 class="font-bold text-white text-lg">چیدمان، جایگاه و رنگ دکمه‌های ربات تلگرام</h3>
            <p class="text-xs text-slate-400 mt-1">ویرایشگر بصری درگ و دراپ (مشابه پنل‌ساز): جابجایی دکمه‌ها، افزودن و ادغام سطرها، تغییر رنگ و متن دکمه‌ها با پیش‌نمایش زنده</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/buttons/restore" onclick="return confirm('آیا از بازنشانی چیدمان تمام دکمه‌ها به حالت پیش‌فرض اطمینان دارید؟')" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium transition border border-slate-700">
                🔄 بازنشانی پیش‌فرض
            </a>
            <button type="button" onclick="addNewRow()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all border border-slate-700">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>افزودن سطر جدید</span>
            </button>
            <button type="button" onclick="saveKeyboardLayout()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-check text-xs"></i>
                <span>ذخیره نهایی چیدمان در تلگرام</span>
            </button>
        </div>
    </div>

    <!-- Info banner -->
    <div class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300 flex items-start gap-3">
        <i class="fa-solid fa-circle-info text-base text-indigo-400 mt-0.5"></i>
        <div class="leading-relaxed">
            <b>راهنمای ویرایشگر هوشمند:</b>
            دکمه‌ها را به راحتی با ماوس بین سطرها درگ کنید یا به «مخزن دکمه‌های غیرفعال» منتقل کنید. با زدن علامت <b>+</b> کنار هر سطر، می‌توانید سطر را ۲ دکمه‌ای یا ۱ دکمه‌ای (تمام‌عرض) کنید. جهت تغییر رنگ، آیکون و متن هر دکمه، روی خود دکمه کلیک فرمایید.
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Visual Keyboard Builder Container -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-950/80 border-2 border-slate-700/80 rounded-3xl p-6 backdrop-blur-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-grip text-indigo-400"></i>
                        <h4 class="font-bold text-white text-sm">چیدمان سطری دکمه‌های کیبورد ربات</h4>
                    </div>
                    <span class="text-[11px] text-slate-400">سطرها و دکمه‌ها را بکشید و رها کنید (Drag & Drop)</span>
                </div>

                <!-- Keyboard Container (Rendered dynamically by JS) -->
                <div id="keyboardContainer" class="space-y-3 min-h-[160px]"></div>
            </div>

            <!-- Unused / Inactive Buttons Dock -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border-2 border-dashed border-slate-700/80 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-300">
                        <i class="fa-solid fa-box-archive text-amber-400"></i>
                        <span>مخزن دکمه‌های استفاده‌نشده (غیرفعال)</span>
                    </div>
                    <span class="text-[11px] text-slate-500">دکمه‌هایی که اینجا قرار دهید در ربات مخفی می‌شوند</span>
                </div>
                <div id="unusedButtonsBox" class="flex flex-wrap gap-2 min-h-[48px] p-2.5 rounded-xl bg-slate-950/50 border border-slate-800/80">
                    <!-- Inactive buttons rendered here -->
                </div>
            </div>
        </div>

        <!-- Live Telegram Mockup Preview -->
        <div>
            <div class="bg-slate-950/80 border border-slate-800 rounded-3xl p-5 sticky top-20 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h4 class="font-bold text-white text-xs flex items-center gap-2">
                        <i class="fa-brands fa-telegram text-sky-400 text-base"></i>
                        <span>پیش‌نمایش زنده در چت تلگرام</span>
                    </h4>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">Live Preview</span>
                </div>

                <div class="rounded-2xl bg-slate-900/90 border border-slate-800 p-4 shadow-inner space-y-3">
                    <!-- Telegram Chat Bubble -->
                    <div class="bg-indigo-600/15 border border-indigo-500/20 rounded-2xl rounded-tr-none p-3.5 text-xs text-slate-200 leading-relaxed">
                        👋 سلام کاربر گرامی!<br>
                        به ربات خوش آمدید. لطفاً گزینه مورد نظر خود را انتخاب فرمایید:
                    </div>

                    <!-- Live Telegram Buttons Mockup -->
                    <div id="telegramLiveMockup" class="space-y-2 pt-2 border-t border-slate-800">
                        <!-- Rendered by JS live -->
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800 text-[11px] text-slate-400 leading-relaxed text-center">
                    دکمه‌ها با رنگ و متنی که در بالا تعیین می‌کنید دقیقاً با همین ترتیب برای تمامی کاربران در چت تلگرام اعمال می‌شوند.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- MODAL: EDIT OR REPLACE BUTTON (مشابه مودال PanelSaz) -->
<!-- ======================================================================= -->
<div id="btnEditModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900 text-slate-100 rounded-2xl shadow-2xl p-6 space-y-4 border border-slate-700 animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-indigo-400"></i>
                <span>ویرایش عنوان، آیکون و رنگ دکمه</span>
            </h3>
            <button onclick="closeBtnEditModal()" class="text-slate-400 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Preview Box -->
        <div>
            <div class="text-xs text-slate-400 font-semibold mb-1.5">پیش‌نمایش زنده دکمه:</div>
            <div id="modalPreviewBtn" class="w-full py-3 px-4 rounded-xl text-center font-bold text-xs shadow-md transition-all duration-200 border">
                عنوان دکمه
            </div>
        </div>

        <!-- Quick Telegram Color Styles (Default, Blue, Green, Red) -->
        <div>
            <div class="text-xs text-slate-300 font-semibold mb-2">انتخاب تم رنگی سریع:</div>
            <div class="grid grid-cols-4 gap-2">
                <!-- 1. Default (Slate) -->
                <div onclick="selectModalColor('default', '#334155')" id="colorCard-default" class="color-card p-2.5 rounded-xl border-2 border-slate-700 hover:border-slate-500 cursor-pointer flex flex-col items-center justify-center gap-1 transition-all bg-slate-800/60">
                    <div class="w-6 h-6 rounded-full bg-slate-600 border border-slate-500 flex items-center justify-center text-white">
                        <i class="fa-solid fa-check text-[10px] hidden check-icon"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-300">پیش‌فرض</span>
                </div>

                <!-- 2. Blue (Primary) -->
                <div onclick="selectModalColor('primary', '#2563eb')" id="colorCard-primary" class="color-card p-2.5 rounded-xl border-2 border-slate-700 hover:border-sky-500 cursor-pointer flex flex-col items-center justify-center gap-1 transition-all bg-slate-800/60">
                    <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center">
                        <i class="fa-solid fa-check text-[10px] hidden check-icon"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-300">آبی</span>
                </div>

                <!-- 3. Green (Success) -->
                <div onclick="selectModalColor('success', '#10b981')" id="colorCard-success" class="color-card p-2.5 rounded-xl border-2 border-slate-700 hover:border-emerald-500 cursor-pointer flex flex-col items-center justify-center gap-1 transition-all bg-slate-800/60">
                    <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                        <i class="fa-solid fa-check text-[10px] hidden check-icon"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-300">سبز</span>
                </div>

                <!-- 4. Red (Danger) -->
                <div onclick="selectModalColor('danger', '#ef4444')" id="colorCard-danger" class="color-card p-2.5 rounded-xl border-2 border-slate-700 hover:border-rose-500 cursor-pointer flex flex-col items-center justify-center gap-1 transition-all bg-slate-800/60">
                    <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center">
                        <i class="fa-solid fa-check text-[10px] hidden check-icon"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-300">قرمز</span>
                </div>
            </div>
        </div>

        <!-- Custom Palette & Hex Picker (PanelSaz style) -->
        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2.5">
            <div class="text-[11px] font-semibold text-slate-400">پالت رنگ‌های سفارشی:</div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="selectCustomHexColor('#8b5cf6')" title="بنفش رویال" class="w-6 h-6 rounded-full bg-[#8b5cf6] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#f97316')" title="نارنجی نئونی" class="w-6 h-6 rounded-full bg-[#f97316] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#eab308')" title="طلایی کهربایی" class="w-6 h-6 rounded-full bg-[#eab308] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#ec4899')" title="صورتی باربی" class="w-6 h-6 rounded-full bg-[#ec4899] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#06b6d4')" title="فیروزه‌ای" class="w-6 h-6 rounded-full bg-[#06b6d4] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#6366f1')" title="نیلی ایندیگو" class="w-6 h-6 rounded-full bg-[#6366f1] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#10b981')" title="سبز زمردی" class="w-6 h-6 rounded-full bg-[#10b981] border border-white/40 shadow hover:scale-125 transition-transform"></button>
                <button type="button" onclick="selectCustomHexColor('#3b82f6')" title="آبی کلاسیک" class="w-6 h-6 rounded-full bg-[#3b82f6] border border-white/40 shadow hover:scale-125 transition-transform"></button>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <div class="relative w-9 h-9 rounded-xl border border-slate-700 overflow-hidden cursor-pointer shrink-0">
                    <input type="color" id="modalCustomColorPicker" value="#2563eb" oninput="onCustomColorPickerChange(this.value)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div id="modalColorPickerIndicator" class="w-full h-full bg-[#2563eb] flex items-center justify-center text-xs text-white">
                        <i class="fa-solid fa-eye-dropper"></i>
                    </div>
                </div>
                <input type="text" id="modalCustomColorHex" placeholder="#2563eb" oninput="onCustomColorHexChange(this.value)" class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-white text-xs font-mono uppercase focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        <!-- Button Text / Title Input -->
        <div>
            <label class="block text-xs text-slate-300 font-semibold mb-1">متن و ایموجی دکمه:</label>
            <input type="text" id="modalBtnTitleInput" oninput="onModalTitleInput(this.value)" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white text-xs font-bold focus:outline-none focus:border-indigo-500">
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-800">
            <button type="button" onclick="deleteModalButton()" class="px-3.5 py-2 border border-rose-500/40 text-rose-400 hover:bg-rose-500/10 font-bold text-xs rounded-xl transition-all">
                انتقال به مخزن غیرفعال
            </button>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeBtnEditModal()" class="px-4 py-2 bg-slate-800 text-slate-300 hover:bg-slate-700 font-semibold text-xs rounded-xl">
                    انصراف
                </button>
                <button type="button" onclick="saveModalChanges()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30">
                    تایید و اعمال
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for POST layout submission -->
<form id="keyboardSubmitForm" action="/admin/buttons/save-layout" method="POST" class="hidden">
    <input type="hidden" name="layout_json" id="postLayoutJson" value="">
    <div id="hiddenInputsContainer"></div>
</form>

<script>
    const screenData = <?= json_encode($screensData['main_menu'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    let currentEditKey = null;
    let modalSelectedStyle = 'default';
    let modalSelectedColor = '#2563eb';

    // Color contrast calculation helper
    function getContrastColor(hex) {
        if (!hex) return '#ffffff';
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(c => c+c).join('');
        const r = parseInt(hex.substring(0, 2), 16) || 0;
        const g = parseInt(hex.substring(2, 4), 16) || 0;
        const b = parseInt(hex.substring(4, 6), 16) || 0;
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return (yiq >= 155) ? '#0f172a' : '#ffffff';
    }

    function renderKeyboard() {
        const container = document.getElementById('keyboardContainer');
        const unusedBox = document.getElementById('unusedButtonsBox');
        const liveMockup = document.getElementById('telegramLiveMockup');

        container.innerHTML = '';
        unusedBox.innerHTML = '';
        liveMockup.innerHTML = '';

        const usedButtonKeys = [];

        // 1. Render Active Keyboard Rows
        screenData.layout.forEach((row, rowIndex) => {
            const rowWrapper = document.createElement('div');
            rowWrapper.className = 'keyboard-row flex items-center gap-2.5 p-2 rounded-2xl bg-slate-900/60 border border-slate-800 transition-all';
            rowWrapper.dataset.rowIndex = rowIndex;

            // Split / Merge toggle button
            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'w-9 h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-base flex items-center justify-center border border-slate-700/80 shrink-0 transition-all active:scale-95';
            plusBtn.innerHTML = '<i class="fa-solid fa-plus text-xs"></i>';
            plusBtn.title = 'تغییر وضعیت: ۱ دکمه تمام‌عرض یا ۲ دکمه کنار هم';
            plusBtn.onclick = () => toggleRowSplit(rowIndex);
            rowWrapper.appendChild(plusBtn);

            // Drag handle for row
            const dragHandle = document.createElement('div');
            dragHandle.className = 'row-drag-handle text-slate-500 hover:text-slate-300 cursor-grab p-1 text-xs shrink-0';
            dragHandle.innerHTML = '<i class="fa-solid fa-grip-vertical"></i>';
            rowWrapper.appendChild(dragHandle);

            // Buttons Container in row
            const buttonsContainer = document.createElement('div');
            buttonsContainer.className = 'row-buttons-box flex-1 grid gap-2 min-h-[44px] p-1 rounded-xl bg-slate-950/40 ' + (row.length === 2 ? 'grid-cols-2' : (row.length >= 3 ? 'grid-cols-' + row.length : 'grid-cols-1'));
            buttonsContainer.dataset.rowIndex = rowIndex;

            // Live mockup row
            const liveRow = document.createElement('div');
            liveRow.className = 'grid gap-2 ' + (row.length === 2 ? 'grid-cols-2' : (row.length >= 3 ? 'grid-cols-' + row.length : 'grid-cols-1'));

            row.forEach(btnKey => {
                const btnData = screenData.buttons[btnKey];
                if (!btnData) return;
                usedButtonKeys.push(btnKey);

                const btnEl = document.createElement('div');
                btnEl.dataset.btnKey = btnKey;
                btnEl.style.backgroundColor = btnData.color || '#2563eb';
                btnEl.style.color = getContrastColor(btnData.color || '#2563eb');
                btnEl.style.borderColor = btnData.color || '#2563eb';
                btnEl.className = 'btn-chip group relative py-3 px-3 rounded-xl text-center font-bold text-xs cursor-pointer shadow-md transition-all active:scale-98 select-none border truncate';
                btnEl.innerHTML = `<span>${escapeHtml(btnData.title)}</span>`;
                btnEl.onclick = () => openBtnEditModal(btnKey);

                buttonsContainer.appendChild(btnEl);

                // Add to live mockup
                const mockBtn = document.createElement('div');
                mockBtn.style.backgroundColor = btnData.color || '#2563eb';
                mockBtn.style.color = getContrastColor(btnData.color || '#2563eb');
                mockBtn.className = 'py-2 px-2 rounded-xl text-center text-xs font-semibold shadow-sm truncate';
                mockBtn.innerText = btnData.title;
                liveRow.appendChild(mockBtn);
            });

            rowWrapper.appendChild(buttonsContainer);
            container.appendChild(rowWrapper);
            if (row.length > 0) {
                liveMockup.appendChild(liveRow);
            }
        });

        // 2. Render Unused Buttons Dock
        const allKeys = Object.keys(screenData.buttons);
        const unusedKeys = allKeys.filter(k => !usedButtonKeys.includes(k));

        if (unusedKeys.length === 0) {
            unusedBox.innerHTML = '<span class="text-xs text-slate-500 italic py-2 px-1">تمامی دکمه‌ها در کیبورد فعال و چیده شده‌اند.</span>';
        } else {
            unusedKeys.forEach(btnKey => {
                const btnData = screenData.buttons[btnKey];
                const chip = document.createElement('div');
                chip.className = 'btn-chip py-2 px-3 rounded-xl text-xs font-semibold cursor-grab shadow-sm transition-all flex items-center gap-2 border border-slate-700/60 hover:border-slate-500 text-slate-300 bg-slate-800/80 select-none';
                chip.dataset.btnKey = btnKey;
                if (btnData.color) {
                    chip.style.borderRight = `4px solid ${btnData.color}`;
                }
                chip.innerHTML = `
                    <i class="fa-solid fa-grip-vertical text-slate-500 text-[10px]"></i>
                    <span>${escapeHtml(btnData.title)}</span>
                    <button type="button" onclick="event.stopPropagation(); restoreUnusedButton('${btnKey}');" class="ml-1 w-5 h-5 rounded-lg bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white flex items-center justify-center text-[10px]" title="افزودن به کیبورد">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                `;
                chip.onclick = () => openBtnEditModal(btnKey);
                unusedBox.appendChild(chip);
            });
        }

        // Initialize Sortable on Rows
        new Sortable(container, {
            handle: '.row-drag-handle',
            animation: 200,
            onEnd: function() {
                const newLayout = [];
                container.querySelectorAll('.keyboard-row').forEach(r => {
                    const rIdx = Number(r.dataset.rowIndex);
                    if (screenData.layout[rIdx]) {
                        newLayout.push(screenData.layout[rIdx]);
                    }
                });
                screenData.layout = newLayout;
                renderKeyboard();
            }
        });

        // Initialize Sortable within rows AND unused box (group: 'buttons')
        const allSortableBoxes = [...container.querySelectorAll('.row-buttons-box'), unusedBox];
        allSortableBoxes.forEach(box => {
            new Sortable(box, {
                group: 'buttons',
                animation: 150,
                onEnd: function() {
                    syncLayoutFromDom();
                }
            });
        });
    }

    function syncLayoutFromDom() {
        const container = document.getElementById('keyboardContainer');
        const newLayout = [];
        container.querySelectorAll('.row-buttons-box').forEach(box => {
            const rowKeys = [];
            box.querySelectorAll('.btn-chip').forEach(btn => {
                if (btn.dataset.btnKey) {
                    rowKeys.push(btn.dataset.btnKey);
                }
            });
            if (rowKeys.length > 0) {
                newLayout.push(rowKeys);
            }
        });
        screenData.layout = newLayout;
        renderKeyboard();
    }

    function addNewRow() {
        screenData.layout.push([]);
        renderKeyboard();
    }

    function toggleRowSplit(rowIndex) {
        const row = screenData.layout[rowIndex];
        if (!row || row.length === 0) return;
        if (row.length === 1) {
            // Check if unused buttons available
            const used = screenData.layout.flat();
            const unused = Object.keys(screenData.buttons).filter(k => !used.includes(k));
            if (unused.length > 0) {
                row.push(unused[0]);
            }
        } else {
            // Pop second button to unused
            row.pop();
        }
        renderKeyboard();
    }

    function restoreUnusedButton(btnKey) {
        if (screenData.layout.length === 0) {
            screenData.layout.push([btnKey]);
        } else {
            const lastRow = screenData.layout[screenData.layout.length - 1];
            if (lastRow.length < 2) {
                lastRow.push(btnKey);
            } else {
                screenData.layout.push([btnKey]);
            }
        }
        renderKeyboard();
    }

    // Modal logic
    function openBtnEditModal(btnKey) {
        currentEditKey = btnKey;
        const btnData = screenData.buttons[btnKey];
        if (!btnData) return;

        document.getElementById('modalBtnTitleInput').value = btnData.title;
        modalSelectedColor = btnData.color || '#2563eb';
        modalSelectedStyle = btnData.style || 'default';

        selectModalColor(modalSelectedStyle, modalSelectedColor);
        document.getElementById('btnEditModal').classList.remove('hidden');
    }

    function closeBtnEditModal() {
        document.getElementById('btnEditModal').classList.add('hidden');
        currentEditKey = null;
    }

    function selectModalColor(style, color) {
        modalSelectedStyle = style;
        modalSelectedColor = color;

        document.querySelectorAll('.color-card').forEach(card => {
            card.classList.remove('border-indigo-500', 'bg-indigo-950/40');
            const check = card.querySelector('.check-icon');
            if (check) check.classList.add('hidden');
        });

        const activeCard = document.getElementById(`colorCard-${style}`);
        if (activeCard) {
            activeCard.classList.add('border-indigo-500', 'bg-indigo-950/40');
            const check = activeCard.querySelector('.check-icon');
            if (check) check.classList.remove('hidden');
        }

        document.getElementById('modalCustomColorHex').value = color;
        document.getElementById('modalCustomColorPicker').value = color;
        document.getElementById('modalColorPickerIndicator').style.backgroundColor = color;

        updateModalPreview();
    }

    function selectCustomHexColor(hex) {
        modalSelectedColor = hex;
        document.getElementById('modalCustomColorHex').value = hex;
        document.getElementById('modalCustomColorPicker').value = hex;
        document.getElementById('modalColorPickerIndicator').style.backgroundColor = hex;
        updateModalPreview();
    }

    function onCustomColorPickerChange(val) {
        selectCustomHexColor(val);
    }

    function onCustomColorHexChange(val) {
        val = val.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(val) || /^#[0-9a-fA-F]{3}$/.test(val)) {
            selectCustomHexColor(val);
        }
    }

    function onModalTitleInput(val) {
        updateModalPreview();
    }

    function updateModalPreview() {
        const preview = document.getElementById('modalPreviewBtn');
        const title = document.getElementById('modalBtnTitleInput').value || 'عنوان دکمه';
        preview.innerText = title;
        preview.style.backgroundColor = modalSelectedColor;
        preview.style.color = getContrastColor(modalSelectedColor);
        preview.style.borderColor = modalSelectedColor;
    }

    function deleteModalButton() {
        if (!currentEditKey) return;
        screenData.layout = screenData.layout.map(row => row.filter(k => k !== currentEditKey)).filter(row => row.length > 0);
        closeBtnEditModal();
        renderKeyboard();
    }

    function saveModalChanges() {
        if (!currentEditKey) return;
        const newTitle = document.getElementById('modalBtnTitleInput').value.trim();
        if (newTitle) {
            screenData.buttons[currentEditKey].title = newTitle;
        }
        screenData.buttons[currentEditKey].color = modalSelectedColor;
        screenData.buttons[currentEditKey].style = modalSelectedStyle;

        closeBtnEditModal();
        renderKeyboard();
    }

    function saveKeyboardLayout() {
        const form = document.getElementById('keyboardSubmitForm');
        document.getElementById('postLayoutJson').value = JSON.stringify(screenData.layout);

        const hiddenContainer = document.getElementById('hiddenInputsContainer');
        hiddenContainer.innerHTML = '';

        Object.keys(screenData.buttons).forEach(k => {
            const b = screenData.buttons[k];
            hiddenContainer.innerHTML += `
                <input type="hidden" name="btn_title[${k}]" value="${escapeHtml(b.title)}">
                <input type="hidden" name="btn_color[${k}]" value="${b.color || ''}">
            `;
        });

        form.submit();
    }

    function escapeHtml(text) {
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderKeyboard();
    });
</script>

<?php 
$pageContent = ob_get_clean(); 
include __DIR__ . '/layout.php'; 
?>
