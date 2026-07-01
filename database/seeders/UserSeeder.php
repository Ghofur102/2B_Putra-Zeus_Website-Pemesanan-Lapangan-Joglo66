<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Hendra Wijaya',
            'email' => 'owner@joglo66.com',
            'password' => Hash::make('password123'),
            'phone' => '081234567890',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Citra Lestari',
            'email' => 'treasurer@joglo66.com',
            'password' => Hash::make('password123'),
            'phone' => '081298765432',
            'role' => 'treasurer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'worker@joglo66.com',
            'password' => Hash::make('password123'),
            'phone' => '081345678901',
            'role' => 'worker',
            'email_verified_at' => now(),
        ]);
    }
}
