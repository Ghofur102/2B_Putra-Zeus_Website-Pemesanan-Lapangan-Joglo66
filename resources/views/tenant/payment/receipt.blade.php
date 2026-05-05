<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bukti Pembayaran</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #EFEFEF; min-height: 100vh; display: flex; justify-content: center; padding: 20px; }
.phone { width: 360px; background: #F2F2F2; border-radius: 24px; overflow: hidden; border: 1px solid #ccc; display: flex; flex-direction: column; }
.step-bar { background: #406093; padding: 6px 16px 8px; display: flex; gap: 4px; }
.step-dot { height: 3px; border-radius: 2px; flex: 1; }
.step-dot.active { background: white; }
.receipt-header { background: #406093; padding: 20px 16px 18px; color: white; text-align: center; }
.rh-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.rh-id { font-size: 12px; opacity: 0.8; margin-bottom: 10px; }
.badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.content { padding: 16px; overflow-y: auto; }
.section-title { font-size: 11px; font-weight: 700; color: #406093; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; margin-top: 14px; }
.section-title:first-child { margin-top: 0; }
.card-white { background: white; border-radius: 12px; padding: 14px; border: 0.5px solid #ddd; }
.r-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
.r-row:last-child { margin-bottom: 0; }
.rk { font-size: 12px; color: #666; }
.rv { font-size: 12px; font-weight: 600; color: #222; text-align: right; max-width: 55%; }
.divider { height: 0.5px; background: #eee; margin: 8px 0; }
.total-row { display: flex; justify-content: space-between; align-items: center; background: #EEF3FA; border-radius: 8px; padding: 8px 10px; margin-top: 6px; }
.total-lbl { font-size: 13px; font-weight: 700; color: #222; }
.total-val { font-size: 15px; font-weight: 700; color: #406093; }
.btn-row { display: flex; gap: 8px; margin-top: 14px; padding-bottom: 16px; }
.btn-primary { background: #406093; color: white; border: none; border-radius: 12px; padding: 13px; flex: 1; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; display: block; }
.btn-secondary { background: white; color: #406093; border: 1.5px solid #406093; border-radius: 12px; padding: 13px; flex: 1; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; display: block; }
.btn-primary:hover { opacity: 0.9; }
.btn-secondary:hover { background: #EEF3FA; }
@media print {
  body { background: white; padding: 0; }
  .phone { width: 100%; border: none; border-radius: 0; }
  .btn-row { display: none; }
}
</style>
</head>
<body>
<div class="phone">
  <div class="step-bar">
    <div class="step-dot active"></div>
    <div class="step-dot active"></div>
    <div class="step-dot active"></div>
  </div>

  {{-- Header Bukti --}}
  <div class="receipt-header">
    <div class="rh-title">Bukti Pembayaran</div>
    <div class="rh-id">ID: {{ $transaksi['id'] }}</div>
    <span class="badge" style="{{ $badgeStyle }}">{{ $transaksi['statusBayar'] }}</span>
  </div>

  <div class="content">

    {{-- ID Transaksi --}}
    <div class="section-title">ID Transaksi</div>
    <div class="card-white">
      <div class="r-row"><span class="rk">ID Transaksi</span><span class="rv">{{ $transaksi['id'] }}</span></div>
      <div class="r-row"><span class="rk">Referensi ID</span><span class="rv">{{ $transaksi['id'] }}</span></div>
      <div class="r-row"><span class="rk">Tanggal Bayar</span><span class="rv">{{ $tanggalBayar }}</span></div>
    </div>

    {{-- Detail Booking --}}
    <div class="section-title">Detail Booking</div>
    <div class="card-white">
      <div class="r-row"><span class="rk">Nama</span><span class="rv">{{ $transaksi['nama'] }}</span></div>
      <div class="r-row"><span class="rk">Lapangan</span><span class="rv">{{ $transaksi['lapangan'] }}</span></div>
      <div class="r-row"><span class="rk">Tanggal</span><span class="rv">{{ $transaksi['tanggal'] }}</span></div>
      <div class="r-row"><span class="rk">Waktu</span><span class="rv">{{ $transaksi['jam'] }}</span></div>
      <div class="r-row"><span class="rk">Durasi</span><span class="rv">{{ $transaksi['durasi'] }}</span></div>
    </div>

    {{-- Nominal --}}
    <div class="section-title">Nominal</div>
    <div class="card-white">
      <div class="r-row"><span class="rk">Jenis Pembayaran</span><span class="rv">{{ $transaksi['tipe'] }}</span></div>
      <div class="r-row"><span class="rk">Metode</span><span class="rv">{{ $transaksi['metode'] }}</span></div>
      <div class="r-row">
        <span class="rk">Status</span>
        <span class="rv">
          <span class="badge" style="{{ $badgeStyle }}">{{ $transaksi['statusBayar'] }}</span>
        </span>
      </div>
      <div class="divider"></div>
      <div class="total-row">
        <span class="total-lbl">Total Dibayar</span>
        <span class="total-val">Rp {{ number_format($transaksi['nominal'], 0, ',', '.') }}</span>
      </div>
    </div>

    {{-- Tombol --}}
    <div class="btn-row">
      <a href="{{ route('payment.index') }}" class="btn-secondary">Kembali ke Beranda</a>
      <a href="#" class="btn-primary" onclick="window.print(); return false;">Unduh Bukti</a>
    </div>

  </div>
</div>
</body>
</html>