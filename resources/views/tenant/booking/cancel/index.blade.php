@extends('tenant.layouts.app')

@section('title', 'Konfirmasi Pembatalan')

@section('content')
{{-- HERO DUA KOLOM --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
    {{-- Kiri: Judul + instruksi --}}
    <div>
        <h1 class="text-3xl font-bold text-[#1E3A5F] mb-4">Konfirmasi Pembatalan</h1>
        <p class="text-[#374151] leading-relaxed">
            Anda akan membatalkan pemesanan lapangan. Silakan periksa kembali detail
            pemesanan Anda sebelum melanjutkan pembatalan. Pastikan Anda membaca
            syarat dan ketentuan pembatalan yang berlaku.
        </p>
    </div>

    {{-- Kanan: Card info booking --}}
    <div class="bg-[#EFF6FF] border border-[#BFDBFE] rounded-2xl p-5">
        {{-- Placeholder gambar --}}
        <div class="bg-gray-200 rounded-xl h-36 flex items-center justify-center text-gray-400 text-sm mb-4">
            Gambar Lapangan
        </div>

        <h3 class="font-bold text-[#1E3A5F] text-lg mb-3">{{ $detail->booking->field->name ?? 'Lapangan' }}</h3>

        <div class="space-y-2 text-sm text-gray-700">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#2563EB] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                <span>{{ $playDate->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#2563EB] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }} WIB</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#2563EB] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m-4-2.803a3 3 0 100-6 3 3 0 000 6z"/></svg>
                <span>{{ $detail->booking->team_name }} (10 orang)</span>
            </div>
        </div>
    </div>
</div>

{{-- CARD: SYARAT & KETENTUAN --}}
<div class="bg-white border border-[#DBEAFE] rounded-xl shadow-sm mb-6">
    <div class="bg-[#F0F9FF] px-5 py-3 rounded-t-xl border-b border-[#DBEAFE]">
        <h2 class="font-bold text-[#1E3A5F]">Syarat & Ketentuan Pembatalan</h2>
    </div>
    <div class="divide-y divide-gray-100 px-5 py-3 space-y-0">
        <div class="flex items-start gap-3 py-3">
            <svg class="w-5 h-5 text-[#2563EB] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            <p class="text-sm text-[#374151]">Pembatalan maksimal dilakukan <strong>H-3</strong> sebelum jadwal bermain.</p>
        </div>
        <div class="flex items-start gap-3 py-3">
            <svg class="w-5 h-5 text-[#DC2626] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01M10.29 3.86l-8.3 14.42A1.5 1.5 0 003.7 20h16.6a1.5 1.5 0 001.31-2.22l-8.3-14.42a1.5 1.5 0 00-2.62 0z"/></svg>
            <p class="text-sm text-[#374151]">Jika melewati batas waktu, <strong>DP hangus</strong> dan tidak dapat dikembalikan.</p>
        </div>
        <div class="flex items-start gap-3 py-3">
            <svg class="w-5 h-5 text-[#2563EB] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            <p class="text-sm text-[#374151]">Jika sesuai syarat, DP dapat dikembalikan sesuai kebijakan refund.</p>
        </div>
    </div>
</div>

{{-- CARD: ALASAN PEMBATALAN --}}
<div class="bg-white border border-[#DBEAFE] rounded-xl shadow-sm mb-6">
    <div class="bg-[#F0F9FF] px-5 py-3 rounded-t-xl border-b border-[#DBEAFE]">
        <h2 class="font-bold text-[#1E3A5F]">Alasan Pembatalan</h2>
    </div>
    <div class="px-5 py-4 space-y-3">
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="reason_option" value="Tim berhalangan hadir" class="accent-[#2563EB] w-4 h-4">
            <span class="text-sm text-gray-700">Tim berhalangan hadir</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="reason_option" value="Jadwal Bentrok" class="accent-[#2563EB] w-4 h-4">
            <span class="text-sm text-gray-700">Jadwal Bentrok</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="reason_option" value="Kondisi Cuaca" class="accent-[#2563EB] w-4 h-4">
            <span class="text-sm text-gray-700">Kondisi Cuaca</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" name="reason_option" value="Alasan Lainnya" class="accent-[#2563EB] w-4 h-4">
            <span class="text-sm text-gray-700">Alasan Lainnya</span>
        </label>

        <textarea id="cancelReason" rows="3"
                  class="w-full border border-[#DBEAFE] rounded-lg px-4 py-3 text-sm focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB] outline-none mt-2"
                  placeholder="Tuliskan alasan pembatalan Anda di sini..."></textarea>
    </div>
</div>

{{-- CARD: RINGKASAN STATUS REFUND / DP --}}
<div class="bg-white border border-[#DBEAFE] rounded-xl shadow-sm mb-6">
    <div class="bg-[#DBEAFE] px-5 py-3 rounded-t-xl">
        <h2 class="font-bold text-[#1E3A5F]">Ringkasan Status Refund / DP</h2>
    </div>
    <div class="px-5 py-4 space-y-3">
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Nominal DP</span>
            <span class="font-semibold">Rp {{ number_format($netPaid, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Nominal yang Dikembalikan</span>
            <span class="font-semibold text-green-600">Rp {{ number_format($isRefundable ? $refundAmount : 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Nominal yang Hangus</span>
            <span class="font-semibold text-red-600">Rp {{ number_format($isRefundable ? 0 : $netPaid, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-sm border-t border-gray-100 pt-3">
            <span class="font-semibold text-gray-700">Total Refund</span>
            <span class="font-bold text-[#2563EB]">Rp {{ number_format($isRefundable ? $refundAmount : 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Status Proses Refund</span>
            <span class="text-gray-400 italic">
                @if ($isRefundable)
                    Akan diproses setelah konfirmasi
                @else
                    Tidak ada refund (DP Hangus)
                @endif
            </span>
        </div>
    </div>
    <div class="px-5 py-3 bg-gray-50 rounded-b-xl border-t border-[#DBEAFE] flex items-start gap-2 text-xs text-gray-500">
        <svg class="w-4 h-4 text-[#2563EB] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
        <span>Refund akan diproses secara otomatis dalam 1x24 jam setelah pembatalan berhasil dikonfirmasi.</span>
    </div>
</div>

{{-- FORM --}}
<form id="cancelForm" method="POST" action="{{ route('booking.cancel.process', $detail->id) }}">
    @csrf
    <input type="hidden" name="reason" id="inputReason">

    {{-- FOOTER TOMBOL --}}
    <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
        <a href="{{ route('booking.history.show', $detail->id) }}"
           class="w-full sm:w-auto px-8 py-3 bg-[#6B7280] text-white font-bold rounded-xl hover:bg-[#4B5563] transition text-center">
            &larr; Kembali
        </a>
        <button type="button" id="btnSubmit"
                class="w-full sm:w-auto px-8 py-3 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-[#1D4ED8] transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Lanjut Konfirmasi &rarr;
        </button>
    </div>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" id="agreeCheck" class="accent-[#2563EB] w-4 h-4 mt-0.5">
        <span class="text-sm text-gray-600">Dengan menekan tombol ini, anda menyetujui syarat dan ketentuan diatas.</span>
    </label>
</form>

<script>
    const reasonTextarea = document.getElementById('cancelReason');
    const reasonHidden = document.getElementById('inputReason');
    const agreeCheck = document.getElementById('agreeCheck');
    const btnSubmit = document.getElementById('btnSubmit');
    const radioButtons = document.querySelectorAll('input[name="reason_option"]');

    function updateSubmitState() {
        const hasReason = reasonHidden.value.trim().length > 0;
        btnSubmit.disabled = !(hasReason && agreeCheck.checked);
    }

    radioButtons.forEach(rb => {
        rb.addEventListener('change', function () {
            if (this.checked) {
                const val = this.value;
                if (val === 'Alasan Lainnya') {
                    reasonTextarea.value = '';
                    reasonTextarea.focus();
                } else {
                    reasonTextarea.value = val;
                }
                reasonHidden.value = reasonTextarea.value;
                updateSubmitState();
            }
        });
    });

    reasonTextarea.addEventListener('input', function () {
        reasonHidden.value = this.value;
        updateSubmitState();
    });

    agreeCheck.addEventListener('change', updateSubmitState);

    document.getElementById('btnSubmit').addEventListener('click', function () {
        if (!this.disabled) {
            document.getElementById('cancelForm').submit();
        }
    });
</script>
@endsection
