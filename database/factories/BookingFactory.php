<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $status_booking = ['active', 'finish', 'waiting'];
        
        return [
            // fk_user_id diisi dari Seeder
            'booking_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status_booking' => fake()->randomElement($status_booking),
        ];
    }
}
