<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(1)->create(['role' => 'owner']);
        User::factory(1)->create(['role' => 'treasurer']);
        User::factory(2)->create(['role' => 'manager']);
        User::factory(2)->create(['role' => 'worker']);
        User::factory(5)->create(['role' => 'tenant']);
    }
}
