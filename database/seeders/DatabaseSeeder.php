<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Field;
use App\Models\Booking;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $users = User::factory(10)->create();
        $tenants = $users->where('role', 'tenant');
        $admins = $users->whereIn('role', ['admin futsal', 'admin mini soccer']);

        // 2. Generate Fields (Total 2 Lapangan)
        $fields = Field::factory(2)->create();

        foreach ($fields as $field) {
            // 3. Field Admins
            DB::table('field_admins')->insert([
                'fk_user_id' => $admins->random()->id,
                'fk_field_id' => $field->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Field Prices (Harga per hari)
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                DB::table('field_prices')->insert([
                    'fk_field_id' => $field->id,
                    'start_time' => '08:00:00',
                    'end_time' => '21:00:00',
                    'day_type' => $day,
                    'price' => $faker->randomElement([100000, 150000, 200000, 250000]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 5. Attributes (Barang sewaan: Bola, Sepatu, dll)
            for ($i = 0; $i < 3; $i++) {
                DB::table('attributes')->insert([
                    'fk_field_id' => $field->id,
                    'name' => $faker->randomElement(['Bola Futsal', 'Sepatu', 'Rompi Tim', 'Sarung Tangan']),
                    'stock' => $faker->numberBetween(5, 20),
                    'price_hour' => $faker->randomElement([15000, 20000, 35000]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 6. Expenses (Pengeluaran Lapangan)
            DB::table('expenses')->insert([
                'fk_field_id' => $field->id,
                'fk_user_id' => $admins->count() > 0 ? $admins->random()->id : $admins->random()->id,
                'category' => $faker->randomElement(['Listrik', 'Air', 'Perawatan Rumput', 'Ganti Bola']),
                'amount' => $faker->numberBetween(500000, 2500000),
                'expense_date' => now()->subDays(rand(1, 30)),
                'proof_photo' => 'dummy_receipt.jpg',
                'generate_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 7. Financial Reports (Laporan Keuangan Bulanan)
            $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
            DB::table('financial_reports')->insert([
                'fk_field_id' => $field->id,
                'year' => date('Y'),
                'month' => $faker->randomElement($months),
                'total_income' => $faker->numberBetween(5000000, 15000000),
                'total_expense' => $faker->numberBetween(1000000, 5000000),
                'net_profit' => $faker->numberBetween(4000000, 10000000),
                'generate_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 8. Field Closures (Penutupan Lapangan Sementara)
            DB::table('field_closures')->insertGetId([
                'fk_field_id' => $field->id,
                'field_closure_start_time' => now()->addDays(rand(1, 5))->setTime(10, 0),
                'field_closure_end_time' => now()->addDays(rand(1, 5))->setTime(15, 0),
                'reason' => 'Perbaikan Jaring dan Rumput',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 9. Generate Bookings (Total 40 Transaksi)
        Booking::factory(40)->make()->each(function ($booking) use ($tenants, $fields, $faker) {
            $booking->fk_user_id = $tenants->random()->id;
            $booking->save();

            $field = $fields->random();

            // 10. Booking Details
            $detailId = DB::table('booking_details')->insertGetId([
                'fk_booking_id' => $booking->id,
                'fk_field_id' => $field->id,
                'start_play_time' => '19:00:00',
                'end_play_time' => '21:00:00',
                'play_date' => $booking->booking_date,
                'price' => 300000,
                'status' => $faker->randomElement(['active', 'waiting', 'finish', 'reschedule from tenant', 'cancelled from tenant']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 11. Booking Attributes (Sewa Barang)
            $attribute = DB::table('attributes')->where('fk_field_id', $field->id)->inRandomOrder()->first();
            if ($attribute) {
                DB::table('booking_attributes')->insert([
                    'fk_booking_id' => $booking->id,
                    'fk_attribute_id' => $attribute->id,
                    'start_booking_attribute_time' => Carbon::parse($booking->booking_date . ' 19:00:00'),
                    'return_booking_attribute_time' => Carbon::parse($booking->booking_date . ' 21:00:00'),
                    'quantity' => 2,
                    'price' => $attribute->price_hour,
                    'total' => $attribute->price_hour * 2,
                    'reason' => '-',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 12. Payments

            DB::table('payments')->insert([
                'fk_booking_id' => $booking->id,
                'fk_booking_detail_id' => $detailId,
                'reference_id' => 'INV-' . strtoupper(Str::random(10)),
                'payment_url' => 'https://midtrans.com/dummy/' . Str::random(15),
                'payment_type' => $faker->randomElement(['down payment', 'final payment']),
                'method' => $faker->randomElement(['transfer']),
                'amount' => $faker->randomElement([100000, 150000, 300000]),
                'status' => $faker->randomElement(['pending', 'success']),
                'paid_at' => $faker->boolean(70) ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 13. Logs
            DB::table('logs')->insert([
                'fk_user_id' => $booking->fk_user_id,
                'action' => 'Created Booking',
                'table_name' => 'bookings',
                'record_id' => $booking->id,
                'description' => 'User successfully created a booking',
                'created_at' => now(),
            ]);

            // Simulasi data Reschedule atau Cancelled berdasarkan status
            $status = DB::table('booking_details')->where('id', $detailId)->value('status');
            $closure = DB::table('field_closures')->inRandomOrder()->first();

            if ($status === 'reschedule from tenant') {
                // 14. Booking Reschedules
                DB::table('booking_reschedules')->insert([
                    'fk_booking_detail_id' => $detailId,
                    'fk_field_closure_id' => $closure ? $closure->id : null,
                    'old_date' => Carbon::parse($booking->booking_date)->subDays(2),
                    'new_date' => $booking->booking_date,
                    'reason' => 'Hujan lebat / Anggota tidak lengkap',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($status === 'cancelled from tenant') {
                // 15. Booking Cancelled
                DB::table('booking_cancelled')->insert([
                    'fk_booking_detail_id' => $detailId,
                    'fk_field_closure_id' => $closure ? $closure->id : null,
                    'reason' => 'Batal mendadak',
                    'cancle_date' => now(), 
                    'status_refund' => $faker->randomElement(['refundable', 'non-refundable']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
