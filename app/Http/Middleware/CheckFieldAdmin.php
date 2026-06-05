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
            return $next($request);
        }

        $fieldId = $request->route('field_id') ?? $request->input('field_id') ?? $request->input('fk_field_id');

        if (!$fieldId && $request->route('detail_booking_id')) {
            $detailId = $request->route('detail_booking_id');

            $bookingDetail = BookingDetail::with('booking')->find($detailId);

            if ($bookingDetail && $bookingDetail->booking) {
                $fieldId = $bookingDetail->booking->fk_field_id;
            }
        }

        if (!$fieldId) {
            return $next($request);
        }

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
