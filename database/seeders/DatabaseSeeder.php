<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Field;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        // =================================================================
        // 1. PEMBUATAN USER (Sesuai Limit yang Diminta)
        // =================================================================
        $owner = User::factory(1)->create(['role' => 'owner'])->first();
        $managers = User::factory(2)->create(['role' => 'manager']);
        $workers = User::factory(2)->create(['role' => 'worker']);
        $tenants = User::factory(5)->create(['role' => 'tenant']);

        // =================================================================
        // 2. PEMBUATAN LAPANGAN
        // =================================================================
        $fields = Field::factory(2)->create();

        // Gabungkan manager dan worker untuk dimasukkan ke field_admins
        $fieldAdmins = $managers->concat($workers);

        foreach ($fields as $field) {

            // --- A. Daftarkan Role Staff ke Field Admins ---
            foreach ($fieldAdmins as $admin) {
                DB::table('field_admins')->insert([
                    'fk_user_id' => $admin->id,
                    'fk_field_id' => $field->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // --- B. Harga per Hari ---
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                DB::table('field_prices')->insert([
                    'fk_field_id' => $field->id,
                    'start_time' => '08:00:00',
                    'end_time' => '21:00:00',
                    'day_type' => $day,
                    'price' => $faker->randomElement([100000, 150000, 200000]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // --- C. Atribut Tambahan (Bola, Rompi, dll) ---
            for ($i = 0; $i < 3; $i++) {
                DB::table('attributes')->insert([
                    'fk_field_id' => $field->id,
                    'name' => $faker->randomElement(['Bola Futsal', 'Sepatu', 'Rompi', 'Sarung Tangan']),
                    'stock' => $faker->numberBetween(5, 20),
                    'price_hour' => $faker->randomElement([15000, 20000, 35000]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // --- D. Laporan Keuangan ---
            $months = ['january', 'february', 'march', 'april', 'may', 'june'];
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

            // =================================================================
            // 3. PEMBUATAN BOOKING (Logis dan Sesuai Status)
            // =================================================================
            // Kita pastikan setiap lapangan punya SETIDAKNYA 1 sample dari setiap status
            $statuses = ['finish', 'waiting', 'active', 'reschedule', 'cancelled', 'field closure'];

            foreach ($statuses as $status) {
                $tenant = $tenants->random();

                // --- LOGIKA TEMPORAL (MAKE SENSE) ---
                if ($status === 'finish') {
                    // Harus masa lalu, dan pembayaran harus Lunas
                    $playDate = now()->subDays(rand(1, 10));
                    $dpStatus = 'success';
                    $finalStatus = 'success';
                } elseif ($status === 'waiting') {
                    // Masa depan, DP masih di check/pending
                    $playDate = now()->addDays(rand(1, 10));
                    $dpStatus = 'pending';
                    $finalStatus = null;
                } elseif ($status === 'active') {
                    // Masa depan, DP sudah lunas
                    $playDate = now()->addDays(rand(1, 10));
                    $dpStatus = 'success';
                    $finalStatus = null;
                } elseif ($status === 'reschedule') {
                    // Masa depan (jadwal baru), DP lunas
                    $playDate = now()->addDays(rand(5, 15));
                    $dpStatus = 'success';
                    $finalStatus = null;
                } else {
                    // cancelled & field closure (Bisa masa depan/lewat, anggap masa depan)
                    $playDate = now()->addDays(rand(1, 10));
                    $dpStatus = 'success'; // Sudah terlanjur bayar DP lalu batal
                    $finalStatus = null;
                }

                $bookingDate = $playDate->copy()->subDays(rand(1, 5)); // Booking date selalu lebih awal dari play_date
                $pricePerSlot = 300000;

                // Insert Booking Induk
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

                // Insert Booking Detail
                $detailId = DB::table('booking_details')->insertGetId([
                    'fk_booking_id' => $bookingId,
                    'start_play_time' => '19:00:00',
                    'end_play_time' => '21:00:00', // 2 Jam Main
                    'play_date' => $playDate->format('Y-m-d'),
                    'price' => $pricePerSlot,
                    'status' => $status,
                    'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                    'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                ]);

                // Insert Attributes (Opsional, agar logic pembayaran nyambung)
                $attributeTotal = 0;
                $attr = DB::table('attributes')->where('fk_field_id', $field->id)->inRandomOrder()->first();
                if ($attr) {
                    $qty = 2;
                    $attributeTotal = $attr->price_hour * $qty; // Karena 2 jam main
                    DB::table('booking_attributes')->insert([
                        'fk_booking_id' => $bookingId,
                        'fk_attribute_id' => $attr->id,
                        'quantity' => $qty,
                        'price' => $attr->price_hour,
                        'total' => $attributeTotal,
                        'reason' => '-',
                        'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                    ]);
                }

                // Kalkulasi Total Uang Masuk Akal
                $totalPayment = $pricePerSlot + $attributeTotal;
                $downPaymentAmount = $totalPayment / 2; // 50% DP

                // --- ALUR PEMBAYARAN MAKE SENSE ---
                if ($dpStatus === 'pending') {
                    // Menunggu Pembayaran DP
                    DB::table('payments')->insert([
                        'fk_booking_id' => $bookingId,
                        'fk_booking_detail_id' => $detailId,
                        'reference_id' => 'INV-DP-' . strtoupper(Str::random(8)),
                        'payment_url' => 'https://midtrans.com/dummy/' . Str::random(10),
                        'payment_type' => 'down payment',
                        'method' => 'transfer',
                        'amount' => $downPaymentAmount,
                        'status' => 'pending',
                        'paid_at' => null,
                        'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                    ]);
                } else {
                    // DP Lunas
                    DB::table('payments')->insert([
                        'fk_booking_id' => $bookingId,
                        'fk_booking_detail_id' => $detailId,
                        'reference_id' => 'INV-DP-' . strtoupper(Str::random(8)),
                        'payment_type' => 'down payment',
                        'method' => 'transfer',
                        'amount' => $downPaymentAmount,
                        'status' => 'success',
                        'paid_at' => $bookingDate->copy()->addMinutes(15)->format('Y-m-d H:i:s'),
                        'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'updated_at' => $bookingDate->copy()->addMinutes(15)->format('Y-m-d H:i:s'),
                    ]);

                    // Jika 'finish', berarti Pelunasan Akhir (Final Payment) juga sudah dibayar
                    if ($finalStatus === 'success') {
                        DB::table('payments')->insert([
                            'fk_booking_id' => $bookingId,
                            'fk_booking_detail_id' => $detailId,
                            'reference_id' => 'INV-FN-' . strtoupper(Str::random(8)),
                            'payment_type' => 'final payment',
                            'method' => 'cash',
                            'amount' => $downPaymentAmount, // Sisa pelunasan
                            'status' => 'success',
                            'paid_at' => $playDate->copy()->setTime(18, 30)->format('Y-m-d H:i:s'), // Lunas sebelum main
                            'created_at' => $playDate->copy()->setTime(18, 30)->format('Y-m-d H:i:s'),
                            'updated_at' => $playDate->copy()->setTime(18, 30)->format('Y-m-d H:i:s'),
                        ]);
                    }
                }

                // --- DATA KHUSUS BERDASARKAN STATUS KHUSUS ---
                if ($status === 'reschedule') {
                    $oldDate = $playDate->copy()->subDays(3); // Logika: jadwal asli 3 hari lalu
                    DB::table('booking_reschedules')->insert([
                        'fk_booking_detail_id' => $detailId,
                        'fk_field_closure_id' => null,
                        'old_date' => $oldDate->format('Y-m-d'),
                        'status_refund' => 'None',
                        'reason' => 'Penyewa minta ganti hari',
                        'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                    ]);
                } elseif ($status === 'cancelled') {
                    DB::table('booking_cancelled')->insert([
                        'fk_booking_detail_id' => $detailId,
                        'fk_field_closure_id' => null,
                        'cancle_date' => $playDate->copy()->subDays(4)->format('Y-m-d'),
                        'status_refund' => 'None', // Karena batal H-4, misal hangus
                        'reason' => 'Ada acara keluarga mendadak',
                        'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                    ]);
                } elseif ($status === 'field closure') {
                    // Buat Data Penutupan Lapangan Khusus
                    $closureId = DB::table('field_closures')->insertGetId([
                        'fk_field_id' => $field->id,
                        'fk_user_id' => $managers->random()->id,
                        'field_closure_start_time' => $playDate->copy()->setTime(8, 0)->format('Y-m-d H:i:s'),
                        'field_closure_end_time' => $playDate->copy()->setTime(23, 0)->format('Y-m-d H:i:s'),
                        'reason' => 'Renovasi Rumput Lapangan Tiba-tiba',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Catat ke tabel cancelled bahwa jadwal ini batal gara-gara lapangan tutup
                    DB::table('booking_cancelled')->insert([
                        'fk_booking_detail_id' => $detailId,
                        'fk_field_closure_id' => $closureId,
                        'cancle_date' => now()->format('Y-m-d'),
                        'status_refund' => 'Full', // Lapangan tutup = Refund Penuh
                        'reason' => 'Terdampak Penutupan Lapangan',
                        'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                    ]);
                }

                // Insert Log Sistem
                DB::table('logs')->insert([
                    'fk_user_id' => $tenant->id,
                    'action' => 'Created Booking',
                    'table_name' => 'bookings',
                    'record_id' => $bookingId,
                    'description' => 'User successfully created a booking',
                    'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
