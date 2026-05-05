<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Lapangan - Booking Lapangan Olahraga</title>
    <style>
        :root {
            --color-primary: #3a5a8c;
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --color-warning: #eab308;
            --color-info: #3b82f6;
            --color-bg: #ffffff;
            --color-text: #1f2937;
            --color-border: #e5e7eb;
            --color-card-bg: #f9fafb;
            --color-light-gray: #f3f4f6;
            --color-gray: #d1d5db;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            color: var(--color-text);
            background-color: var(--color-card-bg);
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background-color: var(--color-primary);
            height: 56px;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-item {
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            transition: opacity 0.3s ease;
        }

        .navbar-item:hover {
            opacity: 0.8;
        }

        .navbar-center {
            color: white;
            font-size: 1rem;
            font-weight: bold;
        }

        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--color-text);
        }

        /* ===== KOLOM KIRI: SLOT JAM ===== */
        .slot-jam-container {
            background-color: var(--color-bg);
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 0.5px solid var(--color-border);
        }

        .slot-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .slot-pill {
            padding: 0.75rem;
            border: 0.5px solid var(--color-border);
            border-radius: 999px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            background-color: var(--color-light-gray);
            color: #6b7280;
        }

        .slot-pill:hover:not(.disabled):not(.terisi) {
            background-color: var(--color-border);
        }

        .slot-pill.kosong {
            background-color: var(--color-light-gray);
            color: #6b7280;
            cursor: pointer;
        }

        .slot-pill.terisi {
            background-color: var(--color-primary);
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .slot-pill.tutup {
            background-color: var(--color-gray);
            color: white;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .slot-pill.selected {
            background-color: var(--color-info);
            color: white;
            border: 2px solid var(--color-primary);
        }

        .slot-pill.disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .empty-message {
            text-align: center;
            color: #9ca3af;
            padding: 2rem;
            background-color: var(--color-card-bg);
            border-radius: 0.375rem;
        }

        /* ===== KOLOM KANAN: KALENDER ===== */
        .calendar-container {
            background-color: var(--color-bg);
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 0.5px solid var(--color-border);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-nav {
            display: flex;
            gap: 0.5rem;
        }

        .calendar-nav button {
            background-color: var(--color-primary);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 1rem;
        }

        .calendar-nav button:hover {
            background-color: #2c4670;
        }

        .calendar-month {
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            flex: 1;
            color: var(--color-text);
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .weekday-cell {
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: #6b7280;
            padding: 0.5rem 0;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0.5px solid var(--color-border);
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            background-color: var(--color-bg);
            color: var(--color-text);
        }

        .calendar-day:hover:not(.disabled):not(.empty) {
            background-color: var(--color-card-bg);
            border-color: var(--color-primary);
        }

        .calendar-day.empty {
            color: transparent;
            cursor: default;
            border-color: transparent;
        }

        .calendar-day.today {
            background-color: var(--color-primary);
            color: white;
            font-weight: 600;
            border-color: var(--color-primary);
        }

        .calendar-day.selected {
            background-color: var(--color-primary);
            color: white;
            font-weight: 600;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(58, 90, 140, 0.2);
        }

        .calendar-day.past {
            background-color: var(--color-light-gray);
            color: #9ca3af;
            cursor: not-allowed;
        }

        /* ===== BUTTON SECTION ===== */
        .button-section {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #2c4670;
        }

        .btn-primary:disabled {
            background-color: var(--color-gray);
            cursor: not-allowed;
            opacity: 0.5;
        }

        .btn-secondary {
            background-color: var(--color-border);
            color: var(--color-text);
        }

        .btn-secondary:hover {
            background-color: var(--color-gray);
        }

        /* ===== INFO SECTION ===== */
        .info-section {
            background-color: var(--color-card-bg);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 2rem;
            border-left: 4px solid var(--color-primary);
        }

        .info-section p {
            font-size: 0.9rem;
            color: var(--color-text);
            margin: 0.25rem 0;
        }

        .info-label {
            font-weight: 600;
            color: var(--color-primary);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .slot-grid {
                grid-template-columns: 1fr;
            }

            .button-section {
                flex-direction: column;
            }

            .navbar {
                flex-direction: column;
                height: auto;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
            }

            .navbar-item {
                font-size: 0.85rem;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="{{ route('tenant.booking.dashboard') }}" class="navbar-item">← Booking Lapangan</a>
        <div class="navbar-center">Jadwal Lapangan</div>
        <a href="#" class="navbar-item">Lihat Riwayat Transaksi</a>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- INFO LAPANGAN -->
        <div class="info-section">
            <p><span class="info-label">Lapangan Dipilih:</span> {{ $field->name }}</p>
            <p><span class="info-label">Tanggal & Slot:</span> <span id="selectedInfo">Belum dipilih</span></p>
        </div>

        <!-- 2 KOLOM: SLOT JAM & KALENDER -->
        <div class="main-content">
            <!-- KOLOM KIRI: SLOT JAM TERSEDIA -->
            <div class="slot-jam-container">
                <div class="section-title">Slot Jam Tersedia :</div>
                <div class="slot-grid" id="slotGrid">
                    <div class="empty-message">Pilih tanggal untuk melihat slot</div>
                </div>
            </div>

            <!-- KOLOM KANAN: KALENDER INTERAKTIF -->
            <div class="calendar-container">
                <div class="section-title">Slot Tanggal :</div>
                
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <button onclick="previousMonth()">&lt;</button>
                    </div>
                    <div class="calendar-month" id="calendarMonth">Mei 2026</div>
                    <div class="calendar-nav">
                        <button onclick="nextMonth()">&gt;</button>
                    </div>
                </div>

                <div class="calendar-weekdays">
                    <div class="weekday-cell">Min</div>
                    <div class="weekday-cell">Sen</div>
                    <div class="weekday-cell">Sel</div>
                    <div class="weekday-cell">Rab</div>
                    <div class="weekday-cell">Kam</div>
                    <div class="weekday-cell">Jum</div>
                    <div class="weekday-cell">Sab</div>
                </div>

                <div class="calendar-days" id="calendarDays"></div>
            </div>
        </div>

        <!-- BUTTON SECTION -->
        <div class="button-section">
            <a href="{{ route('tenant.booking.dashboard') }}" class="btn btn-secondary">Kembali</a>
            <button type="button" onclick="proceedToSlotSelection()" class="btn btn-primary" id="proceedBtn" disabled>
                Lanjut ke Form Pemesanan
            </button>
        </div>

        <!-- BOOKING FORM SECTION (hidden until slots selected) -->
        <div id="bookingForm" style="display: none; margin-top: 3rem;">
            <div style="background-color: var(--color-bg); padding: 2rem; border-radius: 0.5rem; border: 0.5px solid var(--color-border);">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--color-text);">Data Pemesanan</h2>
                
                <form action="{{ route('tenant.booking.confirm-form') }}" method="POST">
                    @csrf
                    
                    <!-- Hidden inputs -->
                    <input type="hidden" id="formFieldId" name="field_id">
                    <input type="hidden" id="formBookingDate" name="booking_date">
                    <input type="hidden" id="formSelectedSlots" name="selected_slots">
                    
                    <!-- Team Name -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-text);">Nama Tim <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="team_name" required maxlength="50" placeholder="Masukkan nama tim" 
                               style="width: 100%; padding: 0.75rem; border: 0.5px solid var(--color-border); border-radius: 0.375rem; font-size: 0.95rem;">
                    </div>
                    
                    <!-- Phone -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-text);">Nomor Telepon <span style="color: #ef4444;">*</span></label>
                        <input type="tel" name="customer_phone" required placeholder="Contoh: 081234567890" 
                               style="width: 100%; padding: 0.75rem; border: 0.5px solid var(--color-border); border-radius: 0.375rem; font-size: 0.95rem;">
                    </div>
                    
                    <!-- Email -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-text);">Email <span style="color: #ef4444;">*</span></label>
                        <input type="email" name="customer_email" required placeholder="Contoh: nama@email.com" 
                               style="width: 100%; padding: 0.75rem; border: 0.5px solid var(--color-border); border-radius: 0.375rem; font-size: 0.95rem;">
                    </div>
                    
                    <!-- Notes -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-text);">Catatan (Opsional)</label>
                        <textarea name="notes" maxlength="500" placeholder="Tambahkan catatan khusus jika ada" 
                                  style="width: 100%; padding: 0.75rem; border: 0.5px solid var(--color-border); border-radius: 0.375rem; font-size: 0.95rem; min-height: 100px; font-family: inherit;"></textarea>
                    </div>
                    
                    <!-- Buttons -->
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="document.getElementById('bookingForm').style.display = 'none'" 
                                class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                            Ubah Pilihan Slot
                        </button>
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                            Lanjut ke Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const fieldId = {{ $field->id }};
        let currentDate = new Date();
        let selectedDate = null;
        let selectedSlots = [];
        let allSlots = [];

        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

        // Initialize calendar
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month display
            document.getElementById('calendarMonth').textContent = `${monthNames[month]} ${year}`;

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day empty';
                dayDiv.textContent = daysInPrevMonth - i;
                calendarDays.appendChild(dayDiv);
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayDiv = document.createElement('div');
                const fullDate = new Date(year, month, day);
                fullDate.setHours(0, 0, 0, 0);

                let className = 'calendar-day';

                // Check if past date
                if (fullDate < today) {
                    className += ' past';
                    dayDiv.onclick = null;
                } else {
                    dayDiv.onclick = () => selectDate(fullDate);
                    
                    // Check if today
                    if (fullDate.getTime() === today.getTime()) {
                        className += ' today';
                    }

                    // Check if selected
                    if (selectedDate && fullDate.getTime() === new Date(selectedDate).setHours(0, 0, 0, 0)) {
                        className += ' selected';
                    }
                }

                dayDiv.className = className;
                dayDiv.textContent = day;
                calendarDays.appendChild(dayDiv);
            }

            // Next month days
            const totalCells = calendarDays.children.length;
            const remainingCells = 42 - totalCells; // 6 rows × 7 days
            for (let i = 1; i <= remainingCells; i++) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day empty';
                dayDiv.textContent = i;
                calendarDays.appendChild(dayDiv);
            }
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        function selectDate(date) {
            selectedDate = date;
            selectedSlots = [];
            renderCalendar();
            fetchSlotJam(date);
            updateSelectedInfo();
        }

        function fetchSlotJam(date) {
            const dateStr = date.toISOString().split('T')[0];
            const slotGrid = document.getElementById('slotGrid');
            
            slotGrid.innerHTML = '<div class="empty-message">Loading...</div>';

            const url = `{{ route('tenant.booking.fetch-slots') }}?field_id=${fieldId}&date=${dateStr}`;
            console.log('Fetching slots from:', url);
            console.log('Field ID:', fieldId);
            console.log('Date:', dateStr);

            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Slot data received:', data);
                    allSlots = data.slots;
                    renderSlotJam();
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    slotGrid.innerHTML = '<div class="empty-message">Gagal memuat slot: ' + error.message + '</div>';
                });
        }

        function renderSlotJam() {
            const slotGrid = document.getElementById('slotGrid');
            
            if (allSlots.length === 0) {
                slotGrid.innerHTML = '<div class="empty-message">Tidak ada slot tersedia</div>';
                return;
            }

            slotGrid.innerHTML = '';

            allSlots.forEach((slot, index) => {
                const pill = document.createElement('div');
                pill.className = `slot-pill ${slot.status}`;
                pill.textContent = slot.jam;

                if (slot.status === 'kosong') {
                    pill.onclick = () => toggleSlotSelection(index);
                    if (selectedSlots.includes(index)) {
                        pill.classList.add('selected');
                    }
                }

                slotGrid.appendChild(pill);
            });
        }

        function toggleSlotSelection(index) {
            const slot = allSlots[index];
            
            if (slot.status !== 'kosong') return;

            if (selectedSlots.includes(index)) {
                selectedSlots = selectedSlots.filter(i => i !== index);
            } else {
                selectedSlots.push(index);
            }

            renderSlotJam();
            updateSelectedInfo();
        }

        function updateSelectedInfo() {
            const infoEl = document.getElementById('selectedInfo');
            if (selectedDate && selectedSlots.length > 0) {
                const date = new Date(selectedDate);
                const dateStr = date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                const slotTimes = selectedSlots.map(idx => allSlots[idx].jam).join(', ');
                infoEl.textContent = `${dateStr} | Slot: ${slotTimes}`;
                document.getElementById('proceedBtn').disabled = false;
            } else {
                infoEl.textContent = 'Belum dipilih';
                document.getElementById('proceedBtn').disabled = true;
            }
        }

        function proceedToSlotSelection() {
            if (!selectedDate || selectedSlots.length === 0) {
                alert('Silakan pilih tanggal dan slot');
                return;
            }

            // Populate hidden form fields
            const dateStr = selectedDate.toISOString().split('T')[0];
            const selectedSlotsData = selectedSlots.map(idx => allSlots[idx]).map(s => s.jam + '|' + s.jam_akhir);
            
            document.getElementById('formFieldId').value = fieldId;
            document.getElementById('formBookingDate').value = dateStr;
            document.getElementById('formSelectedSlots').value = JSON.stringify(selectedSlotsData);
            
            // Scroll to form and show it
            document.getElementById('bookingForm').style.display = 'block';
            document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            renderCalendar();
            
            // Select today by default if it's available
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (today.getMonth() === new Date().getMonth() && today.getFullYear() === new Date().getFullYear()) {
                selectDate(today);
            }
        });
    </script>
</body>
</html>
