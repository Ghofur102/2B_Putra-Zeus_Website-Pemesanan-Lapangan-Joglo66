<?php

namespace App\Http\Controllers;

use App\Enums\StatusBookingEnum;
use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BookingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fieldsId = $request->collect('details')->pluck('fk_field_id')->toArray();
        Gate::authorize('create', [Booking::class, $fieldsId]);

        $request->validate([
            'details' => ['required', 'array', 'min:1'],
            'details.*.fk_field_id' => ['required', 'exists:fields,id'],
            'details.*.play_date' => ['required', 'date'],
            'details.*.start_play_time' => ['required', Rule::date()->format('H:i')],
            'details.*.end_play_time' => ['required', Rule::date()->format('H:i'),
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $start_time = $request->input('details.{' + $index + '}.start_play_time');

                    if ($start_time && $value <= $start_time) {
                        $fail('End play time is not valid, must bigger than start play time!');
                    }
                },
            ],
            'details.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $user = Auth::user();
        DB::beginTransaction();

        try {
            $booking = Booking::create([
                'fk_user_id' => $user->id,
                'booking_date' => now()->toDateString(),
                'status_booking' => StatusBookingEnum::Waiting,
            ]);

            foreach ($request->details as $detail) {

                if (BookingDetail::isBookingDetailConflict($detail)) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message_error' => 'Jadwal bentrok pada salah satu lapangan',
                    ], 400);
                }

                BookingDetail::create([
                    'fk_booking_id' => $booking->id,
                    'fk_field_id' => $detail['fk_field_id'],
                    'start_play_time' => $detail['start_play_time'],
                    'end_play_time' => $detail['end_play_time'],
                    'play_date' => $detail['play_date'],
                    'price' => $detail['price'],
                    'status' => 'waiting',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $booking->load('details'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {}
}
