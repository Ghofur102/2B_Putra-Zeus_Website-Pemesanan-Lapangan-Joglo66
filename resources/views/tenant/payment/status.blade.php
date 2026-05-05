<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Status Pembayaran</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #EFEFEF; min-height: 100vh; display: flex; justify-content: center; padding: 20px; }
.phone { width: 360px; background: #F2F2F2; border-radius: 24px; overflow: hidden; border: 1px solid #ccc; display: flex; flex-direction: column; }
.header { background: #406093; padding: 14px 16px; color: white; display: flex; align-items: center; gap: 12px; }
.header h2 { font-size: 15px; font-weight: 600; }
.step-bar { background: #406093; padding: 6px 16px 8px; display: flex; gap: 4px; }
.step-dot { height: 3px; border-radius: 2px; flex: 1; }
.step-dot.active { background: white; }
.step-dot.inactive { background: rgba(255,255,255,0.3); }
.content { padding: 16px; flex: 1; }
.status-icon { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 20px auto 12px; font-size: 36px; }
.status-title { text-align: center; font-size: 17px; font-weight: 700; margin-bottom: 4px; }
.status-sub { text-align: center; font-size: 13px; color: #666; margin-bottom: 20px; }
.section-title { font-size: 11px; font-weight: 700; color: #406093; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; margin-top: 14px; }
.card-dark { background: #4F4E4E; border-radius: 12px; padding: 14px; margin-bottom: 4px; color: white; }
.row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
.row:last-child { margin-bottom: 0; }
.lbl { font-size: 12px; color: rgba(255,255,255,0.7); }
.val { font-size: 13px; font-weight: 600; color: white; }
.divider { height: 0.5px; background: rgba(255,255,255,0.2); margin: 8px 0; }
.total-row { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 10px; margin-top: 6px; }
.total-lbl { font-size: 13px; color: rgba(255,255,255,0.9); }
.total-val { font-size: 16px; font-weight: 700; color: #FFD700; }
.btn-row { display: flex; gap: 8px; margin-top: 14px; padding-bottom: 16px; }
.btn-primary { background: #406093; color: white; border: none; border-radius: 12px; padding: 13px; flex: 1; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; display: block; }
.btn-secondary { background: white; color: #406093; border: 1.5px solid #406093; border-radius: 12px; padding: 13px; flex: 1; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; display: block; }
.btn-primary:hover { opacity: 0.9; }
.btn-secondary:hover { background: #EEF3FA; }
</style>
</head>
<body>
<div class="phone">
  <div class="step-bar">
    <div class="step-dot active"></div>
    <div class="step-dot active"></div>
    <div class="step-dot inactive"></div>
  </div>
  <div class="header">
    <h2>Status Pembayaran</h2>
  </div>

  <div class="content">
    {{-- Ikon & Teks Status --}}
    <div class="status-icon" style="background: {{ $status['bg'] }};">{{ $status['icon'] }}</div>
    <div class="status-title" style="color: {{ $status['titleColor'] }};">{{ $status['title'] }}</div>
    <div class="status-sub">{{ $status['sub'] }}</div>

    {{-- Detail Pesanan --}}
    <div class="section-title">Detail Pesanan</div>
    <div class="card-dark">
      <div class="row"><span class="lbl">ID Transaksi</span><span class="val">{{ $idTransaksi }}</span></div>
      <div class="row"><span class="lbl">Lapangan</span><span class="val">{{ $transaksi['lapangan'] }}</span></div>
      <div class="row"><span class="lbl">Tanggal</span><span class="val">{{ $transaksi['tanggal'] }}</span></div>
      <div class="row"><span class="lbl">Waktu</span><span class="val">{{ $transaksi['jam'] }}</span></div>
    </div>

    {{-- Detail Pembayaran --}}
    <div class="section-title">Detail Pembayaran</div>
    <div class="card-dark">
      <div class="row"><span class="lbl">Metode</span><span class="val">{{ $transaksi['metode'] }}</span></div>
      <div class="row"><span class="lbl">Jenis</span><span class="val">{{ $transaksi['tipe'] }}</span></div>
      <div class="row">
        <span class="lbl">Status</span>
        <span class="val" style="color: {{ $status['valColor'] }};">{{ $status['badge'] }}</span>
      </div>
      <div class="divider"></div>
      <div class="total-row">
        <span class="total-lbl">Nominal Dibayar</span>
        <span class="total-val">Rp {{ number_format($transaksi['nominal'], 0, ',', '.') }}</span>
      </div>
    </div>

    {{-- Tombol Aksi --}}
    <div class="btn-row">
      @if ($transaksi['statusBayar'] === 'gagal' || $transaksi['statusBayar'] === 'pending')
        <a href="{{ route('payment.index') }}" class="btn-secondary">Coba Ulang</a>
      @else
        <a href="{{ route('payment.index') }}" class="btn-secondary">Kembali</a>
      @endif
      <a href="{{ route('bukti.index') }}" class="btn-primary">Lihat Bukti</a>
    </div>
  </div>
</div>
</body>
</html>