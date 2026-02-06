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
            'user_id'=> User::factory(),
            'name'=> $this->faker->name(),
            'shop_name'=>$this->faker->company() . 'Dodoy',
            'address'=>$this->faker->address(),
            'contact_number'=>$this->faker->phoneNumber(),
            'years_experience'=>$this->faker->numberBetween(1, 20),
            'is_verified'=>$this->faker->boolean(),
            'service_fee_starts_at'=>$this->faker->randomFloat(2, 500, 1000),
            'image'=> 'mechanic_default.jpg',
        ];
    }
}
