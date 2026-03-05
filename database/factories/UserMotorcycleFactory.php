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
            'brand_id' => Brand::all()->random()->id ?? Brand::factory(),
            'model' => $this->faker->randomElement(['NMAX', 'PCX', 'Click 125i', 'Raider R150', 'Aerox']),
            'year_model' => $this->faker->year(),
            'plate_number' => strtoupper($this->faker->bothify('???-####')),
            'engine_number' => strtoupper($this->faker->bothify('ENG-#########')),
            'color' => $this->faker->safeColorName(),
            'is_main' => false,
        ];
    }
}
