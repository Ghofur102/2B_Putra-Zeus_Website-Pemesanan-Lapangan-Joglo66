<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $roles = ['admin futsal', 'admin mini soccer', 'tenant', 'treasurer futsal', 'treasurer mini soccer', 'owner'];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => fake()->numerify('085733532098'),
            'phone_verified_at' => now(),
            'team_name' => fake()->optional()->company(),
            'role' => fake()->randomElement($roles),
            'remember_token' => Str::random(10),
        ];
    }
}
