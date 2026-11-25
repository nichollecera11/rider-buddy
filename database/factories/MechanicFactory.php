<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends Factory<\App\Models\Mechanic>
 */
class MechanicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mechanic(),
            'name' => $this->faker->name(),
            'shop_name' => $this->faker->company() . ' Dodoy',
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'bio' => $this->faker->paragraph(),
            'specialization' => $this->faker->randomElement([
                'Electrical',
                'Engine Overhaul',
                'FI Specialist',
                'Tune up',
            ]),
            'years_experience' => $this->faker->numberBetween(1, 20),
            'is_verified' => false,
            'is_available' => true,
            'diagnostic_fee_base' => $this->faker->randomFloat(2, 100, 500),
            'latitude' => $this->faker->latitude(10.2, 10.4),
            'longitude' => $this->faker->longitude(123.8, 124.0),
            'service_fee_starts_at' => $this->faker->randomFloat(2, 500, 2000),
            'is_24_7' => $this->faker->boolean(30),
            'offers_towing' => $this->faker->boolean(20),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => false,
        ]);
    }

    public function available(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_available' => true,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_available' => false,
        ]);
    }
}
