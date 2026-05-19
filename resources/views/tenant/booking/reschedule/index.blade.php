@extends('tenant.layouts.app')

@section('title', 'Ubah Jadwal Pemesanan')

@section('content')
<div class="max-w-6xl mx-auto pb-10">

    <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl mb-6 shadow-sm border-l-4 border-l-amber-500">
        <div class="flex flex-col gap-1">
            <p class="text-amber-800 text-sm font-bold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                PERINGATAN: ATURAN RESCHEDULE
            </p>
            <ul class="list-disc list-inside text-sm text-amber-700 space-y-1 ml-1 mt-1">
                <li>Reschedule hanya bisa dilakukan <strong>minimal H-3</strong> sebelum jadwal bermain.</li>
                <li>Reschedule hanya <strong>1 kali</strong> per sesi booking.</li>
                <li>Slot lama akan otomatis dibuka setelah reschedule berhasil.</li>
            </ul>
        </div>
    </div>

    <div class="bg-white border border-gray-200 p-4 rounded-xl mb-8 shadow-sm border-l-4 border-l-primary">
        <div class="flex flex-col gap-1">
            <p class="text-gray-700 text-sm"><span class="font-bold text-primary">Lapangan Dipilih:</span> {{ $detail->booking->field->name }}</p>
            <p class="text-gray-700 text-sm"><span class="font-bold text-primary">Jadwal Lama:</span> <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($detail->play_date)->format('d F Y') }} ({{ substr($detail->start_play_time, 0, 5) }} - {{ substr($detail->end_play_time, 0, 5) }})</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">

        <div id="slot-section" data-view-date="{{ $selectedDate }}" data-formatted-date="{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm transition-opacity duration-300 flex flex-col">
            <h3 class="text-lg font-bold text-gray-800 mb-1">Slot Jam Tersedia</h3>
            <p class="text-sm text-gray-500 mb-5 border-b border-gray-100 pb-4">
                Tanggal: <span class="font-bold text-primary">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</span>
            </p>

            @if (count($slots) === 0)
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2 text-center py-10 text-gray-400 text-sm bg-gray-50 rounded-lg border border-dashed border-gray-300">
                        Tidak ada sesi tersedia untuk tanggal ini.
                    </div>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3" id="slotGrid">
                    @foreach ($slots as $slot)
                        @php
                            $canSelect = $slot['is_available'];
                            $isPastSlot = \Carbon\Carbon::parse($selectedDate . ' ' . $slot['start'])->isPast();
                            $isOriginal = $slot['is_original'] ?? false;
                            $isClosed = $slot['is_closed'] ?? false;

                            $disabled = !$canSelect || $isPastSlot || $isOriginal;
                        @endphp

                        <button type="button"
                                data-start="{{ $slot['start'] }}"
                                data-end="{{ $slot['end'] }}"
                                data-price="{{ $slot['price'] }}"
                                @if ($disabled) disabled @endif
                                class="slot-btn px-4 py-3 rounded-xl border text-sm font-medium transition-all text-center flex flex-col justify-center
                                       @if ($isOriginal) bg-amber-50 border-amber-300 text-amber-700 cursor-not-allowed
                                       @elseif ($disabled) bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed
                                       @else bg-white border-gray-200 text-gray-700 hover:bg-blue-50 hover:border-primary @endif">

                            <span class="text-base">{{ substr($slot['start'], 0, 5) }} - {{ substr($slot['end'], 0, 5) }}</span>
                            <span class="block text-xs mt-1 price-label @if($isOriginal) text-amber-600 @elseif($disabled) text-gray-400 @else text-gray-500 @endif">
                                Rp {{ number_format($slot['price'], 0, ',', '.') }}

                                @if ($isOriginal)
                                    <span class="font-bold ml-1 block mt-1">&middot; Jadwal Saat Ini</span>
                                @elseif ($isClosed)
                                    <span class="text-red-500 font-bold ml-1 block mt-1">&middot; Tutup</span>
                                @elseif (!$canSelect && !$isPastSlot)
                                    <span class="text-red-500 font-bold ml-1 block mt-1">&middot; Terisi</span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div id="calendar-section" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm h-fit transition-opacity duration-300">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Pilih Tanggal Baru</h3>

            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('tenant.booking.form.reschedule', ['detail_booking_id' => $detail->id, 'month' => $prevMonth->month, 'year' => $prevMonth->year, 'date' => $selectedDate]) }}"
                   class="ajax-link w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition font-bold">
                   &lt;
                </a>
                <div class="text-base font-bold text-gray-800">
                    {{ Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                </div>
                <a href="{{ route('tenant.booking.form.reschedule', ['detail_booking_id' => $detail->id, 'month' => $nextMonth->month, 'year' => $nextMonth->year, 'date' => $selectedDate]) }}"
                   class="ajax-link w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition font-bold">
                   &gt;
                </a>
            </div>

            <div class="grid grid-cols-7 gap-1 mb-2 text-center text-xs font-bold text-gray-400">
                <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center" id="calendarDays">
                @foreach ($calendar as $cell)
                    @php
                        $isSelected = $cell['date'] === $selectedDate;
                        $linkUrl = route('tenant.booking.form.reschedule', [
                            'detail_booking_id' => $detail->id,
                            'month' => $month,
                            'year' => $year,
                            'date' => $cell['date'],
                        ]);
                    @endphp

                    @if (!$cell['isCurrentMonth'] || $cell['isPast'])
                        <div class="w-9 h-9 flex items-center justify-center mx-auto text-xs text-gray-300 font-medium">
                            {{ $cell['day'] }}
                        </div>
                    @elseif ($isSelected)
                        <a href="{{ $linkUrl }}" class="ajax-link w-9 h-9 flex items-center justify-center mx-auto text-xs bg-primary text-white rounded-full font-bold shadow-md shadow-primary/30 transition-colors">
                            {{ $cell['day'] }}
                        </a>
                    @elseif ($cell['isToday'])
                        <a href="{{ $linkUrl }}" class="ajax-link w-9 h-9 flex items-center justify-center mx-auto text-xs text-primary font-bold border border-primary rounded-full hover:bg-blue-50 transition-colors">
                            {{ $cell['day'] }}
                        </a>
                    @else
                        <a href="{{ $linkUrl }}" class="ajax-link w-9 h-9 flex items-center justify-center mx-auto text-xs text-gray-700 font-medium hover:bg-blue-50 rounded-full transition-colors">
                            {{ $cell['day'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="bg-blue-50 border border-blue-100 p-5 rounded-lg mb-6 border-l-4 border-l-blue-400">
            <h4 class="font-bold text-gray-800 mb-4">Ringkasan Pilihan Anda</h4>

            <div class="flex flex-col md:flex-row gap-6 md:gap-16">
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Slot Dipilih</div>
                    <div class="text-lg font-bold text-primary" id="selectedCount">0</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Total Harga Baru</div>
                    <div class="text-lg font-bold text-primary" id="totalPrice">Rp 0</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Jadwal Pengganti</div>
                    <div class="text-sm font-bold text-primary" id="summaryDate">
                        Belum dipilih
                    </div>
                    <div class="text-sm font-bold text-primary" id="summaryTime">
                        -
                    </div>
                </div>
            </div>
        </div>

        <form id="rescheduleForm" method="POST" action="{{ route('tenant.booking.confirmation.reschedule') }}">
            @csrf
            <input type="hidden" name="detail_booking_id" value="{{ $detail->id }}">
            <input type="hidden" name="new_play_date" id="inputPlayDate">
            <input type="hidden" name="new_start_play_time" id="inputStartTime">
            <input type="hidden" name="new_end_play_time" id="inputEndTime">
            <input type="hidden" name="reason" id="inputReason">

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('tenant.booking.history.show', $detail->fk_booking_id) }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-full transition-colors">
                    Kembali
                </a>
                <button type="button" id="btnConfirm" class="px-8 py-3 bg-primary hover:bg-blue-800 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-bold rounded-full transition-colors shadow-sm shadow-primary/30" disabled>
                    Lanjut Konfirmasi
                </button>
            </div>
        </form>
    </div>

</div>

<div id="reasonModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl p-7 max-w-md w-full mx-4 shadow-2xl border border-gray-100">
        <h3 class="text-xl font-bold text-gray-900 mb-2">Alasan Perubahan</h3>
        <p class="text-sm text-gray-500 mb-5">Tuliskan alasan mengapa Anda ingin mengubah jadwal ini:</p>

        <textarea id="modalReason" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all resize-none mb-6" placeholder="Contoh: Ada urusan mendadak..."></textarea>

        <div class="flex gap-3 justify-end">
            <button type="button" id="modalCancel" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 text-sm transition-colors">
                Batal
            </button>
            <button type="button" id="modalSubmit" class="px-6 py-2.5 bg-primary text-white font-semibold rounded-xl hover:bg-blue-800 text-sm transition-colors shadow-md shadow-primary/30">
                Proses Reschedule
            </button>
        </div>
    </div>
</div>

<script>
    let selectedBooking = null;

    const btnConfirm = document.getElementById('btnConfirm');
    const startTimeInput = document.getElementById('inputStartTime');
    const endTimeInput = document.getElementById('inputEndTime');
    const playDateInput = document.getElementById('inputPlayDate');

    const selectedCountLabel = document.getElementById('selectedCount');
    const totalPriceLabel = document.getElementById('totalPrice');
    const summaryDateLabel = document.getElementById('summaryDate');
    const summaryTimeLabel = document.getElementById('summaryTime');

    function attachSlotListeners() {
        document.querySelectorAll('.slot-btn:not(:disabled)').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.slot-btn').forEach(b => {
                    if(!b.disabled) {
                        b.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-md');
                        b.classList.add('bg-white', 'border-gray-200', 'text-gray-700');
                        b.querySelector('.price-label')?.classList.replace('text-blue-100', 'text-gray-500');
                    }
                });

                this.classList.remove('bg-white', 'border-gray-200', 'text-gray-700');
                this.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-md');
                this.querySelector('.price-label')?.classList.replace('text-gray-500', 'text-blue-100');

                const slotSection = document.getElementById('slot-section');
                const price = parseInt(this.dataset.price);

                selectedBooking = {
                    date: slotSection.dataset.viewDate,
                    formattedDate: slotSection.dataset.formattedDate,
                    start: this.dataset.start,
                    end: this.dataset.end,
                    price: price
                };

                playDateInput.value = selectedBooking.date;
                startTimeInput.value = selectedBooking.start;
                endTimeInput.value = selectedBooking.end;

                selectedCountLabel.textContent = '1';
                totalPriceLabel.textContent = 'Rp ' + price.toLocaleString('id-ID');
                summaryDateLabel.textContent = selectedBooking.formattedDate;
                summaryTimeLabel.textContent = selectedBooking.start.substring(0, 5) + ' - ' + selectedBooking.end.substring(0, 5);

                btnConfirm.disabled = false;
            });
        });
    }

    attachSlotListeners();

    // ==========================================
    // LOGIKA PINDAH TANGGAL (TANPA RELOAD HALAMAN)
    // ==========================================
    document.body.addEventListener('click', async function (e) {
        const link = e.target.closest('.ajax-link');
        if (link) {
            e.preventDefault();

            document.getElementById('slot-section').style.opacity = '0.5';
            document.getElementById('calendar-section').style.opacity = '0.5';

            try {
                const response = await fetch(link.href);
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');

                const newSlotSection = doc.getElementById('slot-section');
                const newCalendarSection = doc.getElementById('calendar-section');

                document.getElementById('slot-section').innerHTML = newSlotSection.innerHTML;
                document.getElementById('slot-section').dataset.viewDate = newSlotSection.dataset.viewDate;
                document.getElementById('slot-section').dataset.formattedDate = newSlotSection.dataset.formattedDate;
                document.getElementById('calendar-section').innerHTML = newCalendarSection.innerHTML;

                attachSlotListeners();

                const currentlyViewingDate = newSlotSection.dataset.viewDate;

                if (selectedBooking && currentlyViewingDate === selectedBooking.date) {
                    const activeBtn = document.querySelector(`.slot-btn[data-start="${selectedBooking.start}"][data-end="${selectedBooking.end}"]:not([disabled])`);
                    if (activeBtn) {
                        activeBtn.classList.remove('bg-white', 'border-gray-200', 'text-gray-700');
                        activeBtn.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-md');
                        activeBtn.querySelector('.price-label')?.classList.replace('text-gray-500', 'text-blue-100');
                    }
                }

            } catch (error) {
                window.location.href = link.href;
            } finally {
                document.getElementById('slot-section').style.opacity = '1';
                document.getElementById('calendar-section').style.opacity = '1';
            }
        }
    });

    document.getElementById('btnConfirm').addEventListener('click', function () {
        if (!selectedBooking) return;
        document.getElementById('reasonModal').classList.remove('hidden');
        document.getElementById('reasonModal').classList.add('flex');
    });

    document.getElementById('modalCancel').addEventListener('click', function () {
        document.getElementById('reasonModal').classList.add('hidden');
        document.getElementById('reasonModal').classList.remove('flex');
    });

    document.getElementById('modalSubmit').addEventListener('click', function () {
        const reason = document.getElementById('modalReason').value.trim();

        if (!reason) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Silakan isi alasan reschedule terlebih dahulu.',
                confirmButtonColor: '#2563EB',
                confirmButtonText: 'Mengerti'
            });
            return;
        }

        document.getElementById('inputReason').value = reason;
        this.disabled = true;
        this.innerHTML = 'Memproses...';

        document.getElementById('rescheduleForm').submit();
    });
</script>
@if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal Memproses!',
            text: "{!! session('error') !!}",
            confirmButtonColor: '#2563EB',
            confirmButtonText: 'Tutup'
        });
    @endif
@endsection
