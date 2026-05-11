@extends('layouts.app')
<script src="https://cdn.tailwindcss.com"></script>

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Success Message -->
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6 text-center">
            <div class="text-5xl mb-3">✓</div>
            <h1 class="text-3xl font-bold mb-2">Pemesanan Berhasil Dibuat!</h1>
            <p class="text-lg">ID Pemesanan: <strong>#{{ $booking->id }}</strong></p>
        </div>

        <!-- Booking Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Detail Pemesanan</h2>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-gray-600 text-sm">ID Pemesanan</p>
                    <p class="font-semibold text-lg">#{{ $booking->id }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Tanggal Pemesanan</p>
                    <p class="font-semibold text-lg">{{ $booking->booking_date }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Nama Tim</p>
                    <p class="font-semibold text-lg">{{ $booking->team_name }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Lapangan</p>
                    <p class="font-semibold text-lg">{{ $booking->field->name }}</p>
                </div>
            </div>

            <div class="border-t pt-4">
                <p class="text-gray-600 text-sm mb-2">Kontak</p>
                <p class="mb-1">
                    <strong>Telepon:</strong> {{ $booking->customer_phone }}
                </p>
                <p class="mb-3">
                    <strong>Email:</strong> {{ $booking->customer_email }}
                </p>
                @if($booking->notes && $booking->notes !== '-')
                    <p>
                        <strong>Catatan:</strong> {{ $booking->notes }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Slot Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Slot yang Dipesan</h2>

            <div class="space-y-2">
                @forelse($booking->details as $detail)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold">{{ $detail->start_play_time }} - {{ $detail->end_play_time }}</p>
                            <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($detail->play_date)->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-blue-600">Rp {{ number_format($detail->price, 0, ',', '.') }}</p>
                            <p class="text-sm text-gray-500">
                                @if($detail->status === 'waiting')
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Menunggu Pembayaran</span>
                                @else
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">{{ ucfirst($detail->status) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">Tidak ada slot yang dipesan</p>
                @endforelse
            </div>

            <div class="border-t mt-4 pt-4">
                <div class="flex justify-between items-center">
                    <p class="font-semibold text-lg">Total Harga:</p>
                    <p class="text-2xl font-bold text-blue-600">
                        Rp {{ number_format($booking->details->sum('price'), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="font-semibold text-blue-900 mb-3">Langkah Selanjutnya</h3>
            <ol class="list-decimal list-inside space-y-2 text-blue-900">
                <li>Lanjutkan ke halaman pembayaran untuk mengaktifkan pemesanan</li>
                <li>Pilih metode pembayaran (Cash atau transfer bank)</li>
                <li>Setelah pembayaran dikonfirmasi, status akan berubah menjadi "Aktif"</li>
                <li>Anda dapat melihat riwayat pemesanan di halaman dashboard</li>
            </ol>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <a href="{{ route('tenant.booking.schedule') }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg text-center transition duration-200">
                Buat Pemesanan Baru
            </a>
            <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200" onclick="redirectToPayment()">
                Lanjut ke Pembayaran
            </button>
        </div>
    </div>
</div>

<script>
function redirectToPayment() {
    // This will redirect to payment page (payment team will provide the URL)
    // Placeholder for now
    alert('Fitur pembayaran akan segera tersedia. Hubungi admin jika ada pertanyaan.');
    // window.location.href = '/payment/' + {{ $booking->id }};
}
</script>
@endsection
