<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'price_per_night' => fake()->numberBetween(30000, 250000),
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
        ];
    }
}
