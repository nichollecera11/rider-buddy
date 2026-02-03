<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(), 
            'image' => null, // Pwede ra null sa pagkakaron
            'shop_name' => fake()->company() . ' Motors', 
            'seller_name' => fake()->userName(), 
            'address' => fake()->address(),
            'contact_number' => fake()->phoneNumber(),
            'business_permit_no' => 'BP-' . fake()->numberBetween(10000, 99999),
            'has_delivery' => fake()->boolean(),
        ];
    }
}
