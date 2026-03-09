<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_motorcycle_id' => 1, // I-override lang ni sa Seeder
            'mechanic_id' => fake()->randomElement([null, 1]),
            'service_type' => fake()->randomElement(['Change Oil', 'Tune-up', 'CVT Cleaning', 'Brake Pad Replacement']),
            'description' => fake()->sentence(),
            'odometer_reading' => fake()->numberBetween(1000, 50000),
            'service_date' => fake()->date(),
            'cost' => fake()->randomFloat(2, 200, 5000),
            'is_verified_by_mechanic' => fake()->boolean(50), // 50% chance verified
        ];
    }
}
