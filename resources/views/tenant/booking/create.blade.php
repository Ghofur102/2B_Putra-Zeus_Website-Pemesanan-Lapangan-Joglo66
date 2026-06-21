@extends('tenant.layouts.app')

@section('title', 'Pemesanan Lapangan')

@section('content')
<div id="booking-app" data-field-id="{{ $field->id }}" data-fetch-url="{{ route('tenant.booking.fetch-slots') }}">

    <x-tenant-card variant="accent" class="mb-8">
        <div class="flex flex-col gap-2">
            <p class="text-gray-700 text-sm"><span class="font-bold text-primary">Lapangan Dipilih:</span> {{ $field->name }}</p>
            <p class="text-gray-700 text-sm"><span class="font-bold text-primary">Tanggal & Slot:</span> <span id="selectedInfo" class="font-medium text-gray-900">Belum dipilih</span></p>
        </div>
    </x-tenant-card>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <x-tenant-card variant="default">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Slot Jam Tersedia</h3>
            <div class="grid grid-cols-2 gap-4" id="slotGrid">
                <div class="col-span-2 text-center py-8 text-gray-400 text-sm bg-gray-50 rounded-tenant-md border border-dashed border-gray-300">
                    Pilih tanggal untuk melihat slot
                </div>
            </div>
        </x-tenant-card>

        <x-tenant-card variant="default">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Pilih Tanggal</h3>

            <div class="flex justify-between items-center mb-6">
                <button type="button" id="btnPrevMonth" class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-tenant-md transition-all font-bold cursor-pointer">&lt;</button>
                <div class="text-base font-bold text-gray-800" id="calendarMonth"></div>
                <button type="button" id="btnNextMonth" class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-tenant-md transition-all font-bold cursor-pointer">&gt;</button>
            </div>

            <div class="grid grid-cols-7 gap-2 mb-2">
                @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $day)
                    <div class="text-center text-xs font-bold text-gray-400 py-1">{{ $day }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-2" id="calendarDays"></div>
        </x-tenant-card>
    </div>

    <x-tenant-card variant="default">
        <div class="bg-primary-light border border-primary/10 p-4 rounded-tenant-md mb-6 border-l-4 border-l-primary">
            <h4 class="font-bold text-gray-800 mb-3">Ringkasan Pilihan Anda</h4>
            <div class="flex gap-8">
                <div>
                    <div class="text-xs text-gray-500 font-medium">Slot Dipilih:</div>
                    <div class="text-lg font-bold text-primary" id="selectedCount">0</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium">Total Harga:</div>
                    <div class="text-lg font-bold text-primary" id="totalPrice">Rp 0</div>
                </div>
            </div>
        </div>

        <form action="{{ route('tenant.booking.confirm-form') }}" method="POST" id="bookingForm">
            @csrf
            <input type="hidden" name="field_id" value="{{ $field->id }}">
            <input type="hidden" id="formBookingDate" name="booking_date">
            <input type="hidden" id="formSelectedSlots" name="selected_slots">

            <div class="flex justify-end gap-4 mt-6">
                <x-tenant-button :href="route('tenant.booking.dashboard')" variant="danger" class="px-6 py-2.5">
                    Kembali
                </x-tenant-button>
                <x-tenant-button type="submit" id="submitBtn" disabled variant="primary" class="px-6 py-2.5">
                    Lanjut ke Konfirmasi
                </x-tenant-button>
            </div>
        </form>
    </x-tenant-card>
</div>
@endsection
