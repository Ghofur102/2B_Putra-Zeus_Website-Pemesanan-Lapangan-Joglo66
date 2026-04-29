<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\Models\BookingDetail;

class CheckFieldAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Akses ditolak. Anda bukan admin.'], 403);
        }

        // 1. Cari field_id dari parameter langsung atau request body
        $fieldId = $request->route('field_id') ?? $request->input('field_id') ?? $request->input('fk_field_id');

        // 2. JIKA TIDAK KETEMU, cek apakah URL membawa ID Booking Detail
        // (Berlaku untuk rute: detail-booking/{detail_booking_id}, reschedule-booking, dll)
        if (!$fieldId && $request->route('detail_booking_id')) {
            $detailId = $request->route('detail_booking_id');

            // Cari data booking-nya untuk mengetahui ID lapangannya
            $bookingDetail = BookingDetail::with('booking')->find($detailId);

            if ($bookingDetail && $bookingDetail->booking) {
                $fieldId = $bookingDetail->booking->fk_field_id;
            }
        }

        // Jika rute memang sama sekali tidak butuh pengecekan lapangan, biarkan lewat
        if (!$fieldId) {
            return $next($request);
        }

        // 3. Cek Otorisasi: Apakah worker ini berhak mengelola lapangan tersebut?
        $isAuthorized = DB::table('field_admins')
            ->where('fk_user_id', $user->id)
            ->where('fk_field_id', $fieldId)
            ->exists();

        if (!$isAuthorized) {
            return response()->json([
                'status' => 'error',
                'message' => "Forbidden. Anda tidak ditugaskan untuk mengelola lapangan ini."
            ], 403);
        }

        return $next($request);
    }
}
