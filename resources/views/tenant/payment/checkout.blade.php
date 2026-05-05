{{-- Checkout Pembayaran --}}
@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Konfirmasi Pesanan</h2>
    <div class="card mb-3">
        <div class="card-body">
            <h5>Booking ID: {{ $booking['id'] }}</h5>
            <p>Nama Pemesan: {{ $booking['user_name'] }}</p>
            <p>Tanggal: {{ $booking['tanggal'] }}</p>
            <p>Jam: {{ $booking['jam'] }}</p>
            <p>Total Harga: Rp{{ number_format($booking['total_price'], 0, ',', '.') }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('payment.checkout.process') }}">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking['id'] }}">
        <div class="mb-3">
            <label>Pilih Metode Pembayaran:</label><br>
            <input type="radio" name="metode_pembayaran" value="dp" checked> DP 50%<br>
            <input type="radio" name="metode_pembayaran" value="lunas"> Lunas
        </div>
        <button type="submit" class="btn btn-primary">Lanjut ke Pembayaran</button>
    </form>
</div>
@endsection
