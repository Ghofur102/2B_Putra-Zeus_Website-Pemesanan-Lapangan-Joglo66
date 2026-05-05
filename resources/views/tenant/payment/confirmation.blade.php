<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Halaman Pembayaran</title>
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
.section-title { font-size: 11px; font-weight: 700; color: #406093; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; margin-top: 14px; }
.section-title:first-child { margin-top: 0; }
.card-dark { background: #4F4E4E; border-radius: 12px; padding: 14px; margin-bottom: 4px; color: white; }
.row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
.row:last-child { margin-bottom: 0; }
.lbl { font-size: 12px; color: rgba(255,255,255,0.7); }
.val { font-size: 13px; font-weight: 600; color: white; }
.divider { height: 0.5px; background: rgba(255,255,255,0.2); margin: 8px 0; }
.total-row { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 10px; margin-top: 6px; }
.total-lbl { font-size: 13px; color: rgba(255,255,255,0.9); }
.total-val { font-size: 16px; font-weight: 700; color: #FFD700; }
.opt-label { display: flex; align-items: center; gap: 10px; background: white; border: 1.5px solid #ddd; border-radius: 10px; padding: 12px 14px; margin-bottom: 8px; cursor: pointer; }
.opt-label.checked { border-color: #406093; background: #EEF3FA; }
.radio-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #ccc; flex-shrink: 0; }
.opt-label.checked .radio-dot { border-color: #406093; background: radial-gradient(circle, #406093 45%, white 45%); }
.opt-info { flex: 1; }
.opt-title { font-size: 13px; font-weight: 600; color: #222; }
.opt-sub { font-size: 11px; color: #888; }
.opt-amount { font-size: 14px; font-weight: 700; color: #406093; }
.method-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
.method-label { display: flex; flex-direction: column; align-items: center; background: white; border: 1.5px solid #ddd; border-radius: 10px; padding: 10px; cursor: pointer; }
.method-label.checked { border-color: #406093; background: #EEF3FA; }
.method-icon { font-size: 20px; margin-bottom: 4px; }
.method-name { font-size: 11px; font-weight: 600; color: #333; }
input[type="radio"] { display: none; }
.timer-bar { background: #4F4E4E; border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.timer-lbl { font-size: 12px; color: rgba(255,255,255,0.8); margin-bottom: 2px; }
.timer-val { font-size: 22px; font-weight: 700; color: #FFD700; font-family: 'Courier New', monospace; }
.btn-primary { background: #406093; color: white; border: none; border-radius: 12px; padding: 14px; width: 100%; font-size: 15px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { opacity: 0.9; }
</style>
</head>
<body>
<div class="phone">
  <div class="step-bar">
    <div class="step-dot active"></div>
    <div class="step-dot inactive"></div>
    <div class="step-dot inactive"></div>
  </div>
  <div class="header">
    <h2>Halaman Pembayaran</h2>
  </div>

  <div class="content">
    {{-- Form dikirim ke route 'payment.store' via POST --}}
    <form method="POST" action="{{ route('payment.store') }}">
      @csrf

      {{-- Ringkasan Tagihan --}}
      <div class="section-title">Ringkasan Tagihan</div>
      <div class="card-dark">
        <div class="row"><span class="lbl">Nama Pemesan</span><span class="val">{{ $booking['nama'] }}</span></div>
        <div class="row"><span class="lbl">Lapangan</span><span class="val">{{ $booking['lapangan'] }}</span></div>
        <div class="row"><span class="lbl">Tanggal</span><span class="val">{{ $booking['tanggal'] }}</span></div>
        <div class="row"><span class="lbl">Jam</span><span class="val">{{ $booking['jam'] }}</span></div>
        <div class="row"><span class="lbl">Durasi</span><span class="val">{{ $booking['durasi'] }}</span></div>
        <div class="divider"></div>
        <div class="total-row">
          <span class="total-lbl">Total Harga</span>
          <span class="total-val">Rp {{ number_format($booking['total'], 0, ',', '.') }}</span>
        </div>
      </div>

      {{-- Pilih Tipe Pembayaran --}}
      <div class="section-title">Pilih Pembayaran</div>
      <label class="opt-label {{ $tipe === 'dp' ? 'checked' : '' }}">
        <input type="radio" name="tipe" value="dp" {{ $tipe === 'dp' ? 'checked' : '' }}>
        <div class="radio-dot"></div>
        <div class="opt-info">
          <div class="opt-title">DP 50%</div>
          <div class="opt-sub">Bayar sebagian sekarang</div>
        </div>
        <span class="opt-amount">Rp {{ number_format($booking['total'] / 2, 0, ',', '.') }}</span>
      </label>
      <label class="opt-label {{ $tipe === 'lunas' ? 'checked' : '' }}">
        <input type="radio" name="tipe" value="lunas" {{ $tipe === 'lunas' ? 'checked' : '' }}>
        <div class="radio-dot"></div>
        <div class="opt-info">
          <div class="opt-title">Lunas</div>
          <div class="opt-sub">Bayar penuh sekarang</div>
        </div>
        <span class="opt-amount">Rp {{ number_format($booking['total'], 0, ',', '.') }}</span>
      </label>

      {{-- Metode Bayar --}}
      <div class="section-title">Cara Bayar</div>
      <div class="method-grid">
        @foreach ($metodeList as $key => $m)
        <label class="method-label {{ $metode === $key ? 'checked' : '' }}">
          <input type="radio" name="metode" value="{{ $key }}" {{ $metode === $key ? 'checked' : '' }}>
          <div class="method-icon">{{ $m['icon'] }}</div>
          <div class="method-name">{{ $m['nama'] }}</div>
        </label>
        @endforeach
      </div>

      {{-- Timer --}}
      <div class="timer-bar">
        <div>
          <div class="timer-lbl">Batas waktu pembayaran</div>
          <div class="timer-val" id="countdown">30:00</div>
        </div>
        <div style="font-size:24px;">⏱</div>
      </div>

      {{-- Hidden fields --}}
      <input type="hidden" name="booking_id" value="{{ $bookingId }}">
      <input type="hidden" name="booking_detail_id" value="{{ $detail ? $detail->id : '' }}">
      <input type="hidden" name="nama"     value="{{ $booking['nama'] }}">
      <input type="hidden" name="lapangan" value="{{ $booking['lapangan'] }}">
      <input type="hidden" name="tanggal"  value="{{ $booking['tanggal'] }}">
      <input type="hidden" name="jam"      value="{{ $booking['jam'] }}">
      <input type="hidden" name="durasi"   value="{{ $booking['durasi'] }}">
      <input type="hidden" name="total"    value="{{ $booking['total'] }}">
      <input type="hidden" name="nominal"  value="{{ $nominal }}">

      <button type="submit" class="btn-primary">Bayar Sekarang</button>
    </form>
  </div>
</div>

<script>
let sisa = 30 * 60;
const el = document.getElementById('countdown');
setInterval(function () {
  if (sisa <= 0) { el.textContent = '00:00'; return; }
  sisa--;
  let m = Math.floor(sisa / 60);
  let s = sisa % 60;
  el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}, 1000);

document.querySelectorAll('input[type="radio"]').forEach(r => {
  r.addEventListener('change', function () {
    document.querySelectorAll('.opt-label').forEach(l => l.classList.remove('checked'));
    document.querySelectorAll('.method-label').forEach(l => l.classList.remove('checked'));
    if (this.name === 'tipe')   this.closest('.opt-label').classList.add('checked');
    if (this.name === 'metode') this.closest('.method-label').classList.add('checked');
  });
});
</script>
</body>
</html>