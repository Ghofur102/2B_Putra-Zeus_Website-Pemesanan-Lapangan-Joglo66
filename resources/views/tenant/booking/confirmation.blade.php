@extends('layouts.app')
<script src="https://cdn.tailwindcss.com"></script>

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-2">Konfirmasi Pemesanan</h1>
    <p class="text-gray-600 mb-6">Periksa kembali data pemesanan Anda sebelum melanjutkan</p>

    <div class="space-y-6">
            <!-- Selected Slots Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Slot Pemesanan</h2>
                <div class="border-t pt-4">
                    <p class="mb-2">
                        <strong>Lapangan:</strong> {{ $field->name }}
                    </p>
                    <p class="mb-4">
                        <strong>Tanggal:</strong> {{ $booking_date->format('d/m/Y') }} ({{ $booking_date->format('l') }})
                    </p>

                    <div class="bg-gray-50 rounded p-4">
                        <h3 class="font-semibold mb-3">Jam-jam yang dipilih:</h3>
                        <ul class="space-y-2">
                            @foreach($selected_slots as $index => $slot)
                                <li class="flex justify-between items-center">
                                    <span>{{ $slot['start_time'] }} - {{ $slot['end_time'] }}</span>
                                    <span class="text-blue-600 font-semibold">Rp {{ number_format($slot['price'], 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-4 pt-4 border-t text-right">
                        <p class="text-gray-600 mb-1">Total Harga</p>
                        <p class="text-3xl font-bold text-blue-600">Rp {{ number_format($total_price, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Data Penyewa</h2>

                <form action="{{ route('tenant.booking.store') }}" method="POST">
                    @csrf

                    <!-- Hidden inputs -->
                    <input type="hidden" name="field_id" value="{{ $field->id }}">
                    <input type="hidden" name="booking_date" value="{{ $booking_date->format('Y-m-d') }}">
                    <input type="hidden" name="selected_slots" value="{{ json_encode($selected_slots) }}">

                    <!-- Team Name -->
                    <div class="mb-4">
                        <label for="team_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Tim <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="team_name"
                            name="team_name"
                            required
                            maxlength="50"
                            value="{{ old('team_name') }}"
                            placeholder="Nama tim Anda"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        @error('team_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="mb-4">
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Nomor Telepon <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="tel"
                            id="customer_phone"
                            name="customer_phone"
                            required
                            maxlength="50"
                            value="{{ old('customer_phone') }}"
                            placeholder="Nomor telepon Anda"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        @error('customer_phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="customer_email"
                            name="customer_email"
                            required
                            maxlength="50"
                            value="{{ old('customer_email') }}"
                            placeholder="Email Anda"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        @error('customer_email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan (Opsional)
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            maxlength="50"
                            rows="3"
                            placeholder="Catatan tambahan (misal: no. jersey, preferensi lapangan, dll)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Terms -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-900 mb-2">
                            ⚠️ <strong>Perhatian:</strong> Dengan mengklik "Konfirmasi & Lanjut Pembayaran", Anda setuju untuk melanjutkan ke tahap pembayaran. 
                            Pembayaran harus dilakukan untuk mengaktifkan pemesanan.
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-4">
                        <a href="javascript:history.back()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg text-center transition duration-200">
                            Kembali
                        </a>
                        <button
                            type="submit"
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                        >
                            Konfirmasi & Lanjut Pembayaran
                        </button>
                    </div>

                    @if($errors->any())
                        <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </form>
            </div>
        </div>
</div>
@endsection
