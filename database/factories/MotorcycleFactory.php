<?php

namespace Database\Factories;

use App\Models\Seller;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Motorcycle>
 */
class MotorcycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'seller_id' => Seller::factory(), 
        'brand_id' => Brand::inRandomOrder()->first()->id ?? 1,
        'model' => fake()->randomElement(['NMAX', 'Click 125i', 'Raider R150', 'PCX 160', 'Sniper 155', 'Aerox 155']),
        'year_model' => fake()->numberBetween(2018, 2024),
        'plate_number' => fake()->bothify('??-####'),
        'mileage' => fake()->numberBetween(500, 30000),
        'price' => fake()->numberBetween(45000, 160000),
        'condition' => fake()->randomElement(['brand_new', 'second_hand']),
        'document_status' => 'complete_original',
        'is_registered' => true,
        'description' => fake()->paragraph(),
        'issues'=> fake()->paragraph(),
        'is_sold' => false,
        ];
    }
}
