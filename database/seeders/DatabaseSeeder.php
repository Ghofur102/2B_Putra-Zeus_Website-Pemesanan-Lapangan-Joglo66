<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\FieldSeeder;
use Database\Seeders\BookingSeeder;
use Database\Seeders\EmployeeSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            FieldSeeder::class,
            // BookingSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
