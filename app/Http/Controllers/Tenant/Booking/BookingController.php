<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Services\DuitkuService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\FieldPrice;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;

class BookingController extends Controller
{
    public function createForm(Request $request)
    {
        $fieldId = $request->query('field_id');
        if (!$fieldId) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('info', 'Silakan pilih lapangan terlebih dahulu');
        }

        $field = Field::findOrFail($fieldId);

        return view('tenant.booking.create', [
            'field' => $field,
        ]);
    }

    public function confirmForm(Request $request)
    {
        $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'selected_slots' => 'required|string',
        ]);

        $field = Field::findOrFail($request->field_id);

        $selectedSlotsRaw = json_decode($request->selected_slots, true);

        $totalPrice = 0;
        $groupedSlots = [];

        foreach ($selectedSlotsRaw as $item) {
            $playDate = $item['date'];
            $startTime = Carbon::parse($item['jam'])->format('H:i:s');
            $endTime = Carbon::parse($item['jam_akhir'])->format('H:i:s');
            $dayType = strtolower(Carbon::parse($playDate)->format('l'));

            $fieldPrice = FieldPrice::where('fk_field_id', $field->id)
                ->where('day_type', $dayType)
                ->whereTime('start_time', '<=', $startTime)
                ->whereTime('end_time', '>=', $endTime)
                ->first();

            $price = $fieldPrice ? $fieldPrice->price : 0;
            $totalPrice += $price;

            if (!isset($groupedSlots[$playDate])) {
                $groupedSlots[$playDate] = [];
            }
            $groupedSlots[$playDate][] = [
                'jam' => $item['jam'],
                'jam_akhir' => $item['jam_akhir'],
                'harga' => $price
            ];
        }

        ksort($groupedSlots);

        return view('tenant.booking.confirmation', compact(
            'field',
            'groupedSlots',
            'totalPrice'
        ));
    }

    public function store(Request $request, DuitkuService $duitkuService)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'team_name' => 'required|string|max:50',
            'phone_number' => 'required|string|max:50',
            'customer_email' => 'required|email|max:50',
            'notes' => 'nullable|string',
            'booking_data' => 'required|json',
            'payment_type' => 'required|in:down payment,final payment',
        ]);

        $fieldId = $validated['field_id'];
        $userId = \Illuminate\Support\Facades\Auth::id() ?? 1;
        $groupedSlots = json_decode($validated['booking_data'], true);

        if (empty($groupedSlots)) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('error', 'Pilih minimal satu slot pemesanan.');
        }

        DB::connection('mysql_joglo66_app')->beginTransaction();

        try {
            // 2. Cek ketersediaan slot (Mencegah bentrok jadwal)
            foreach ($groupedSlots as $playDate => $slots) {
                foreach ($slots as $slot) {
                    $isBooked = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
                            $query->where('fk_field_id', $fieldId);
                        })
                        ->where('play_date', $playDate)
                        ->where('start_play_time', $slot['jam'])
                        ->where('end_play_time', $slot['jam_akhir'])
                        ->whereIn('status', ['active', 'waiting'])
                        ->lockForUpdate()
                        ->exists();

                    if ($isBooked) {
                        throw new \Exception("Slot {$slot['jam']} - {$slot['jam_akhir']} pada {$playDate} sudah dipesan.");
                    }
                }
            }

            $booking = new Booking();
            $booking->fk_user_id = $userId;
            $booking->fk_field_id = $fieldId;
            $booking->team_name = $validated['team_name'];
            $booking->customer_phone = $validated['phone_number'];
            $booking->customer_email = $validated['customer_email'];
            $booking->notes = $validated['notes'] ?? '-';
            $booking->booking_date = now()->format('Y-m-d');
            $booking->save();

            $totalPrice = 0;

            // 4. Simpan Detail Jadwal
            foreach ($groupedSlots as $playDate => $slots) {
                foreach ($slots as $slot) {
                    $totalPrice += $slot['harga'];

                    $bookingDetail = new BookingDetail();
                    $bookingDetail->fk_booking_id = $booking->id;
                    $bookingDetail->start_play_time = $slot['jam'];
                    $bookingDetail->end_play_time = $slot['jam_akhir'];
                    $bookingDetail->play_date = $playDate;
                    $bookingDetail->price = $slot['harga'];
                    $bookingDetail->status = 'waiting';
                    $bookingDetail->save();
                }
            }

            $amountToPay = $request->payment_type === 'down payment' ? ($totalPrice / 2) : $totalPrice;

            $duitkuResponse = $duitkuService->createInvoice($booking, $amountToPay);

            $payment = new Payment();
            $payment->fk_booking_id = $booking->id;
            $payment->reference_id = $duitkuResponse->reference;
            $payment->payment_url = $duitkuResponse->paymentUrl ?? '-';
            $payment->payment_type = $request->payment_type;
            $payment->method = 'transfer';
            $payment->amount = $amountToPay;
            $payment->status = 'pending';
            $payment->save();

            DB::connection('mysql_joglo66_app')->commit();

            return view('tenant.booking.checkout', [
                'booking' => $booking,
                'reference' => $duitkuResponse->reference,
                'amountToPay' => $amountToPay
            ]);

        } catch (\Exception $e) {
            DB::connection('mysql_joglo66_app')->rollBack();

            return redirect()->route('tenant.booking.dashboard')
                ->with('error', 'Transaksi gagal: ' . $e->getMessage() . ' Silakan ulangi.');
        }
    }

    public function success(int $bookingId)
    {
        $booking = \App\Models\Booking::with(['details', 'field', 'user'])
            ->findOrFail($bookingId);

        return view('tenant.booking.success', [
            'booking' => $booking,
        ]);
    }
}
