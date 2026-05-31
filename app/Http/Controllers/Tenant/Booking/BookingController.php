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
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use UnexpectedValueException;
use Throwable;

class BookingController extends Controller
{
    const PAYMENT_DP = 'down payment';
    const PAYMENT_FINAL = 'final payment';
    const PAYMENT_RESCHEDULE_FEE = 'reschedule fee';
    const PAYMENT_REFUND = 'refund';
    const STATUS_REFUND_DEPOSIT = 'deposit required';
    const STATUS_REFUND_REFUND = 'refund required';
    const STATUS_REFUND_NONE = 'none';
    const STATUS_REFUND_REFUNDABLE = 'Full';
    const STATUS_REFUND_NON_REFUNDABLE = 'None';

    // Solusi php:S1192 - Ekstraksi Seluruh String Duplikat ke Konstanta Kelas
    private const ROUTE_DASHBOARD = 'tenant.booking.dashboard';
    private const DB_CONNECTION = 'mysql_joglo66_app';
    private const STATUS_CANCELLED = 'cancelled';
    private const STATUS_WAITING = 'waiting';
    private const TIME_FORMAT = 'H:i:s';
    private const DATE_FORMAT = 'Y-m-d';
    private const STR_DASH = '-';

    /**
     * Show create booking form
     */
    public function createForm(Request $request): RedirectResponse|View
    {
        $fieldId = $request->query('field_id');

        if (! $fieldId) {
            $response = redirect()->route(self::ROUTE_DASHBOARD)
                ->with('info', 'Silakan pilih lapangan terlebih dahulu');
        } else {
            $field = Field::findOrFail($fieldId);
            $response = view('tenant.booking.create', [
                'field' => $field,
            ]);
        }

        return $response;
    }

