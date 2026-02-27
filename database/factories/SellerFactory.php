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
            'shop_name' => $this->faker->company() . ' Motors',
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'business_permit_no' => 'BP-' . $this->faker->numberBetween(10000, 99999),
            'has_delivery' => $this->faker->boolean(),
            'description' =>$this->faker->sentence(),
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'latitude' => $this->faker->latitude(10.2, 10.4), // Random Cebu-ish lat
            'longitude' => $this->faker->longitude(123.8, 124.0),
            'is_official_store' => $this->faker->boolean(),     
            'is_verified' => $this->faker->boolean(),       
            'is_24_7' => $this->faker->boolean()
        ];
    }
}
