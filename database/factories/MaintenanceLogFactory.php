<?php

namespace Database\Factories;

use App\Models\Mechanic;
use App\Models\UserMotorcycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_motorcycle_id' => UserMotorcycle::factory(),
            'mechanic_id' => Mechanic::factory(),
            'service_type' => fake()->randomElement([
                'Change Oil',
                'Tune-up',
                'CVT Cleaning',
                'Brake Pad Replacement',
            ]),
            'description' => fake()->sentence(),
            'odometer_reading' => fake()->numberBetween(1000, 50000),
            'service_date' => fake()->date(),
            'cost' => fake()->randomFloat(2, 200, 5000),
            'is_verified_by_mechanic' => true,
        ];
    }

    public function withoutMechanic(): static
    {
        return $this->state(fn () => [
            'mechanic_id' => null,
            'is_verified_by_mechanic' => false,
        ]);
    }
}
