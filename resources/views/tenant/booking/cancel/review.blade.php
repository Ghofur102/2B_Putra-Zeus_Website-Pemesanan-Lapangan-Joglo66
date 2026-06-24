@extends('tenant.layouts.app')

@section('title', 'Ringkasan Booking')

@section('content')
<div class="py-8 flex items-center justify-center">
    <x-tenant-card variant="default" class="w-full max-w-2xl">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Ringkasan Booking</h2>

        <div class="bg-primary-light border border-primary/10 rounded-tenant-md p-5 mb-6 text-sm flex flex-col gap-3">
            <div class="flex justify-between items-center pb-2 border-b border-gray-200/60">
                <span class="text-gray-500 font-medium">Tanggal</span>
                <span class="text-gray-900 font-bold">{{ \Carbon\Carbon::parse($detail->play_date)->format('d F Y') }}</span>
            </div>
            <div class="flex justify-between items-center pb-2 border-b border-gray-200/60">
                <span class="text-gray-500 font-medium">Jam bermain</span>
                <span class="text-gray-900 font-bold">
                    {{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-500 font-medium">Lapangan yang dipesan</span>
                <span class="text-gray-900 font-bold">{{ $detail->booking->field->name ?? '-' }}</span>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-gray-800 text-sm">Status Pembayaran</h3>
            <span class="text-xs font-black uppercase tracking-wide px-3 py-1 border rounded-tenant-sm {{ $isRefundable ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                {{ $isRefundable ? 'DP dikembalikan' : 'DP hangus' }}
            </span>
        </div>

        <div class="mb-8">
            <button type="button" id="toggleNotes" class="text-primary underline hover:text-primary-hover text-sm font-semibold transition-colors cursor-pointer outline-none">
                Catatan kebijakan pembatalan
            </button>
            <div id="notesContent" class="hidden mt-4 bg-gray-50 border border-gray-100 rounded-tenant-md p-4 text-sm text-gray-600 leading-relaxed flex flex-col gap-2 shadow-inner">
                <p>Pembatalan yang dilakukan minimal H-3 sebelum jadwal bermain akan mendapatkan refund penuh dari DP yang sudah dibayarkan. Proses refund dilakukan otomatis dalam 1x24 jam setelah pembatalan berhasil dikonfirmasi.</p>
                <p>Pembatalan yang dilakukan kurang dari H-3 sebelum jadwal bermain mengakibatkan DP hangus dan tidak dapat dikembalikan.</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4">
            <x-tenant-button href="javascript:history.back()" variant="danger" class="w-full sm:w-1/2 py-3">
                Batal Konfirmasi
            </x-tenant-button>

            <form method="POST" action="{{ route('tenant.booking.process.cancelled', $detail->id) }}" class="w-full sm:w-1/2">
                @csrf
                <input type="hidden" name="reason" value="{{ $validated['reason'] }}">
                <input type="hidden" name="confirmed" value="1">
                <x-tenant-button type="submit" variant="primary" class="w-full py-3 bg-red-600 hover:bg-red-700 shadow-tenant-sm">
                    Konfirmasi Pembatalan
                </x-tenant-button>
            </form>
        </div>
    </x-tenant-card>
</div>
@endsection
