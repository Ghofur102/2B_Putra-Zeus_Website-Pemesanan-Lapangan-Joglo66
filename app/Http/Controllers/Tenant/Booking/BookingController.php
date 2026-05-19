<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Field;
use App\Models\FieldPrice;
use App\Models\Payment;
use App\Services\DuitkuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BookingController extends Controller
{
    const PAYMENT_DP = 'down payment';
    const PAYMENT_FINAL = 'final payment';
    const PAYMENT_RESCHEDULE_FEE = 'reschedule fee';
    const PAYMENT_REFUND = 'refund';
    const STATUS_REFUND_DEPOSIT = 'deposit required';
    const STATUS_REFUND_REFUND = 'refund required';
    const STATUS_REFUND_NONE = 'none';
    const STATUS_REFUND_REFUNDABLE = 'refundable';
    const STATUS_REFUND_NON_REFUNDABLE = 'non-refundable';

    public function createForm(Request $request)
    {
        $fieldId = $request->query('field_id');
        if (! $fieldId) {
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

            if (! isset($groupedSlots[$playDate])) {
                $groupedSlots[$playDate] = [];
            }
            $groupedSlots[$playDate][] = [
                'jam' => $item['jam'],
                'jam_akhir' => $item['jam_akhir'],
                'harga' => $price,
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
        // 1. Hapus validasi phone_number dan customer_email (Kita tidak percaya input front-end)
        $validated = $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'team_name' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'booking_data' => 'required|json',
            'payment_type' => 'required|in:'.self::PAYMENT_DP.','.self::PAYMENT_FINAL,
        ]);

        $fieldId = $validated['field_id'];

        // 2. Ambil data User dari sesi yang otentik di Backend
        $user = Auth::user();
        $userId = $user ? $user->id : 1;

        $groupedSlots = json_decode($validated['booking_data'], true);

        if (empty($groupedSlots)) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('error', 'Pilih minimal satu slot pemesanan.');
        }

        DB::connection('mysql_joglo66_app')->beginTransaction();

        try {
            foreach ($groupedSlots as $playDate => $slots) {
                foreach ($slots as $slot) {

                    // Cek bentrok Booking
                    $isBooked = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
                        $query->where('fk_field_id', $fieldId);
                    })
                        ->where('play_date', $playDate)
                        ->whereNotIn('status', ['cancelled', 'failed', 'expired'])
                        ->where('start_play_time', '<', $slot['jam_akhir'])
                        ->where('end_play_time', '>', $slot['jam'])
                        ->lockForUpdate()
                        ->exists();

                    if ($isBooked) {
                        throw new \Exception("Slot {$slot['jam']} - {$slot['jam_akhir']} pada {$playDate} sudah dipesan orang lain.");
                    }

                    // Cek bentrok Tutup Lapangan
                    if (Schema::connection('mysql_joglo66_app')->hasTable('field_closures')) {
                        $slotStartDT = $playDate . ' ' . $slot['jam'] . ':00';
                        $slotEndDT = $playDate . ' ' . $slot['jam_akhir'] . ':00';

                        $isClosed = DB::connection('mysql_joglo66_app')->table('field_closures')
                            ->where('fk_field_id', $fieldId)
                            ->where(function($query) use ($slotStartDT, $slotEndDT) {
                                $query->where('field_closure_start_time', '<', $slotEndDT)
                                      ->where('field_closure_end_time', '>', $slotStartDT);
                            })->exists();

                        if ($isClosed) {
                            throw new \Exception("Lapangan sedang ditutup pada slot {$slot['jam']} - {$slot['jam_akhir']} di tanggal {$playDate}.");
                        }
                    }
                }
            }

            // 3. Simpan data menggunakan $user asli, bukan input $request
            $booking = new Booking;
            $booking->fk_user_id = $userId;
            $booking->fk_field_id = $fieldId;
            $booking->team_name = $validated['team_name'];
            $booking->customer_phone = $user ? ($user->phone_number ?? $user->phone ?? '-') : '-';
            $booking->customer_email = $user ? $user->email : '-';
            $booking->notes = $validated['notes'] ?? '-';
            $booking->booking_date = now()->format('Y-m-d');
            $booking->save();

            $totalPrice = 0;

            foreach ($groupedSlots as $playDate => $slots) {
                foreach ($slots as $slot) {
                    $totalPrice += $slot['harga'];

                    $bookingDetail = new BookingDetail;
                    $bookingDetail->fk_booking_id = $booking->id;
                    $bookingDetail->start_play_time = $slot['jam'];
                    $bookingDetail->end_play_time = $slot['jam_akhir'];
                    $bookingDetail->play_date = $playDate;
                    $bookingDetail->price = $slot['harga'];
                    $bookingDetail->status = 'waiting';
                    $bookingDetail->save();
                }
            }

            $amountToPay = $request->payment_type === self::PAYMENT_DP ? ($totalPrice / 2) : $totalPrice;

            $duitkuResponse = $duitkuService->createInvoice($booking, $amountToPay);

            $payment = new Payment;
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
                'amountToPay' => $amountToPay,
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql_joglo66_app')->rollBack();

            return redirect()->route('tenant.booking.dashboard')
                ->with('error', 'Transaksi gagal: '.$e->getMessage());
        }
    }

    public function success(int $bookingId)
    {
        $booking = Booking::with(['details', 'field', 'user'])
            ->findOrFail($bookingId);

        return view('tenant.booking.success', [
            'booking' => $booking,
        ]);
    }
}
