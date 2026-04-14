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
            'category' => fake()->randomElement(['futsal', 'mini soccer']),
        ];
    }
}
