<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserMotorcycle>
 */
class UserMotorcycleFactory extends Factory
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
        'brand_id' => Brand::all()->random()->id,
        'model' => $this->faker->word(),
        'year_model' => $this->faker->year(),
        'engine_capacity' => $this->faker->randomElement([125, 155, 400, 650]),
        'transmission' => $this->faker->randomElement(['manual', 'automatic', 'semi_automatic', 'none_electric']),
        'fuel_type' => $this->faker->randomElement(['gasoline', 'electric']),
        'color' => $this->faker->safeColorName(),
        
        // 📋 LTO Details (Nullable pero butangan natog fake data)
        'plate_number' => strtoupper($this->faker->bothify('?? #####')),
        'engine_number' => strtoupper($this->faker->bothify('?#?#######')), // Fake Long Strings
        'chassis_number' => strtoupper($this->faker->bothify('?#?###########')), 
        
        // 🗓️ Dates
        'last_registration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        'insurance_expiry' => $this->faker->dateTimeBetween('now', '+1 year'),
        
        // 📈 Maintenance
        'current_odometer' => $this->faker->numberBetween(100, 50000),
        
        // ⚙️ Status Flags
        'is_main' => false,
        'is_active' => true,
    ];
    }
}
