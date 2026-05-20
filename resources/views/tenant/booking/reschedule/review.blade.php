@extends('tenant.layouts.app')

@section('title', 'Konfirmasi Perubahan')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center" style="background:#F8FAFC;margin:-1.5rem -1rem;padding:2rem 1rem">
    <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-2xl">
        {{-- HEADER --}}
        <h1 class="text-center text-2xl font-bold text-[#1E3A5F] mb-8">Konfirmasi Perubahan</h1>

        {{-- PERBANDINGAN JADWAL 3 KOLOM --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 items-center gap-4 text-center mb-8">
            {{-- Kiri: Jadwal Lama --}}
            <div class="text-[#6B7280]">
                <p class="font-semibold">{{ \Carbon\Carbon::parse($detail->play_date)->format('d F Y') }}</p>
                <p class="text-sm">{{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}</p>
            </div>

            {{-- Tengah: Panah --}}
            <div class="text-[#2563EB] text-3xl font-bold">&raquo;&raquo;</div>

            {{-- Kanan: Jadwal Baru --}}
            <div class="text-[#1E3A5F] font-bold">
                <p class="text-lg">{{ \Carbon\Carbon::parse($validated['new_play_date'])->format('d F Y') }}</p>
                <p>{{ $validated['new_start_play_time'] }} - {{ $validated['new_end_play_time'] }}</p>
            </div>
        </div>

        {{-- DIVIDER --}}
        <hr class="border-[#BFDBFE] mb-6">

        {{-- RINGKASAN TAGIHAN --}}
        <div class="space-y-3 mb-8">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-gray-700">Sisa tagihan awal</span>
                <span class="font-semibold text-gray-700">Rp {{ number_format($oldPrice, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="font-semibold text-gray-700">Tagihan sekarang</span>
                <span class="font-semibold text-[#DC2626]">
                    @if ($priceDiff > 0)
                        + Rp {{ number_format($priceDiff, 0, ',', '.') }}
                    @elseif ($priceDiff < 0)
                        - Rp {{ number_format(abs($priceDiff), 0, ',', '.') }}
                    @else
                        Rp 0
                    @endif
                </span>
            </div>
            <div class="flex justify-between items-center border-t border-[#BFDBFE] pt-3">
                <span class="font-semibold text-gray-700">Total sisa tagihan</span>
                <span class="font-bold text-xl text-[#2563EB]">Rp {{ number_format($newPrice, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- FOOTER TOMBOL --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('tenant.booking.process.reschedule', $detail->id) }}"
               class="block text-center px-6 py-3 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                Batal Konfirmasi
            </a>
            <form method="POST" action="{{ route('tenant.booking.process.reschedule', $detail->id) }}" class="block">
                @csrf
                <input type="hidden" name="detail_booking_id" value="{{ $detail->id }}">
                <input type="hidden" name="new_play_date" value="{{ $validated['new_play_date'] }}">
                <input type="hidden" name="new_start_play_time" value="{{ $validated['new_start_play_time'] }}">
                <input type="hidden" name="new_end_play_time" value="{{ $validated['new_end_play_time'] }}">
                <input type="hidden" name="reason" value="{{ $validated['reason'] }}">
                <input type="hidden" name="confirmed" value="1">
                <button type="submit"
                        class="w-full px-6 py-3 bg-[#2563EB] text-white font-semibold rounded-xl shadow-[0_4px_12px_rgba(37,99,235,0.4)] hover:bg-[#1D4ED8] transition">
                    Konfirmasi & Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
