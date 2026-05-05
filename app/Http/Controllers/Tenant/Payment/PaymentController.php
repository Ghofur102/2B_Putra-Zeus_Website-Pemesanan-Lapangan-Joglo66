<?php

namespace App\Http\Controllers\Tenant\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Services\TripayService;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $bookingId = $request->query('booking_id');

        if ($bookingId) {
            $booking = Booking::with(['field', 'details'])
                ->findOrFail($bookingId);

            $detail  = $booking->details->first();
            $total   = $booking->details->sum('price');
            $tipe    = $request->query('tipe', 'dp');
            $metode  = $request->query('metode', 'qris');
            $nominal = $tipe === 'dp' ? $total / 2 : $total;

            $metodeList = [
                'qris'      => ['nama' => 'QRIS',       'icon' => '🧾'],
                'gopay'     => ['nama' => 'GoPay',      'icon' => '📱'],
                'shopeepay' => ['nama' => 'ShopeePay',  'icon' => '🛍️'],
                'linkaja'   => ['nama' => 'LinkAja',    'icon' => '🔗'],
                'bca'       => ['nama' => 'BCA',        'icon' => '🏦'],
                'bni'       => ['nama' => 'BNI',        'icon' => '🏦'],
                'bri'       => ['nama' => 'BRI',        'icon' => '🏦'],
                'mandiri'   => ['nama' => 'Mandiri',    'icon' => '🏦'],
            ];

            $bookingData = [
                'nama'     => $booking->user->name ?? '-',
                'lapangan' => $booking->field->name,
                'tanggal'  => $booking->booking_date,
                'jam'      => optional($detail)->start_play_time . ' – ' . optional($detail)->end_play_time,
                'durasi'   => $detail ? $this->hitungDurasi($detail->start_play_time, $detail->end_play_time) : '-',
                'total'    => $total,
            ];

            return view('tenant.payment.confirmation', [
                'booking'    => $bookingData,
                'bookingId'  => $bookingId,
                'detail'     => $detail,
                'total'      => $total,
                'metodeList' => $metodeList,
                'tipe'       => $tipe,
                'metode'     => $metode,
                'nominal'    => $nominal,
            ]);
        } else {
            // $userId = auth()->id();
            // if (!$userId) {
            //     abort(401, 'Please login to view payments.');
            // }
            $payments = Payment::with(['booking.field', 'booking.user'])->get(); // For testing, show all payments with relations

            return view('tenant.payment.index', compact('payments'));
        }
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'booking_id'        => 'required|exists:bookings,id',
            'booking_detail_id' => 'nullable|exists:booking_details,id',
            'tipe'              => 'required|in:dp,lunas',
            'metode'            => 'required|in:qris,gopay,shopeepay,linkaja,bca,bni,bri,mandiri',
            'total'             => 'required|numeric',
        ]);

        $booking     = Booking::with(['field', 'details', 'user'])->findOrFail($request->input('booking_id'));
        $tipe        = $request->input('tipe');
        $metode      = $request->input('metode');
        $total       = (int) $request->input('total');
        $nominal     = $tipe === 'dp' ? (int) round($total / 2) : $total;
        $paymentType = $tipe === 'dp' ? 'down payment' : 'final payment';
        $referenceId = 'TRX-' . strtoupper(uniqid());

        $tripayService  = new TripayService();
        $tripayResponse = $tripayService->createTransaction([
            'merchant_ref'   => $referenceId,
            'amount'         => $nominal,
            'method'         => $metode,
            'customer_name'  => $booking->user->name  ?? 'Pelanggan',
            'customer_email' => $booking->user->email ?? 'customer@example.com',
            'customer_phone' => $booking->user->phone ?? '',
            'order_items'    => [
                [
                    'name'     => 'Booking lapangan ' . $booking->field->name,
                    'price'    => $nominal,
                    'quantity' => 1,
                ],
            ],
            'description'  => $paymentType === 'down payment' ? 'Pembayaran DP booking' : 'Pembayaran pelunasan booking',
            'expired_time' => now()->addHours(6)->timestamp,
        ]);

        if (!$tripayResponse['success']) {
            return back()->withErrors(['metode' => $tripayResponse['message']]);
        }

        $paymentUrl = $tripayResponse['data']['checkout_url'] ?? $tripayResponse['data']['payment_url'] ?? null;

        $payment = Payment::create([
            'fk_booking_id'        => $booking->id,
            'fk_booking_detail_id' => $request->input('booking_detail_id'),
            'reference_id'         => $referenceId,
            'payment_url'          => $paymentUrl,
            'payment_type'         => $paymentType,
            'method'               => $metode,
            'amount'               => $nominal,
            'status'               => 'pending',
            'paid_at'              => null,
        ]);

        if (!$paymentUrl) {
            return back()->withErrors(['metode' => 'Tripay mengembalikan URL pembayaran kosong.']);
        }

        return redirect()->away($paymentUrl);
    }

    public function tripayReturn(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('merchant_ref');

        if (!$reference) {
            return redirect()->route('payment.index')->withErrors(['reference' => 'Reference pembayaran tidak ditemukan.']);
        }

        $payment = Payment::with(['booking.field', 'booking.user'])->where('reference_id', $reference)->first();

        if (!$payment) {
            return redirect()->route('payment.index')->withErrors(['reference' => 'Pembayaran tidak ditemukan.']);
        }

        // Set session transaksi untuk halaman status dan bukti
        session(['transaksi' => [
            'id' => $payment->reference_id,
            'statusBayar' => $payment->status === 'success' ? 'sukses' : ($payment->status === 'failed' ? 'gagal' : 'pending'),
            'totalBayar' => $payment->amount,
            'metodeBayar' => $payment->method,
            'tanggalBayar' => $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : null,
            'nama' => $payment->booking->user->name ?? '-',
            'lapangan' => $payment->booking->field->name,
            'tanggal' => $payment->booking->booking_date,
            'jam' => optional($payment->booking->details->first())->start_play_time . ' – ' . optional($payment->booking->details->first())->end_play_time,
            'durasi' => $payment->booking->details->first() ? $this->hitungDurasi($payment->booking->details->first()->start_play_time, $payment->booking->details->first()->end_play_time) : '-',
            'metode' => $payment->method,
            'tipe' => $payment->payment_type,
            'nominal' => $payment->amount,
        ]]);

        return redirect()->route('status.index');
    }

    public function dummyCheckout(Request $request)
    {
        $reference = $request->query('reference');
        $amount    = $request->query('amount');
        $method    = $request->query('method');

        return view('tenant.payment.dummy-checkout', [
            'reference' => $reference,
            'amount'    => $amount,
            'method'    => $method,
        ]);
    }

    public function dummySimulate(Request $request)
    {
        $reference = $request->input('reference') ?? $request->input('merchant_ref');
        $status    = $request->input('status');

        $payment = Payment::with(['booking.field', 'booking.user'])->where('reference_id', $reference)->first();

        if (!$payment) {
            return back()->withErrors(['reference' => 'Payment record tidak ditemukan.']);
        }

        $payment->status = match ($status) {
            'paid'   => 'success',
            'failed' => 'failed',
            default  => 'pending',
        };

        if ($payment->status === 'success' && !$payment->paid_at) {
            $payment->paid_at = now();
        }

        $payment->save();

        $statusBayar = match($payment->status) {
            'success' => 'sukses',
            'failed'  => 'gagal',
            default   => 'pending',
        };

        session(['transaksi' => [
            'id'          => $payment->reference_id,
            'statusBayar' => $statusBayar,
            'totalBayar'  => $payment->amount,
            'metodeBayar' => $payment->method,
            'tanggalBayar'=> $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : null,
            'nama'        => $payment->booking->user->name ?? '-',
            'lapangan'    => $payment->booking->field->name ?? '-',
            'tanggal'     => $payment->booking->booking_date ?? '-',
            'jam'         => optional($payment->booking->details->first())->start_play_time . ' – ' . optional($payment->booking->details->first())->end_play_time,
            'durasi'      => $payment->booking->details->first() ? $this->hitungDurasi($payment->booking->details->first()->start_play_time, $payment->booking->details->first()->end_play_time) : '-',
            'metode'      => $payment->method,
            'tipe'        => $payment->payment_type,
            'nominal'     => $payment->amount,
        ]]);

        return redirect()->route('status.index');
    }

    public function testStatus($reference)
    {
        $payment = Payment::with(['booking.field', 'booking.user'])->where('reference_id', $reference)->first();

        if (!$payment) {
            return redirect()->route('payment.index')->withErrors(['reference' => 'Pembayaran tidak ditemukan.']);
        }

        $statusBayar = match($payment->status) {
            'success' => 'sukses',
            'failed'  => 'gagal',
            default   => 'pending',
        };

        session(['transaksi' => [
            'id'          => $payment->reference_id,
            'statusBayar' => $statusBayar,
            'totalBayar'  => $payment->amount,
            'metodeBayar' => $payment->method,
            'tanggalBayar'=> $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : null,
            'nama'        => $payment->booking->user->name ?? '-',
            'lapangan'    => $payment->booking->field->name ?? '-',
            'tanggal'     => $payment->booking->booking_date ?? '-',
            'jam'         => optional($payment->booking->details->first())->start_play_time . ' – ' . optional($payment->booking->details->first())->end_play_time,
            'durasi'      => $payment->booking->details->first() ? $this->hitungDurasi($payment->booking->details->first()->start_play_time, $payment->booking->details->first()->end_play_time) : '-',
            'metode'      => $payment->method,
            'tipe'        => $payment->payment_type,
            'nominal'     => $payment->amount,
        ]]);

        return redirect()->route('status.index');
    }

    public function status()
    {
        $transaksi = session('transaksi');

        if (!$transaksi) {
            return redirect()->route('payment.index');
        }

        $statusConfig = [
            'sukses'  => ['icon' => '✅', 'bg' => '#d4edda', 'title' => 'Pembayaran Berhasil!',  'sub' => 'Transaksi kamu telah dikonfirmasi',  'badge' => 'Sukses',  'titleColor' => '#155724', 'valColor' => '#90EE90'],
            'gagal'   => ['icon' => '❌', 'bg' => '#f8d7da', 'title' => 'Pembayaran Gagal',       'sub' => 'Transaksi tidak dapat diproses',     'badge' => 'Gagal',   'titleColor' => '#721c24', 'valColor' => '#FF8080'],
            'pending' => ['icon' => '⏳', 'bg' => '#fff3cd', 'title' => 'Menunggu Verifikasi',    'sub' => 'Pembayaran sedang diverifikasi',     'badge' => 'Pending', 'titleColor' => '#856404', 'valColor' => '#FFD700'],
        ];

        $status = $statusConfig[$transaksi['statusBayar']] ?? $statusConfig['sukses'];

        return view('tenant.payment.status', [
            'transaksi'   => $transaksi,
            'idTransaksi' => $transaksi['id'],
            'status'      => $status,
        ]);
    }

    public function bukti()
    {
        $transaksi = session('transaksi');

        if (!$transaksi) {
            return redirect()->route('payment.index');
        }

        $badgeStyle = match (strtolower($transaksi['statusBayar'])) {
            'sukses'  => 'background:#d4edda; color:#155724;',
            'gagal'   => 'background:#f8d7da; color:#721c24;',
            'pending' => 'background:#fff3cd; color:#856404;',
            default   => 'background:#d4edda; color:#155724;',
        };

        return view('tenant.payment.receipt', [
            'transaksi'    => $transaksi,
            'tanggalBayar' => now()->translatedFormat('d F Y'),
            'badgeStyle'   => $badgeStyle,
        ]);
    }

    private function hitungDurasi(string $start, string $end): string
    {
        $startTime = \Carbon\Carbon::createFromTimeString($start);
        $endTime   = \Carbon\Carbon::createFromTimeString($end);
        return $startTime->diffInHours($endTime) . ' Jam';
    }
}
    