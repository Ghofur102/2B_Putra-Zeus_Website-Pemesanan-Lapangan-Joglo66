<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Arena',
            'description' => fake()->paragraph(),
            'image_url' => fake()->imageUrl(800, 600, 'sports'),
            'start_time' => '08:00:00',
            'close_time' => '21:00:00',
            'category' => fake()->randomElement(['futsal', 'mini soccer']),
        ];
    }
}
