@extends('tenant.layouts.app')

@section('title', 'Ringkasan Booking')

@section('content')
<div style="background:#F8FAFC;margin:-1.5rem -1rem;padding:3rem 1rem;min-height:70vh">
    <div class="bg-white shadow-sm rounded-2xl p-8 mx-auto" style="max-width:700px">
        {{-- JUDUL --}}
        <h2 class="text-2xl font-bold text-[#1E3A5F] mb-6">Ringkasan Booking</h2>

        {{-- TABEL RINGKASAN --}}
        <div class="bg-[#EFF6FF] rounded-xl p-5 mb-6">
            <div class="flex justify-between items-center py-3 border-b border-[#BFDBFE]">
                <span class="text-[#6B7280]">Tanggal</span>
                <span class="text-[#1E3A5F] font-bold">{{ \Carbon\Carbon::parse($detail->play_date)->format('d F Y') }}</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-[#BFDBFE]">
                <span class="text-[#6B7280]">Jam bermain</span>
                <span class="text-[#1E3A5F] font-bold">
                    {{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}
                </span>
            </div>
            <div class="flex justify-between items-center py-3">
                <span class="text-[#6B7280]">Lapangan yang dipesan</span>
                <span class="text-[#1E3A5F] font-bold">{{ $detail->booking->field->name ?? '-' }}</span>
            </div>
        </div>

        {{-- STATUS PEMBAYARAN --}}
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-[#1E3A5F]">Status Pembayaran</h3>
            <span class="font-bold italic text-[#2563EB]">
                @if ($isRefundable)
                    DP dikembalikan
                @else
                    DP hangus
                @endif
            </span>
        </div>

        {{-- CATATAN KEBIJAKAN (ACCORDION) --}}
        <div class="mb-8">
            <button type="button" id="toggleNotes"
                    class="text-[#2563EB] underline hover:text-[#1D4ED8] text-sm font-medium transition">
                Catatan kebijakan pembatalan
            </button>
            <div id="notesContent" class="hidden mt-3 bg-[#F0F9FF] rounded-lg p-4 text-sm text-gray-600 leading-relaxed">
                <p>Pembatalan yang dilakukan minimal H-3 sebelum jadwal bermain akan mendapatkan refund penuh
                   dari DP yang sudah dibayarkan. Proses refund dilakukan otomatis dalam 1x24 jam setelah
                   pembatalan berhasil dikonfirmasi.</p>
                <p class="mt-2">Pembatalan yang dilakukan kurang dari H-3 sebelum jadwal bermain mengakibatkan
                   DP hangus dan tidak dapat dikembalikan.</p>
            </div>
        </div>

        {{-- FOOTER TOMBOL --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('tenant.booking.process.cancelled', $detail->id) }}"
               class="w-full sm:w-1/2 text-center px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">
                Batal Konfirmasi
            </a>
            <form method="POST" action="{{ route('tenant.booking.process.cancelled', $detail->id) }}" class="w-full sm:w-1/2">
                @csrf
                <input type="hidden" name="reason" value="{{ $validated['reason'] }}">
                <input type="hidden" name="confirmed" value="1">
                <button type="submit"
                        class="w-full px-6 py-3 bg-[#DC2626] text-white font-bold rounded-xl hover:bg-[#B91C1C] transition shadow-sm">
                    Konfirmasi Pembatalan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('toggleNotes').addEventListener('click', function () {
        const content = document.getElementById('notesContent');
        const isHidden = content.classList.contains('hidden');
        content.classList.toggle('hidden');
        this.textContent = isHidden ? 'Sembunyikan catatan kebijakan pembatalan' : 'Catatan kebijakan pembatalan';
    });
</script>
@endsection
