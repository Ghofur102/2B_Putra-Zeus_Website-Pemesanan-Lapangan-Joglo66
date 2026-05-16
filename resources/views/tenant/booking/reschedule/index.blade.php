@extends('tenant.layouts.app')

@section('title', 'Ubah Jadwal Pemesanan')

@section('content')
<h1 class="text-[#1E3A5F] text-2xl font-bold mb-6">Ubah Jadwal Pemesanan</h1>

<div class="bg-[#DBEAFE] border-l-4 border-[#2563EB] rounded-lg p-4 mb-6">
    <p class="text-[#1D4ED8] font-bold">⚠️ PERINGATAN: ATURAN RESCHEDULE</p>
    <ul class="list-disc list-inside mt-2 text-sm text-[#374151] space-y-1">
        <li>Reschedule hanya bisa dilakukan <strong>minimal H-3</strong> sebelum jadwal bermain.</li>
        <li>Reschedule hanya <strong>1 kali</strong> per booking.</li>
        <li>Slot lama akan otomatis dibuka setelah reschedule berhasil.</li>
    </ul>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- KOLOM KIRI: KALENDER --}}
    <div class="bg-white border border-[#BFDBFE] rounded-xl p-5">
        <h2 class="text-[#1E3A5F] font-bold mb-3">Pilih Tanggal Baru:</h2>

        {{-- Navigasi bulan --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('booking.reschedule.form', [$detail->id, 'month' => $prevMonth->month, 'year' => $prevMonth->year, 'date' => $selectedDate]) }}"
               class="text-[#2563EB] hover:bg-[#EFF6FF] px-2 py-1 rounded text-sm">&larr; Sebelumnya</a>
            <span class="font-semibold text-sm">{{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</span>
            <a href="{{ route('booking.reschedule.form', [$detail->id, 'month' => $nextMonth->month, 'year' => $nextMonth->year, 'date' => $selectedDate]) }}"
               class="text-[#2563EB] hover:bg-[#EFF6FF] px-2 py-1 rounded text-sm">Berikutnya &rarr;</a>
        </div>

        {{-- Header hari --}}
        <div class="grid grid-cols-7 text-center text-xs font-semibold text-gray-500 mb-2">
            <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
        </div>

        {{-- Grid tanggal --}}
        <div class="grid grid-cols-7 text-center">
            @foreach ($calendar as $cell)
                @php
                    $isSelected = $cell['date'] === $selectedDate;
                    $linkUrl = route('booking.reschedule.form', [
                        $detail->id,
                        'month' => $month,
                        'year' => $year,
                        'date' => $cell['date'],
                    ]);
                @endphp

                @if (!$cell['isCurrentMonth'])
                    <div class="py-1.5 text-xs text-gray-300">{{ $cell['day'] }}</div>
                @elseif ($cell['isPast'])
                    <span class="py-1.5 text-xs text-gray-300">{{ $cell['day'] }}</span>
                @elseif ($isSelected)
                    <a href="{{ $linkUrl }}"
                       class="py-1.5 text-xs bg-[#2563EB] text-white rounded-full font-bold hover:bg-[#1D4ED8] mx-auto"
                       style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center">
                        {{ $cell['day'] }}
                    </a>
                @elseif ($cell['isToday'])
                    <a href="{{ $linkUrl }}"
                       class="py-1.5 text-xs text-[#2563EB] font-bold border border-[#2563EB] rounded-full hover:bg-[#EFF6FF] mx-auto"
                       style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center">
                        {{ $cell['day'] }}
                    </a>
                @else
                    <a href="{{ $linkUrl }}"
                       class="py-1.5 text-xs text-gray-700 hover:bg-[#EFF6FF] rounded-full mx-auto"
                       style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center">
                        {{ $cell['day'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- KOLOM KANAN: SESI JAM --}}
    <div class="bg-white border border-[#BFDBFE] rounded-xl p-5">
        <h2 class="text-[#1E3A5F] font-bold mb-3">Pilih Sesi Jam</h2>
        <p class="text-sm text-gray-500 mb-4">
            Tanggal: <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</strong>
        </p>

        @if (count($slots) === 0)
            <div class="text-sm text-gray-400 text-center py-8">Tidak ada sesi tersedia untuk tanggal ini.</div>
        @else
            <div class="grid grid-cols-2 gap-3">
                @foreach ($slots as $slot)
                    @php
                        $canSelect = $slot['is_available'];
                        $isPastSlot = \Carbon\Carbon::parse($selectedDate . ' ' . $slot['start'])->isPast();
                        $disabled = !$canSelect || $isPastSlot;
                    @endphp
                    <button type="button"
                            data-start="{{ $slot['start'] }}"
                            data-end="{{ $slot['end'] }}"
                            data-price="{{ $slot['price'] }}"
                            @if ($disabled) disabled @endif
                            class="slot-btn px-3 py-3 rounded-xl border text-sm font-medium transition
                                   @if ($disabled)
                                       bg-gray-100 border-gray-200 text-gray-300 cursor-not-allowed
                                   @else
                                       bg-white border-[#D1D5DB] text-gray-700 hover:bg-[#EFF6FF] hover:border-[#2563EB]
                                   @endif">
                        {{ substr($slot['start'], 0, 5) }} - {{ substr($slot['end'], 0, 5) }}
                        <span class="block text-xs mt-0.5
                            @if ($disabled) text-gray-300
                            @else text-gray-400 @endif">
                            Rp {{ number_format($slot['price'], 0, ',', '.') }}
                            @if (!$canSelect && !$isPastSlot)
                                &middot; Terisi
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- FORM (hidden inputs, submit via JS when confirm clicked) --}}
<form id="rescheduleForm" method="POST" action="{{ route('booking.reschedule.process', $detail->id) }}">
    @csrf
    <input type="hidden" name="new_play_date" id="inputPlayDate" value="{{ $selectedDate }}">
    <input type="hidden" name="new_start_play_time" id="inputStartTime">
    <input type="hidden" name="new_end_play_time" id="inputEndTime">
    <input type="hidden" name="reason" id="inputReason">
    <input type="hidden" name="confirmed" id="inputConfirmed">

    <div class="mt-8 flex justify-center">
        <button type="button" id="btnConfirm"
                class="px-12 py-4 bg-[#2563EB] text-white font-bold text-lg rounded-xl hover:bg-[#1D4ED8] transition disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
            Lanjut Konfirmasi
        </button>
    </div>
</form>

{{-- Modal Alasan --}}
<div id="reasonModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
        <h3 class="text-lg font-bold text-[#1E3A5F] mb-3">Alasan Reschedule</h3>
        <p class="text-sm text-gray-500 mb-3">Tuliskan alasan perubahan jadwal:</p>
        <textarea id="modalReason" rows="3"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  placeholder="Alasan..."></textarea>
        <div class="flex gap-3 mt-4 justify-end">
            <button type="button" id="modalCancel"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Batal</button>
            <button type="button" id="modalSubmit"
                    class="px-4 py-2 bg-[#2563EB] text-white rounded-lg hover:bg-[#1D4ED8] text-sm">Lanjutkan</button>
        </div>
    </div>
</div>

<script>
    let selectedSlot = null;
    const btnConfirm = document.getElementById('btnConfirm');
    const playDateInput = document.getElementById('inputPlayDate');
    const startTimeInput = document.getElementById('inputStartTime');
    const endTimeInput = document.getElementById('inputEndTime');

    document.querySelectorAll('.slot-btn:not(:disabled)').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.slot-btn').forEach(b => {
                b.classList.remove('bg-[#2563EB]', 'text-white', 'border-[#2563EB]');
                b.classList.add('bg-white', 'border-[#D1D5DB]', 'text-gray-700');
            });
            this.classList.remove('bg-white', 'border-[#D1D5DB]', 'text-gray-700');
            this.classList.add('bg-[#2563EB]', 'text-white', 'border-[#2563EB]');
            this.querySelector('span')?.classList.remove('text-gray-400');
            this.querySelector('span')?.classList.add('text-blue-200');

            selectedSlot = {
                start: this.dataset.start,
                end: this.dataset.end,
            };
            startTimeInput.value = this.dataset.start;
            endTimeInput.value = this.dataset.end;
            btnConfirm.disabled = false;
        });
    });

    document.getElementById('btnConfirm').addEventListener('click', function () {
        if (!selectedSlot) return;
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
            alert('Silakan isi alasan reschedule.');
            return;
        }
        document.getElementById('inputReason').value = reason;
        document.getElementById('inputConfirmed').value = '1';
        document.getElementById('rescheduleForm').submit();
    });
</script>
@endsection
