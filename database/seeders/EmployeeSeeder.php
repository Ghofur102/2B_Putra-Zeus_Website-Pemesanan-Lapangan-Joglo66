<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Field;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $staffUsers = User::whereIn('role', ['treasurer', 'manager', 'worker'])->get();

        $treasurer = User::where('role', 'treasurer')->first() ?? User::where('role', 'owner')->first();
        $fields = Field::all();

        if ($fields->isEmpty() || !$treasurer || $staffUsers->isEmpty()) {
            return;
        }

        $employeeIds = [];

        // ==============================================================
        // 1. SEEDING MASTER DATA KARYAWAN (Dengan Relasi ke Akun Login)
        // ==============================================================
        foreach ($staffUsers as $user) {
            $position = ucfirst($user->role);
            if ($user->role === 'worker') $position = 'Staff Lapangan';

            $employeeIds[] = DB::table('employees')->insertGetId([
                'fk_user_id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone ?? $faker->phoneNumber,
                'address' => $faker->address,
                'position' => $position,
                'base_salary' => $faker->randomElement([2000000, 2500000, 3000000, 3500000]),
                'join_date' => Carbon::now()->subMonths(random_int(6, 24))->format('Y-m-d'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==============================================================
        // 2. SEEDING MASTER DATA KARYAWAN (Tanpa Akun Login / Pegawai Kasar)
        // ==============================================================
        for ($i = 0; $i < 3; $i++) {
            $employeeIds[] = DB::table('employees')->insertGetId([
                'fk_user_id' => null,
                'name' => $faker->name,
                'phone_number' => $faker->phoneNumber,
                'address' => $faker->address,
                'position' => $faker->randomElement(['Security', 'Cleaning Service', 'Tukang Parkir']),
                'base_salary' => $faker->randomElement([1500000, 1800000]),
                'join_date' => Carbon::now()->subMonths(random_int(2, 12))->format('Y-m-d'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==============================================================
        // 3. SEEDING HISTORI GAJI (Employee Salaries + Expenses)
        // ==============================================================
        $monthsToSeed = 3;

        for ($i = 1; $i <= $monthsToSeed; $i++) {
            $paymentDate = Carbon::now()->subMonths($i)->endOfMonth();
            $periodMonth = strtolower($paymentDate->format('F'));
            $periodYear = $paymentDate->format('Y');

            foreach ($employeeIds as $empId) {
                $employee = DB::table('employees')->where('id', $empId)->first();

                $bonus = $faker->randomElement([0, 0, 100000, 250000]);
                $deduction = $faker->randomElement([0, 0, 0, 50000]);

                $amountPaid = $employee->base_salary + $bonus - $deduction;

                $expenseId = DB::table('expenses')->insertGetId([
                    'fk_field_id' => $fields->random()->id,
                    'fk_user_id' => $treasurer->id, // Bendahara yang meng-input
                    'category' => 'Gaji Karyawan',
                    'amount' => $amountPaid,
                    'expense_date' => $paymentDate->format('Y-m-d'),
                    'proof_photo' => 'bukti_transfer_gaji_' . uniqid() . '.jpg',
                    'generate_at' => $paymentDate->format('Y-m-d'),
                    'created_at' => $paymentDate->format('Y-m-d H:i:s'),
                    'updated_at' => $paymentDate->format('Y-m-d H:i:s'),
                ]);

                DB::table('employee_salaries')->insert([
                    'fk_employee_id' => $empId,
                    'fk_expense_id' => $expenseId,
                    'amount_paid' => $amountPaid,
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                    'payment_date' => $paymentDate->format('Y-m-d'),
                    'bonus' => $bonus,
                    'deduction' => $deduction,
                    'notes' => "Pembayaran gaji {$employee->name} untuk periode " . ucfirst($periodMonth) . " {$periodYear}",
                    'created_at' => $paymentDate->format('Y-m-d H:i:s'),
                    'updated_at' => $paymentDate->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
