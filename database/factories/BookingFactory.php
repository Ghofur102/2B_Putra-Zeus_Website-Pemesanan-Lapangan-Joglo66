<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            // fk_user_id dan fk_field_id diisi dari Seeder
            'booking_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'team_name' => fake()->company() . ' FC',
        ];
    }
}
