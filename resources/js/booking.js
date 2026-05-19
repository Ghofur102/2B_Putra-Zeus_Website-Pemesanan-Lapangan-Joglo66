const appData = document.getElementById('booking-app');

if (appData) {
    const fieldId = appData.dataset.fieldId;
    const fetchUrl = appData.dataset.fetchUrl;

    let currentDate = new Date();
    let selectedDate = null;
    let allSlots = [];

    let selectedSlots = [];

    const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

    function getLocalDateFormat(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        document.getElementById('calendarMonth').textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        const calendarDays = document.getElementById('calendarDays');

        calendarDays.innerHTML = '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let i = firstDay - 1; i >= 0; i--) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'w-8 h-8 flex items-center justify-center mx-auto text-xs text-gray-300 font-medium text-transparent';
            emptyDiv.textContent = daysInPrevMonth - i;
            calendarDays.appendChild(emptyDiv);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const fullDate = new Date(year, month, day);
            fullDate.setHours(0, 0, 0, 0);
            const dateStr = getLocalDateFormat(fullDate);

            let className = 'w-8 h-8 flex items-center justify-center mx-auto text-xs font-medium rounded-full cursor-pointer transition-colors ';

            if (fullDate < today) {
                className += 'text-gray-300 cursor-not-allowed';
            } else {
                className += 'text-gray-700 hover:bg-blue-50';
                if (fullDate.getTime() === today.getTime()) {
                    className = 'w-8 h-8 flex items-center justify-center mx-auto text-xs text-primary font-bold border border-primary rounded-full hover:bg-blue-50 cursor-pointer transition-colors';
                }
                if (selectedDate && fullDate.getTime() === new Date(selectedDate).setHours(0, 0, 0, 0)) {
                    className = 'w-8 h-8 flex items-center justify-center mx-auto text-xs bg-primary text-white rounded-full font-bold shadow-sm cursor-pointer transition-colors';
                }

                const hasSlot = selectedSlots.some(s => s.date === dateStr);
                if (hasSlot && (!selectedDate || fullDate.getTime() !== new Date(selectedDate).setHours(0, 0, 0, 0))) {
                    className = 'w-8 h-8 flex items-center justify-center mx-auto text-xs bg-blue-50 text-primary ring-1 ring-primary rounded-full font-bold cursor-pointer transition-colors';
                }
            }

            const dayDiv = document.createElement('div');
            dayDiv.className = className;
            dayDiv.textContent = day;

            if (fullDate >= today) {
                dayDiv.onclick = () => selectDate(fullDate);
            }

            calendarDays.appendChild(dayDiv);
        }

        const remaining = 42 - calendarDays.children.length;
        for (let i = 1; i <= remaining; i++) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'w-8 h-8 flex items-center justify-center mx-auto text-xs text-gray-300 font-medium text-transparent';
            emptyDiv.textContent = i;
            calendarDays.appendChild(emptyDiv);
        }
    }

    function selectDate(date) {
        selectedDate = date;
        renderCalendar();

        const dateStr = getLocalDateFormat(date);
        const slotGrid = document.getElementById('slotGrid');
        slotGrid.innerHTML = '<div class="col-span-2 text-center py-8 text-gray-500 bg-gray-50 rounded-lg animate-pulse border border-dashed border-gray-300">Memuat slot...</div>';

        fetch(`${fetchUrl}?field_id=${fieldId}&date=${dateStr}`)
            .then(res => res.json())
            .then(data => {
                allSlots = data.slots || [];
                renderSlotJam();
            });
    }

    function renderSlotJam() {
        const slotGrid = document.getElementById('slotGrid');
        slotGrid.innerHTML = '';

        if (allSlots.length === 0) return;

        const dateStr = getLocalDateFormat(selectedDate);
        const now = new Date();
        const isToday = selectedDate.toDateString() === now.toDateString();

        allSlots.forEach((slot) => {
            let finalStatus = slot.status;

            if (isToday && finalStatus === 'kosong') {
                const [slotHour, slotMinute] = slot.jam.split(':').map(Number);
                if (slotHour < now.getHours() || (slotHour === now.getHours() && slotMinute <= now.getMinutes())) {
                    finalStatus = 'lewat';
                }
            }

            const isSelected = selectedSlots.some(s => s.date === dateStr && s.jam === slot.jam);

            // Membuat Elemen Tombol mirip halaman Reschedule
            const btn = document.createElement('button');
            btn.type = 'button';

            const isDisabled = finalStatus !== 'kosong';
            if (isDisabled) btn.disabled = true;

            let btnClass = 'slot-btn px-3 py-3 rounded-xl border text-sm font-medium transition text-center flex flex-col justify-center ';
            let priceClass = 'block text-xs mt-0.5 price-label ';

            if (finalStatus === 'kosong') {
                if (isSelected) {
                    btnClass += 'bg-primary text-white border-primary shadow-sm';
                    priceClass += 'text-blue-100';
                } else {
                    btnClass += 'bg-white border-gray-200 text-gray-700 hover:bg-blue-50 hover:border-primary';
                    priceClass += 'text-gray-500';
                }
            } else {
                btnClass += 'bg-gray-100 border-gray-200 text-gray-300 cursor-not-allowed';
                priceClass += 'text-gray-400';
            }

            btn.className = btnClass;

            let statusHtml = '';
            if (finalStatus === 'terisi') statusHtml = '<span class="text-red-500 font-bold ml-1 block mt-1">&middot; Terisi</span>';
            else if (finalStatus === 'tutup') statusHtml = '<span class="text-red-500 font-bold ml-1 block mt-1">&middot; Tutup</span>';
            else if (finalStatus === 'lewat') statusHtml = '<span class="text-gray-400 font-bold ml-1 block mt-1">&middot; Lewat</span>';

            btn.innerHTML = `
                ${slot.jam.substring(0,5)} - ${slot.jam_akhir.substring(0,5)}
                <span class="${priceClass}">
                    Rp ${new Intl.NumberFormat('id-ID').format(slot.harga)}
                    ${statusHtml}
                </span>
            `;

            if (finalStatus === 'kosong') {
                btn.onclick = () => {
                    const existingIdx = selectedSlots.findIndex(s => s.date === dateStr && s.jam === slot.jam);
                    if (existingIdx > -1) {
                        selectedSlots.splice(existingIdx, 1);
                    } else {
                        selectedSlots.push({ date: dateStr, jam: slot.jam, jam_akhir: slot.jam_akhir, harga: slot.harga });
                    }
                    renderSlotJam();
                    renderCalendar();
                    updateSelectedInfo();
                };
            }

            slotGrid.appendChild(btn);
        });
    }

    function updateSelectedInfo() {
        const infoEl = document.getElementById('selectedInfo');
        const submitBtn = document.getElementById('submitBtn');

        if (selectedSlots.length > 0) {
            const uniqueDates = new Set(selectedSlots.map(s => s.date)).size;
            infoEl.textContent = `${selectedSlots.length} Slot di ${uniqueDates} Hari Berbeda`;
            submitBtn.disabled = false;

            document.getElementById('formSelectedSlots').value = JSON.stringify(selectedSlots);
        } else {
            infoEl.textContent = 'Belum dipilih';
            submitBtn.disabled = true;
            document.getElementById('formSelectedSlots').value = '[]';
        }

        let total = 0;
        selectedSlots.forEach(s => total += s.harga);
        document.getElementById('selectedCount').textContent = selectedSlots.length;
        document.getElementById('totalPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }

    document.getElementById('btnPrevMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); };
    document.getElementById('btnNextMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); };

    const initialToday = new Date();
    initialToday.setHours(0, 0, 0, 0);
    renderCalendar();
    selectDate(initialToday);
}
