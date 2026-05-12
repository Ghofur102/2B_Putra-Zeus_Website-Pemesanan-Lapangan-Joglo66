<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Field;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $fields = Field::all();
        $tenants = User::where('role', 'tenant')->get();
        $managers = User::where('role', 'manager')->get();

        $statuses = ['finish', 'waiting', 'active', 'reschedule', 'cancelled', 'field closure'];

        foreach ($fields as $field) {
            foreach ($statuses as $status) {
                $tenant = $tenants->random();
                $this->createBookingScenario($field, $tenant, $managers, $status, $faker);
            }
        }
    }

    private function createBookingScenario($field, $tenant, $managers, $status, $faker): void
    {
        // Penggunaan random_int untuk menghindari peringatan PRNG SonarQube
        $playDate = $this->determinePlayDate($status);
        $dpStatus = in_array($status, ['waiting']) ? 'pending' : 'success';
        $finalStatus = ($status === 'finish') ? 'success' : null;

        $bookingDate = $playDate->copy()->subDays(random_int(1, 5));
        $pricePerSlot = 300000;

        $bookingId = DB::table('bookings')->insertGetId([
            'fk_user_id' => $tenant->id,
            'fk_field_id' => $field->id,
            'team_name' => $faker->company,
            'booking_date' => $bookingDate->format('Y-m-d H:i:s'),
            'customer_phone' => $faker->phoneNumber,
            'customer_email' => $tenant->email,
            'created_at' => $bookingDate->format('Y-m-d H:i:s'),
            'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
        ]);

        $detailId = DB::table('booking_details')->insertGetId([
            'fk_booking_id' => $bookingId,
            'start_play_time' => '19:00:00',
            'end_play_time' => '21:00:00',
            'play_date' => $playDate->format('Y-m-d'),
            'price' => $pricePerSlot,
            'status' => $status,
            'created_at' => $bookingDate->format('Y-m-d H:i:s'),
            'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
        ]);

        $attributeTotal = $this->attachBookingAttribute($field->id, $bookingId, $bookingDate);
        $downPaymentAmount = ($pricePerSlot + $attributeTotal) / 2;

        $this->processPayments($bookingId, $detailId, $dpStatus, $finalStatus, $downPaymentAmount, $bookingDate, $playDate);
        $this->handleSpecialStatuses($status, $detailId, $field->id, $managers->random()->id, $playDate, $bookingDate);

        DB::table('logs')->insert([
            'fk_user_id' => $tenant->id,
            'action' => 'Created Booking',
            'table_name' => 'bookings',
            'record_id' => $bookingId,
            'description' => 'User successfully created a booking',
            'created_at' => $bookingDate->format('Y-m-d H:i:s'),
        ]);
    }

    private function determinePlayDate(string $status)
    {
        if ($status === 'finish') return now()->subDays(random_int(1, 10));
        if ($status === 'reschedule') return now()->addDays(random_int(5, 15));

        return now()->addDays(random_int(1, 10));
    }

    private function attachBookingAttribute(int $fieldId, int $bookingId, $bookingDate): int
    {
        $attr = DB::table('attributes')->where('fk_field_id', $fieldId)->inRandomOrder()->first();
        if (!$attr) return 0;

        $qty = 2;
        $total = $attr->price_hour * $qty;

        DB::table('booking_attributes')->insert([
            'fk_booking_id' => $bookingId,
            'fk_attribute_id' => $attr->id,
            'quantity' => $qty,
            'price' => $attr->price_hour,
            'total' => $total,
            'reason' => '-',
            'created_at' => $bookingDate->format('Y-m-d H:i:s'),
            'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
        ]);

        return $total;
    }

    private function processPayments($bookingId, $detailId, $dpStatus, $finalStatus, $amount, $bookingDate, $playDate): void
    {
        // Down Payment
        $isPending = $dpStatus === 'pending';
        DB::table('payments')->insert([
            'fk_booking_id' => $bookingId,
            'fk_booking_detail_id' => $detailId,
            'reference_id' => 'INV-DP-' . strtoupper(Str::random(8)),
            'payment_url' => $isPending ? 'https://midtrans.com/dummy/' . Str::random(10) : null,
            'payment_type' => 'down payment',
            'method' => 'transfer',
            'amount' => $amount,
            'status' => $dpStatus,
            'paid_at' => $isPending ? null : $bookingDate->copy()->addMinutes(15)->format('Y-m-d H:i:s'),
            'created_at' => $bookingDate->format('Y-m-d H:i:s'),
            'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
        ]);

        // Final Payment
        if ($finalStatus === 'success') {
            DB::table('payments')->insert([
                'fk_booking_id' => $bookingId,
                'fk_booking_detail_id' => $detailId,
                'reference_id' => 'INV-FN-' . strtoupper(Str::random(8)),
                'payment_type' => 'final payment',
                'method' => 'cash',
                'amount' => $amount,
                'status' => 'success',
                'paid_at' => $playDate->copy()->setTime(18, 30)->format('Y-m-d H:i:s'),
                'created_at' => $playDate->copy()->setTime(18, 30)->format('Y-m-d H:i:s'),
                'updated_at' => $playDate->copy()->setTime(18, 30)->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function handleSpecialStatuses($status, $detailId, $fieldId, $managerId, $playDate, $bookingDate): void
    {
        if ($status === 'reschedule') {
            DB::table('booking_reschedules')->insert([
                'fk_booking_detail_id' => $detailId,
                'old_date' => $playDate->copy()->subDays(3)->format('Y-m-d'),
                'status_refund' => 'None',
                'reason' => 'Penyewa minta ganti hari',
                'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
            ]);
        } elseif ($status === 'cancelled') {
            DB::table('booking_cancelled')->insert([
                'fk_booking_detail_id' => $detailId,
                'cancle_date' => $playDate->copy()->subDays(4)->format('Y-m-d'),
                'status_refund' => 'None',
                'reason' => 'Ada acara keluarga mendadak',
                'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
            ]);
        } elseif ($status === 'field closure') {
            $closureId = DB::table('field_closures')->insertGetId([
                'fk_field_id' => $fieldId,
                'fk_user_id' => $managerId,
                'field_closure_start_time' => $playDate->copy()->setTime(8, 0)->format('Y-m-d H:i:s'),
                'field_closure_end_time' => $playDate->copy()->setTime(23, 0)->format('Y-m-d H:i:s'),
                'reason' => 'Renovasi Rumput Lapangan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('booking_cancelled')->insert([
                'fk_booking_detail_id' => $detailId,
                'fk_field_closure_id' => $closureId,
                'cancle_date' => now()->format('Y-m-d'),
                'status_refund' => 'Full',
                'reason' => 'Terdampak Penutupan Lapangan',
                'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
