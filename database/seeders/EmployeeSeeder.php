<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $treasurerUser = User::where('role', 'treasurer')->first();
        $workerUser = User::where('role', 'worker')->first();

        if ($treasurerUser) {
            DB::table('employees')->insert([
                'fk_user_id' => $treasurerUser->id,
                'name' => $treasurerUser->name,
                'phone_number' => $treasurerUser->phone,
                'address' => 'Jl. Anggrek No. 45, Banyuwangi',
                'position' => 'Bendahara',
                'base_salary' => 4500000,
                'join_date' => '2025-01-15',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($workerUser) {
            DB::table('employees')->insert([
                'fk_user_id' => $workerUser->id,
                'name' => $workerUser->name,
                'phone_number' => $workerUser->phone,
                'address' => 'Jl. S Parman Gg. 3, Banyuwangi',
                'position' => 'Staff Lapangan',
                'base_salary' => 3200000,
                'join_date' => '2025-03-01',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
