@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Checkout Pembayaran</h2>
    <div class="card mb-3">
        <div class="card-body">
            <h5>Booking ID: {{ $booking->id }}</h5>
            <p>Nama Pemesan: {{ $booking->user->name ?? '-' }}</p>
            <p>Total Harga: Rp{{ number_format($booking->total_price, 0, ',', '.') }}</p>
        </div>
    </div>
    <form action="{{ route('payment.process') }}" method="POST">
        @csrf
        <input type="hidden" name="fk_booking_id" value="{{ $booking->id }}">
        <div class="mb-3">
            <label for="payment_type" class="form-label">Tipe Pembayaran</label>
            <select name="payment_type" id="payment_type" class="form-control" required>
                <option value="">-- Pilih --</option>
                <option value="down payment">Down Payment</option>
                <option value="final payment">Final Payment</option>
                <option value="reschedule fee">Reschedule Fee</option>
                <option value="refund">Refund</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="method" class="form-label">Metode Pembayaran</label>
            <select name="method" id="method" class="form-control" required>
                <option value="">-- Pilih --</option>
                <option value="transfer">Transfer Bank</option>
                <option value="cash">Cash</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Nominal Pembayaran</label>
            <input type="number" name="amount" id="amount" class="form-control" value="{{ $booking->total_price }}" min="1" required>
        </div>
        <button type="submit" class="btn btn-primary">Bayar</button>
    </form>
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif
</div>
@endsection
