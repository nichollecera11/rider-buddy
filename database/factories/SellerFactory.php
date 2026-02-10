<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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
            'user_id' => User::factory(),
            'image' => null, // Pwede ra null sa pagkakaron
            'shop_name' => fake()->company() . ' Motors',
            'address' => fake()->address(),
            'contact_number' => fake()->phoneNumber(),
            'business_permit_no' => 'BP-' . fake()->numberBetween(10000, 99999),
            'has_delivery' => fake()->boolean(),
        ];
    }
}