    /**
     * Confirm selected booking slots
     */
    public function confirmForm(Request $request): View
    {
        $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'selected_slots' => 'required|string',
        ]);

        $field = Field::findOrFail($request->field_id);
        $selectedSlotsRaw = json_decode($request->selected_slots, true);

        $calculatedData = $this->calculateAndGroupSlots($field->id, $selectedSlotsRaw);

        return view('tenant.booking.confirmation', [
            'field' => $field,
            'groupedSlots' => $calculatedData['groupedSlots'],
            'totalPrice' => $calculatedData['totalPrice']
        ]);
    }

    /**
     * Store booking transaction and handle Duitku Invoice Payment
     */
    public function store(Request $request, DuitkuService $duitkuService): RedirectResponse|View
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'team_name' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'booking_data' => 'required|json',
            'payment_type' => 'required|in:'.self::PAYMENT_DP.','.self::PAYMENT_FINAL,
        ]);

        $user = Auth::user();
        $userId = $user ? $user->id : 1;
        $groupedSlots = json_decode($validated['booking_data'], true);

        if (empty($groupedSlots)) {
            return redirect()->route(self::ROUTE_DASHBOARD)
                ->with('error', 'Pilih minimal satu slot pemesanan.');
        }

        DB::connection(self::DB_CONNECTION)->beginTransaction();

        try {
            $this->validateSlotsAvailability((int)$validated['field_id'], $groupedSlots);

            $savedData = $this->saveBookingAndDetails($userId, $user, $validated, $groupedSlots);
            $booking = $savedData['booking'];
            $totalPrice = $savedData['totalPrice'];

            $amountToPay = $request->payment_type === self::PAYMENT_DP ? ($totalPrice / 2) : $totalPrice;
            $duitkuResponse = $duitkuService->createInvoice($booking, $amountToPay);

            $payment = new Payment;
            $payment->fk_booking_id = $booking->id;
            $payment->reference_id = $duitkuResponse->reference;
            $payment->payment_url = $duitkuResponse->paymentUrl ?? self::STR_DASH;
            $payment->payment_type = $request->payment_type;
            $payment->method = 'transfer';
            $payment->amount = $amountToPay;
            $payment->status = 'pending';
            $payment->save();

            DB::connection(self::DB_CONNECTION)->commit();

            $response = view('tenant.booking.checkout', [
                'booking' => $booking,
                'reference' => $duitkuResponse->reference,
                'amountToPay' => $amountToPay,
            ]);
        } catch (Throwable $e) {
            DB::connection(self::DB_CONNECTION)->rollBack();

            $response = redirect()->route(self::ROUTE_DASHBOARD)
                ->with('error', 'Transaksi gagal: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Show successful checkout details
     */
    public function success(int $bookingId): View
    {
        $booking = Booking::with(['details', 'field', 'user'])->findOrFail($bookingId);

        return view('tenant.booking.success', [
            'booking' => $booking,
        ]);
    }

    /**
     * Private Helper: Mengelompokkan slot serta kalkulasi akumulasi harga (php:S3776)
     */
    private function calculateAndGroupSlots(int $fieldId, array $slotsRaw): array
    {
        $totalPrice = 0;
        $groupedSlots = [];

        foreach ($slotsRaw as $item) {
            $playDate = $item['date'];
            $startTime = Carbon::parse($item['jam'])->format(self::TIME_FORMAT);
            $endTime = Carbon::parse($item['jam_akhir'])->format(self::TIME_FORMAT);
            $dayType = strtolower(Carbon::parse($playDate)->format('l'));

            $fieldPrice = FieldPrice::where('fk_field_id', $fieldId)
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

        return [
            'totalPrice' => $totalPrice,
            'groupedSlots' => $groupedSlots
        ];
    }

    /**
     * Private Helper: Validasi ketersediaan slot secara massal (php:S3776)
     */
    private function validateSlotsAvailability(int $fieldId, array $groupedSlots): void
    {
        foreach ($groupedSlots as $playDate => $slots) {
            foreach ($slots as $slot) {
                $this->checkBookingConflict($fieldId, $playDate, $slot);
                $this->checkFieldClosureConflict($fieldId, $playDate, $slot);
            }
        }
    }

    /**
     * Private Helper: Validasi tabrakan jadwal dengan booking aktif lain (php:S112)
     */
    private function checkBookingConflict(int $fieldId, string $playDate, array $slot): void
    {
        $isBooked = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
            $query->where('fk_field_id', $fieldId);
        })
            ->where('play_date', $playDate)
            ->whereNotIn('status', [self::STATUS_CANCELLED, 'failed', 'expired'])
            ->where('start_play_time', '<', $slot['jam_akhir'])
            ->where('end_play_time', '>', $slot['jam'])
            ->lockForUpdate()
            ->exists();

        if ($isBooked) {
            throw new UnexpectedValueException("Slot {$slot['jam']} - {$slot['jam_akhir']} pada {$playDate} sudah dipesan orang lain.");
        }
    }

    /**
     * Private Helper: Validasi tabrakan jadwal dengan penutupan lapangan/operasional (php:S112)
     */
    private function checkFieldClosureConflict(int $fieldId, string $playDate, array $slot): void
    {
        if (Schema::connection(self::DB_CONNECTION)->hasTable('field_closures')) {
            $slotStartDT = $playDate . ' ' . $slot['jam'] . ':00';
            $slotEndDT = $playDate . ' ' . $slot['jam_akhir'] . ':00';

            $isClosed = DB::connection(self::DB_CONNECTION)->table('field_closures')
                ->where('fk_field_id', $fieldId)
                ->where(function($query) use ($slotStartDT, $slotEndDT) {
                    $query->where('field_closure_start_time', '<', $slotEndDT)
                          ->where('field_closure_end_time', '>', $slotStartDT);
                })->exists();

            if ($isClosed) {
                throw new UnexpectedValueException("Lapangan sedang ditutup pada slot {$slot['jam']} - {$slot['jam_akhir']} di tanggal {$playDate}.");
            }
        }
    }

    /**
     * Private Helper: Menyimpan relasi Booking beserta BookingDetail ke database
     */
    private function saveBookingAndDetails(int $userId, $user, array $validated, array $groupedSlots): array
    {
        $booking = new Booking;
        $booking->fk_user_id = $userId;
        $booking->fk_field_id = $validated['field_id'];
        $booking->team_name = $validated['team_name'];
        $booking->customer_phone = $user ? ($user->phone_number ?? $user->phone ?? self::STR_DASH) : self::STR_DASH;
        $booking->customer_email = $user ? $user->email : self::STR_DASH;
        $booking->notes = $validated['notes'] ?? self::STR_DASH;
        $booking->booking_date = now()->format(self::DATE_FORMAT);
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
                $bookingDetail->status = self::STATUS_WAITING;
                $bookingDetail->save();
            }
        }

        return [
            'booking' => $booking,
            'totalPrice' => $totalPrice
        ];
    }
}
