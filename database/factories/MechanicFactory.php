<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mechanic>
 */
class MechanicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'shop_name' => $this->faker->company() . 'Dodoy',
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'bio' => $this->faker->paragraph(),
            'specialization' => $this->faker->randomElement(['Electrical', 'Engine Overhaul', 'FI Specialist', 'Tune up']),
            'years_experience' => $this->faker->numberBetween(1, 20),
            'is_verified' => $this->faker->boolean(),
            'is_available' => true,
            'diagnostic_fee_base' => $this->faker->randomFloat(2, 100, 500),
            'latitude' => $this->faker->latitude(10.2, 10.4), // Random Cebu-ish lat
            'longitude' => $this->faker->longitude(123.8, 124.0), // Random Cebu-ish long
            'service_fee_starts_at' => $this->faker->randomFloat(2, 500, 2000),
            'is_24_7' => $this->faker->boolean(30), // 30% chance nga open 24/7 (pananglitan vulcanizing)
            'offers_towing' => $this->faker->boolean(20), // Pipila ra ang naay pang-guyod
            
        ];
    }
}
