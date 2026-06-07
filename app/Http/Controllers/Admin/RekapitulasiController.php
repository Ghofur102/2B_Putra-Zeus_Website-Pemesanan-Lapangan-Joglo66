<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class RekapitulasiController extends Controller
{
    /**
     * DEVELOPER : Huda
     * ROUTE     : GET /api/admin/rekap-harian
     * MIDDLEWARE: auth:sanctum, check.field.admin
     * PARAMETER : Request $request (query: 'tanggal' format Y-m-d)
     * OUTPUT    : JsonResponse ['success' => bool, 'message' => string, 'data' => array]
     */
    public function index(Request $request): JsonResponse
    {
        $status = 200;

        try {
            // 1. Validasi filter tanggal dari $request->query('tanggal'), pastikan format sesuai Y-m-d.
            $validator = Validator::make($request->query(), [
                'tanggal' => ['required', 'date_format:Y-m-d'],
            ]);

            // 2. Jika validasi gagal, kembalikan response error 422.
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $tanggal = $validator->validated()['tanggal'];
            $user    = $request->user();

            // 3. Tarik data dari entitas Payment berdasarkan filter tanggal yang dikirimkan.
            //    Join ke bookings untuk mendapat team_name dan fk_field_id.
            //    Jika user adalah worker, filter hanya untuk field yang dikelolanya.
            $query = Payment::with(['booking.field'])
                ->whereDate('paid_at', $tanggal)
                ->where('status', 'success');

            if ($user && $user->role === 'worker') {
                $fieldIds = DB::table('field_admins')
                    ->where('fk_user_id', $user->id)
                    ->pluck('fk_field_id');

                $query->whereHas('booking', fn ($q) =>
                    $q->whereIn('fk_field_id', $fieldIds)
                );
            }

            $payments = $query->get();

            // 5. Skenario error E1: data transaksi tidak ditemukan.
            if ($payments->isEmpty()) {
                throw new HttpException(404, 'Tidak ada transaksi pada tanggal ' . $tanggal . '.');
            }

            // 4. Kalkulasi total akumulasi nominal uang masuk
            //    dikelompokkan berdasarkan jenis transaksi.
            $totalDP          = $payments->where('payment_type', 'down payment')->sum('amount');
            $totalPelunasan   = $payments->where('payment_type', 'final payment')->sum('amount');
            $totalAtribut     = $payments->where('payment_type', 'attribute rental')->sum('amount');
            $totalDPHangus    = $payments->where('payment_type', 'dp hangus')->sum('amount');
            $grandTotal       = $totalDP + $totalPelunasan + $totalAtribut - $totalDPHangus;

            // Susun list transaksi untuk dikirim ke Flutter
            $transaksi = $payments->map(fn ($payment) => [
                'id'             => $payment->id,
                'reference_id'   => $payment->reference_id,
                'team_name'      => $payment->booking?->team_name ?? '-',
                'field_name'     => $payment->booking?->field?->name ?? '-',
                'payment_type'   => $payment->payment_type,
                'method'         => $payment->method,
                'amount'         => $payment->amount,
                'status'         => $payment->status,
                'paid_at'        => $payment->paid_at?->toIso8601String(),
            ])->values();

            // 6. Kembalikan response JSON sukses 200.
            $data = [
                'success' => true,
                'message' => 'Data rekap harian berhasil diambil.',
                'data'    => [
                    'tanggal'    => $tanggal,
                    'ringkasan'  => [
                        'total_dp'        => $totalDP,
                        'total_pelunasan'  => $totalPelunasan,
                        'total_atribut'   => $totalAtribut,
                        'total_dp_hangus' => $totalDPHangus,
                        'grand_total'     => $grandTotal,
                    ],
                    'transaksi'  => $transaksi,
                ],
            ];

        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data   = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data   = [
                'success' => false,
                'message' => 'Gagal mengambil data rekap harian.',
                'error'   => $e->getMessage(),
            ];
        }

        return response()->json($data, $status);
    }
}
