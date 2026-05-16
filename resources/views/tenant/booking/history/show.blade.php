@extends('tenant.layouts.app')

@section('title', 'Detail Booking')

@section('content')
<div class="mb-4">
    <a href="{{ route('booking.history') }}" class="text-sm text-green-700 hover:underline">&larr; Kembali ke Riwayat</a>
</div>

<h1 class="text-2xl font-bold mb-6">Detail Booking</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <span class="text-gray-500">Lapangan</span>
            <p class="font-semibold">{{ $detail->booking->field->name ?? '-' }}</p>
        </div>
        <div>
            <span class="text-gray-500">Tim</span>
            <p class="font-semibold">{{ $detail->booking->team_name }}</p>
        </div>
        <div>
            <span class="text-gray-500">Tanggal Main</span>
            <p class="font-semibold">{{ $playDate->format('d M Y') }}</p>
        </div>
        <div>
            <span class="text-gray-500">Jam</span>
            <p class="font-semibold">{{ $start->format('H:i') }} - {{ $end->format('H:i') }}</p>
        </div>
        <div>
            <span class="text-gray-500">Durasi</span>
            <p class="font-semibold">{{ $duration }} jam</p>
        </div>
        <div>
            <span class="text-gray-500">Status</span>
            <p class="font-semibold">{{ ucfirst($detail->status) }}</p>
        </div>
        <div>
            <span class="text-gray-500">Harga</span>
            <p class="font-semibold">Rp {{ number_format($detail->price, 0, ',', '.') }}</p>
        </div>
        <div>
            <span class="text-gray-500">Total Dibayar</span>
            <p class="font-semibold">Rp {{ number_format($detail->booking->payments->where('status', 'success')->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount'), 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($detail->notes && $detail->notes !== '-')
    <div>
        <span class="text-gray-500 text-sm">Catatan</span>
        <p>{{ $detail->booking->notes }}</p>
    </div>
    @endif
</div>

@if ($detail->booking->payments->count() > 0)
<div class="bg-white border border-gray-200 rounded-lg p-6 mt-4">
    <h2 class="font-semibold mb-3">Riwayat Pembayaran</h2>
    <div class="space-y-2 text-sm">
        @foreach ($detail->booking->payments as $payment)
        <div class="flex justify-between items-center border-b pb-2">
            <div>
                <span class="capitalize">{{ $payment->payment_type }}</span>
                <span class="text-gray-400 text-xs ml-2">{{ $payment->reference_id }}</span>
            </div>
            <div class="text-right">
                <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                <span class="text-xs ml-2 {{ $payment->status === 'success' ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if (in_array($detail->status, ['active', 'waiting', 'reschedule']))
<div class="flex gap-3 mt-6">
    @if ($canReschedule && !$alreadyRescheduled)
        <a href="{{ route('booking.reschedule.form', $detail->id) }}"
           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
            Ubah Jadwal (Reschedule)
        </a>
    @endif
    @if ($canCancel)
        <a href="{{ route('booking.cancel.form', $detail->id) }}"
           class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
            Batalkan Booking
        </a>
    @endif
</div>
@if ($detail->status === 'reschedule')
    <div class="mt-3 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-3">
        Booking ini sudah direschedule. Tidak dapat mengubah jadwal lagi.
    </div>
@endif
@if (!$canReschedule && $detail->status !== 'reschedule' && $detail->status !== 'cancelled')
    <div class="mt-3 text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded p-3">
        Reschedule dan pembatalan hanya bisa dilakukan minimal H-3 sebelum jadwal bermain.
    </div>
@endif
@endif
@endsection
