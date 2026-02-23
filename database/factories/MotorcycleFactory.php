<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

class MotorcycleFactory extends Factory
{
    public function definition(): array
    {
        $condition = $this->faker->randomElement(['brand_new', 'second_hand']);
        $isOpenForSwap = $this->faker->boolean(40); // 40% chance nga open for swap

        return [
            'seller_id' => Seller::inRandomOrder()->first()->id ?? Seller::factory(),
            'brand_id' => Brand::inRandomOrder()->first()->id ?? Brand::factory(),
            'model' => $this->faker->randomElement(['Click 125i', 'Aerox 155', 'NMAX', 'Raider R150', 'Sniper 155', 'PCX 160']),
            'year_model' => $this->faker->numberBetween(2018, 2024),
            'color' => $this->faker->safeColorName(),
            'plate_number' => strtoupper($this->faker->bothify('?? ####')),
            'mileage' => $condition === 'brand_new' ? 0 : $this->faker->numberBetween(1000, 50000),
            'engine_capacity' => $this->faker->randomElement([110, 125, 150, 155, 160, 400]),
            'transmission' => $this->faker->randomElement(['manual', 'automatic', 'semi_automatic']),
            'fuel_type' => 'gasoline',
            'current_location' => $this->faker->city(),
            'price' => $this->faker->numberBetween(45000, 150000),
            'is_negotiable' => $this->faker->boolean(70), // Kasagaran negotiable sa Pinas
            'is_open_for_swap' => $isOpenForSwap,
            'swap_preferences' => $isOpenForSwap ? $this->faker->randomElement([
                'Swap to iPhone 15 Pro Max plus cash',
                'Swap to higher unit, add ko cash',
                'Open for swap to Aerox or NMAX only',
                'Swap to Four wheels, add ko cash',
                'Straight swap to Sniper 155'
            ]) : null,
            'condition' => $condition,
            'document_status' => $this->faker->randomElement(['complete_original', 'orig_cr_xerox_or', 'xerox_only', 'no_papers']),
            'is_registered' => $this->faker->boolean(80),
            'description' => $this->faker->paragraph(),
            'issues' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'is_sold' => false,
        ];
    }
}