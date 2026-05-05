@extends('layouts.app')

@section('title', 'Simulasi Pembayaran Tripay')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">🔄 Simulasi Pembayaran</h1>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <p class="text-sm text-blue-700">
                Mode Dummy Aktif - Transaksi tidak akan dikirim ke Tripay.
            </p>
        </div>

        <div class="space-y-4">
            <div class="border rounded p-4">
                <h3 class="font-semibold text-gray-700">Detail Pembayaran</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Reference:</dt>
                        <dd class="font-mono text-gray-900">{{ $reference }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Metode:</dt>
                        <dd class="font-mono text-gray-900">{{ strtoupper($method) }}</dd>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <dt class="text-gray-600 font-semibold">Total:</dt>
                        <dd class="font-bold text-lg text-green-600">Rp {{ number_format($amount, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="border rounded p-4 bg-gray-50">
                <h3 class="font-semibold text-gray-700 mb-3">Simulasikan Status</h3>
                <div class="grid grid-cols-1 gap-2">
                    <form method="POST" action="{{ route('payment.dummy.simulate') }}" class="contents">
                        @csrf
                        <input type="hidden" name="reference" value="{{ $reference }}">
                        <input type="hidden" name="merchant_ref" value="{{ $reference }}">
                        <input type="hidden" name="amount" value="{{ $amount }}">
                        <input type="hidden" name="method" value="{{ $method }}">

                        <button type="submit" name="status" value="paid" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded transition">
                            ✅ Berhasil Bayar
                        </button>
                        <button type="submit" name="status" value="pending" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded transition">
                            ⏳ Menunggu (Pending)
                        </button>
                        <button type="submit" name="status" value="failed" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition">
                            ❌ Gagal Bayar
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mt-6">
                <p class="text-xs text-yellow-700">
                    <strong>Catatan:</strong> Setelah mengklik tombol, sistem akan mengirim callback dummy dan memperbarui status pembayaran di database.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    body { background: #f5f5f5; }
</style>
@endsection
