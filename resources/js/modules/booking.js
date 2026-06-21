export function initializeBookingApp() {
    const appData = document.getElementById('booking-app');
    if (!appData) {
        return;
    }

    const fieldId = appData.dataset.fieldId;
    const fetchUrl = appData.dataset.fetchUrl;

    let currentDate = new Date();
    let selectedDate = null;
    let allSlots = [];
    let selectedSlots = [];

    const monthNames = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

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
            emptyDiv.className = 'calendar-day empty text-transparent';
            emptyDiv.textContent = daysInPrevMonth - i;
            calendarDays.appendChild(emptyDiv);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const fullDate = new Date(year, month, day);
            fullDate.setHours(0, 0, 0, 0);
            const dateStr = getLocalDateFormat(fullDate);

            const dayDiv = document.createElement('div');
            dayDiv.classList.add('calendar-day');
            dayDiv.textContent = day;

            if (fullDate < today) {
                dayDiv.classList.add('past');
            } else {
                if (fullDate.getTime() === today.getTime()) {
                    dayDiv.classList.add('today');
                }
                if (selectedDate && fullDate.getTime() === new Date(selectedDate).setHours(0, 0, 0, 0)) {
                    dayDiv.classList.add('selected');
                }

                const hasSlot = selectedSlots.some(s => s.date === dateStr);
                const isNotCurrentlySelected = !selectedDate || fullDate.getTime() !== new Date(selectedDate).setHours(0, 0, 0, 0);

                if (hasSlot && isNotCurrentlySelected) {
                    dayDiv.className = 'calendar-day bg-primary-light text-primary ring-1 ring-primary font-bold';
                }

                dayDiv.onclick = () => selectDate(fullDate);
            }

            calendarDays.appendChild(dayDiv);
        }

        const remaining = 42 - calendarDays.children.length;
        for (let i = 1; i <= remaining; i++) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'calendar-day empty text-transparent';
            emptyDiv.textContent = i;
            calendarDays.appendChild(emptyDiv);
        }
    }

    function selectDate(date) {
        selectedDate = date;
        renderCalendar();

        const dateStr = getLocalDateFormat(date);
        const slotGrid = document.getElementById('slotGrid');
        slotGrid.innerHTML = '<div class="col-span-2 text-center py-8 text-gray-500 bg-gray-50 rounded-tenant-md animate-pulse border border-dashed border-gray-300 text-sm">Memuat slot...</div>';

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

        if (allSlots.length === 0) {
            return;
        }

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

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'slot-pill flex flex-col justify-center items-center gap-1';

            if (finalStatus === 'kosong') {
                btn.classList.add(isSelected ? 'selected' : 'kosong');
            } else {
                btn.classList.add(finalStatus);
                btn.disabled = true;
            }

            let statusLabel = '';
            if (finalStatus === 'terisi' || finalStatus === 'tutup') {
                statusLabel = `<span class="text-red-500 font-bold block">&middot; ${finalStatus.toUpperCase()}</span>`;
            } else if (finalStatus === 'lewat') {
                statusLabel = '<span class="text-gray-400 font-bold block">&middot; LEWAT</span>';
            }

            const priceColor = isSelected ? 'text-blue-100' : (finalStatus === 'kosong' ? 'text-gray-500' : 'text-gray-400');

            btn.innerHTML = `
                <span class="block">${slot.jam.substring(0, 5)} - ${slot.jam_akhir.substring(0, 5)}</span>
                <span class="block text-xs font-medium ${priceColor}">
                    Rp ${new Intl.NumberFormat('id-ID').format(slot.harga)}
                    ${statusLabel}
                </span>
            `;

            if (finalStatus === 'kosong') {
                btn.onclick = () => {
                    const existingIdx = selectedSlots.findIndex(s => s.date === dateStr && s.jam === slot.jam);
                    if (existingIdx > -1) {
                        selectedSlots.splice(existingIdx, 1);
                    } else {
                        selectedSlots.push({
                            date: dateStr,
                            jam: slot.jam,
                            jam_akhir: slot.jam_akhir,
                            harga: slot.harga
                        });
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
        const formSelectedSlots = document.getElementById('formSelectedSlots');

        if (selectedSlots.length > 0) {
            const uniqueDates = new Set(selectedSlots.map(s => s.date)).size;
            infoEl.textContent = `${selectedSlots.length} Slot di ${uniqueDates} Hari Berbeda`;
            submitBtn.removeAttribute('disabled');
            formSelectedSlots.value = JSON.stringify(selectedSlots);
        } else {
            infoEl.textContent = 'Belum dipilih';
            submitBtn.setAttribute('disabled', 'true');
            formSelectedSlots.value = '[]';
        }

        const total = selectedSlots.reduce((sum, s) => sum + s.harga, 0);
        document.getElementById('selectedCount').textContent = selectedSlots.length;
        document.getElementById('totalPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }

    document.getElementById('btnPrevMonth').onclick = () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    };

    document.getElementById('btnNextMonth').onclick = () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    };

    const initialToday = new Date();
    initialToday.setHours(0, 0, 0, 0);
    renderCalendar();
    selectDate(initialToday);
}
