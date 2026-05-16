@extends('tenant.layouts.app')

@section('title', 'Riwayat Booking')

@section('content')
<h1 class="text-2xl font-bold mb-6">Riwayat Booking</h1>

@if ($details->isEmpty())
    <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
        Belum ada booking. Silakan booking lapangan terlebih dahulu.
    </div>
@else
    <div class="space-y-4">
        @foreach ($details as $detail)
            <a href="{{ route('booking.history.show', $detail->id) }}"
               class="block bg-white border border-gray-200 rounded-lg p-4 hover:border-green-400 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-semibold">{{ $detail->booking->field->name ?? '-' }}</span>
                        <span class="text-gray-500 text-sm ml-2">{{ $detail->booking->team_name }}</span>
                    </div>
                    <span class="text-xs px-2 py-1 rounded {{ $detail->status === 'active' ? 'bg-green-100 text-green-700' : ($detail->status === 'waiting' ? 'bg-yellow-100 text-yellow-700' : ($detail->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')) }}">
                        {{ ucfirst($detail->status) }}
                    </span>
                </div>
                <div class="text-sm text-gray-500 mt-1">
                    {{ \Carbon\Carbon::parse($detail->play_date)->format('d M Y') }}
                    {{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} -
                    {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
