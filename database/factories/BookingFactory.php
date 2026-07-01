<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'team_name' => fake()->company() . ' FC',
        ];
    }
}
