<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\FieldClosure;
use App\Exceptions\BookingException;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class BookingController extends Controller
{
    /**
     * Show booking form with calendar and slot selection
     * Consolidated view that replaces separate schedule and create-form pages
     */
    public function createForm(Request $request)
    {
        // Get field_id from query param or redirect to dashboard
        $fieldId = $request->query('field_id');
        if (!$fieldId) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('info', 'Silakan pilih lapangan terlebih dahulu');
        }

        $field = Field::findOrFail($fieldId);

        // Return consolidated view with calendar and form
        return view('tenant.booking.create', [
            'field' => $field,
        ]);
    }

    /**
     * Show confirmation form before saving
     */
    public function confirmForm(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'booking_date' => 'required|date',
            'selected_slots' => 'required',
        ]);

        $bookingDate = Carbon::createFromFormat('Y-m-d', $validated['booking_date']);
        $fieldId = $validated['field_id'];

        // Get field
        $field = Field::findOrFail($fieldId);

        // Parse and validate selected slots
        $slotResult = $this->parseAndValidateSlots(
            $validated['selected_slots'],
            $field,
            $bookingDate
        );

        if ($slotResult['error']) {
            return redirect()->back()->with('error', $slotResult['error']);
        }

        return view('tenant.booking.confirmation', [
            'field' => $field,
            'booking_date' => $bookingDate,
            'selected_slots' => $slotResult['slots'],
            'total_price' => $slotResult['total_price'],
        ]);
    }

    /**
     * Parse, normalize, and validate selected slots
     *
     * @param mixed $selectedSlotsInput
     * @param Field $field
     * @param Carbon $bookingDate
     * @return array
     */
    private function parseAndValidateSlots($selectedSlotsInput, $field, $bookingDate)
    {
        // Normalize input to array
        $selectedSlotsInput = $this->normalizeSlotInput($selectedSlotsInput);

        $selectedSlots = [];
        $totalPrice = 0;

        foreach ($selectedSlotsInput as $slotData) {
            $slotInfo = $this->processSlot($slotData, $field, $bookingDate);

            if ($slotInfo['error']) {
                return [
                    'error' => $slotInfo['error'],
                    'slots' => [],
                    'total_price' => 0,
                ];
            }

            $selectedSlots[] = $slotInfo['slot'];
            $totalPrice += $slotInfo['price'];
        }

        if (empty($selectedSlots)) {
            return [
                'error' => 'Pilih minimal satu slot pemesanan.',
                'slots' => [],
                'total_price' => 0,
            ];
        }

        return [
            'error' => null,
            'slots' => $selectedSlots,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Normalize slot input to array format
     *
     * @param mixed $input
     * @return array
     */
    private function normalizeSlotInput($input)
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            return $decoded ?: [$input];
        }

        return is_array($input) ? $input : [$input];
    }

    /**
     * Process and validate a single slot
     *
     * @param string $slotData
     * @param Field $field
     * @param Carbon $bookingDate
     * @return array
     */
    private function processSlot($slotData, $field, $bookingDate)
    {
        // slotData format: "HH:mm|HH:mm"
        $parts = explode('|', $slotData);
        $startTime = trim($parts[0] ?? null);
        $endTime = trim($parts[1] ?? null);

        if (!$startTime || !$endTime) {
            return [
                'error' => null, // Skip invalid slots silently
                'slot' => null,
                'price' => 0,
            ];
        }

        // Get day type
        $dayType = $this->getDayType($bookingDate);

        // Get price for this slot
        $price = $field->fieldPrices()
            ->where('day_type', $dayType)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->first();

        if (!$price) {
            return [
                'error' => 'Slot tidak valid atau harga tidak ditemukan.',
                'slot' => null,
                'price' => 0,
            ];
        }

        return [
            'error' => null,
            'slot' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $price->price,
            ],
            'price' => $price->price,
        ];
    }

    /**
     * Get day type from booking date
     *
     * @param Carbon $bookingDate
     * @return string
     */
    private function getDayType($bookingDate)
    {
        $dayName = strtolower($bookingDate->format('l'));
        $dayTypeMap = [
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday',
        ];

        return $dayTypeMap[$dayName] ?? 'monday';
    }

    /**
     * Save booking to database
     */
    public function store(Request $request)
    {
        // Validate all inputs
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'team_name' => 'required|string|max:50',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'required|email|max:50',
            'notes' => 'nullable|string|max:50',
            'selected_slots' => 'required|json',
        ]);

        $bookingDate = Carbon::createFromFormat('Y-m-d', $validated['booking_date']);
        $fieldId = $validated['field_id'];
        $userId = auth()->id() ?? 1; // Default user ID 1 for testing

        // Decode selected slots
        $selectedSlots = json_decode($validated['selected_slots'], true);
        if (!$selectedSlots || empty($selectedSlots)) {
            return redirect()->back()->with('error', 'Pilih minimal satu slot pemesanan.');
        }

        try {
            // Use transaction to ensure atomicity
            DB::transaction(function () use (
                $validated,
                $bookingDate,
                $fieldId,
                $userId,
                $selectedSlots
            ) {
                // Double-check slot availability (prevent double booking)
                foreach ($selectedSlots as $slotData) {
                    // Handle array structure from JSON
                    if (is_array($slotData)) {
                        $startTime = $slotData['start_time'];
                        $endTime = $slotData['end_time'];
                    } else {
                        [$startTime, $endTime] = explode('|', $slotData);
                    }

                    $isBooked = BookingDetail::where('play_date', $bookingDate->format('Y-m-d'))
                        ->where('start_play_time', $startTime)
                        ->where('end_play_time', $endTime)
                        ->whereIn('status', ['active', 'waiting'])
                        ->lockForUpdate()
                        ->exists();

                    if ($isBooked) {
                        throw new BookingException("Slot {$startTime} - {$endTime} sudah dipesan. Silakan pilih slot lain.");
                    }
                }

                // Create booking record
                $booking = new \App\Models\Booking();
                $booking->fk_user_id = $userId;
                $booking->fk_field_id = $fieldId;
                $booking->team_name = $validated['team_name'];
                $booking->customer_phone = $validated['customer_phone'];
                $booking->customer_email = $validated['customer_email'];
                $booking->notes = $validated['notes'] ?? '-';
                $booking->booking_date = $bookingDate->format('Y-m-d');
                $booking->save();

                // Create booking details for each slot
                $field = Field::with('fieldPrices')->find($fieldId);
                $dayName = strtolower($bookingDate->format('l'));
                $dayTypeMap = [
                    'monday' => 'monday',
                    'tuesday' => 'tuesday',
                    'wednesday' => 'wednesday',
                    'thursday' => 'thursday',
                    'friday' => 'friday',
                    'saturday' => 'saturday',
                    'sunday' => 'sunday',
                ];
                $dayType = $dayTypeMap[$dayName];

                foreach ($selectedSlots as $slotData) {
                    // Handle array structure from JSON
                    if (is_array($slotData)) {
                        $startTime = $slotData['start_time'];
                        $endTime = $slotData['end_time'];
                    } else {
                        [$startTime, $endTime] = explode('|', $slotData);
                    }

                    // Get price for this slot
                    $price = $field->fieldPrices()
                        ->where('day_type', $dayType)
                        ->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime)
                        ->first();

                    if (!$price) {
                        throw new BookingException("Harga tidak ditemukan untuk slot {$startTime} - {$endTime}");
                    }

                    $bookingDetail = new \App\Models\BookingDetail();
                    $bookingDetail->fk_booking_id = $booking->id;
                    $bookingDetail->start_play_time = $startTime;
                    $bookingDetail->end_play_time = $endTime;
                    $bookingDetail->play_date = $bookingDate->format('Y-m-d');
                    $bookingDetail->price = $price->price;
                    $bookingDetail->status = 'waiting'; // Status waiting until payment confirmed
                    $bookingDetail->save();
                }
            });

            // Success - redirect to payment page (payment team will provide)
            return redirect()->route('tenant.booking.success', ['booking_id' => \App\Models\Booking::latest('id')->first()->id])
                ->with('success', 'Pemesanan berhasil dibuat! Lanjutkan ke halaman pembayaran.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat pemesanan: ' . $e->getMessage());
        }
    }

    /**
     * Show success page after booking created
     */
    public function success($bookingId)
    {
        $booking = \App\Models\Booking::with(['details', 'field', 'user'])
            ->findOrFail($bookingId);

        // Ensure user can only see their own booking (for production)
        // $userId = auth()->id() ?? 1; // For now, allow all to view for testing
        // if ($booking->fk_user_id !== $userId) {
        //     abort(403, 'Unauthorized access');
        // }

        return view('tenant.booking.success', [
            'booking' => $booking,
        ]);
    }

    /**
     * Show user's booking dashboard
     */
    public function dashboard(Request $request)
    {
        $userId = auth()->id() ?? 1; // Default user ID 1 for testing
        
        // Get all fields
        $fields = Field::all();
        
        // Get selected field from query parameter
        $selectedFieldId = $request->query('field_id');
        $selectedField = null;
        $nearestBookings = collect();
        $userBookings = collect();
        
        if ($selectedFieldId) {
            $selectedField = Field::findOrFail($selectedFieldId);
            
            // Get nearest bookings for this field (upcoming, for all users)
            $today = Carbon::today();
            $nearestBookings = BookingDetail::with(['booking.field', 'booking.user'])
                ->whereHas('booking', function ($query) use ($selectedFieldId) {
                    $query->where('fk_field_id', $selectedFieldId);
                })
                ->whereDate('play_date', '>=', $today)
                ->where('status', '!=', 'cancelled')
                ->orderBy('play_date')
                ->orderBy('start_play_time')
                ->limit(5)
                ->get();
            
            // Get user's booking history for this field
            $userBookings = Booking::with(['details', 'field'])
                ->where('fk_user_id', $userId)
                ->where('fk_field_id', $selectedFieldId)
                ->orderBy('booking_date', 'desc')
                ->get();
        }

        return view('tenant.booking.dashboard.index', [
            'fields' => $fields,
            'selectedField' => $selectedField,
            'selectedFieldId' => $selectedFieldId,
            'nearestBookings' => $nearestBookings,
            'userBookings' => $userBookings,
        ]);
    }
}
