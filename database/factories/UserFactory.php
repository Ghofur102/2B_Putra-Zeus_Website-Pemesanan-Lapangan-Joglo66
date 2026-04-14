<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => fake()->numerify('0857########'),
            'phone_verified_at' => now(),
            'role' => fake()->randomElement(['worker', 'tenant', 'treasurer', 'owner', 'manager']),
            'remember_token' => Str::random(10),
        ];
    }
}
