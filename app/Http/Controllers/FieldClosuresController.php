<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use App\Models\BookingDetail;
use App\Models\FieldClosure;

class FieldClosuresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, int $fk_field_id)
    {
        Gate::authorize('viewAny', [FieldClosure::class, $fk_field_id]);
        $dataFieldClosures = FieldClosure::where('fk_field_id', $fk_field_id)->get();
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data_field_closures' => $dataFieldClosures,
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'fk_field_id' => ['required', 'integer', 'exists:fields, id'],
            'field_closure_start_time' => ['required', Rule::date()->format('Y-m-d H:i:s')->afterOrEqual(today())],
            'field_closure_end_time' => ['required', Rule::date()->format('Y-m-d H:i:s')->afterOrEqual(today()), 'after:field_closure_start_time'],
            'reason' => ['required', 'max:300']
        ]);

        Gate::authorize('create', [FieldClosure::class, $validatedData['fk_field_id']]);

        $addDateFieldClosure = FieldClosure::create([
            'fk_field_id' => $validatedData['fk_field_id'],
            'field_closure_start_time' => $validatedData['field_closure_start_time'],
            'field_closure_end_time' => $validatedData['field_closure_end_time'],
            'reason' => $validatedData['reason'],
        ]);

        BookingDetail::where('fk_field_id', $validatedData['fk_field_id'])
            /** LOGIKA RENTANG WAKTU
             * Booking hanya valid jika:
             * - Waktu mulai main < waktu buka kembali
             * - Waktu selesai main > waktu tutup mendadak
             * Contoh: Tutup 08:00–11:00, maka waktu main 09:00–10:00 valid untuk di ubah statusnya menjadi waktu tutup mendadak lapangan.
             */
            ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                $validatedData['field_closure_end_time'],
                $validatedData['field_closure_start_time']
            ])
            ->update(['status', 'field closure']);

        $affectedBookings = BookingDetail::where('fk_field_id', $validatedData['fk_field_id'])
            ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                $validatedData['field_closure_end_time'],
                $validatedData['field_closure_start_time']
            ])
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data_field_closure' => $addDateFieldClosure,
                'affected_bookings' => $affectedBookings
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $fk_field_id, string $id)
    {
        Gate::authorize('view', [FieldClosure::class, $fk_field_id]);
        $detailFieldClosure = FieldClosure::findOrFail($id);
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'detail_field_closure' => $detailFieldClosure,
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
