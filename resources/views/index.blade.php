@extends('tenant.layouts.app')

@section('title', 'Beranda')

@section('content')
    <h2 class="text-2xl font-bold mb-4 text-black">Informasi Lapangan</h2>

    <div class="flex flex-col md:flex-row justify-between items-start mb-10 gap-6">
        <div class="flex flex-col gap-4">
            <div class="text-base text-gray-800">
                Lapangan:
                <select id="fieldSelect"
                    data-route="{{ route('tenant.booking.dashboard') }}"
                    class="border-b border-dashed border-gray-500 bg-transparent text-black font-medium outline-none cursor-pointer pb-1 focus:border-primary focus:ring-0">
                    <option value="">-- Pilih Lapangan --</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}" {{ $selectedFieldId == $field->id ? 'selected' : '' }}>
                            {{ $field->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="{{ $selectedFieldId ? 'block' : 'hidden' }} text-base text-gray-800">
                Status lapangan: <span class="text-green-500 font-bold">Tersedia</span>
            </div>

            @if($selectedFieldId)
                <a href="{{ route('tenant.booking.create-form', ['field_id' => $selectedFieldId]) }}" class="bg-primary text-white px-6 py-2.5 rounded-full text-base font-medium inline-block mt-2 w-fit hover:bg-blue-800 transition shadow-sm">Pesan Now</a>
            @else
                <a href="#" id="disabledPesanBtn" class="bg-primary opacity-70 text-white px-6 py-2.5 rounded-full text-base font-medium inline-block mt-2 w-fit cursor-not-allowed shadow-sm">Pesan Now</a>
            @endif
        </div>

        <div
            class="w-full md:w-80 h-48 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center shadow-inner border border-gray-200">
            @if ($selectedField && $selectedField->image_url)
                <img src="{{ asset($selectedField->image_url) }}" alt="Gambar Lapangan" class="w-full h-full object-cover">
            @else
                <span class="text-gray-400 text-sm font-medium">Gambar tidak tersedia</span>
            @endif
        </div>
    </div>

    <h2 class="text-2xl font-bold mb-4 text-black mt-10">Booking Terdekat</h2>
    <div class="mb-10 flex flex-col gap-3">
        @if ($selectedField && $nearestBookings->isNotEmpty())
            @foreach ($nearestBookings as $data)
                @include('tenant.components.booking-row', [
                    'dayMonth' => $data['day_month'],
                    'year' => $data['year'],
                    'time' => $data['time'],
                    'teamName' => $data['team_name'],
                    'statusText' => $data['status_text'],
                ])
            @endforeach
        @else
            <div class="text-center p-6 border border-dashed border-gray-400 rounded-md text-gray-500 bg-gray-50">
                Tidak ada booking tersedia
            </div>
        @endif
    </div>

    <h2 class="text-2xl font-bold mb-4 text-black">Riwayat</h2>
    <div class="flex flex-col gap-3">
        @if ($selectedField && $userBookings->isNotEmpty())
            @foreach ($userBookings as $data)
                @include('tenant.components.booking-row', [
                    'dayMonth' => $data['day_month'],
                    'year' => $data['year'],
                    'time' => $data['time'],
                    'teamName' => $data['team_name'],
                    'statusText' => $data['status_text'],
                ])
            @endforeach
        @else
            <div class="text-center p-6 border border-dashed border-gray-400 rounded-md text-gray-500 bg-gray-50">
                Riwayat belum tersedia
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
