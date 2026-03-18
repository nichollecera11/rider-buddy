<?php
namespace Database\Factories;

use App\Models\Consultation;
use App\Models\User;
use App\Models\Mechanic;
use App\Models\UserMotorcycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    public function definition(): array
    {
        return [
            // Mo-pili ni og random existing ID o mobuhat og bag-o
            'user_id' => User::factory(), 
            'mechanic_id' => Mechanic::factory(),
            'user_motorcycle_id' => UserMotorcycle::factory(),
            
            'consultation_type' => $this->faker->randomElement(['standard', 'sos']),
            'issue_description' => $this->faker->paragraph(),
            
            // Ang agreed fee i-base nato sa common rates
            'agreed_diagnostic_fee' => $this->faker->randomElement([150, 200, 300, 500]),
            'estimated_repair_costs' => $this->faker->optional()->randomFloat(2, 500, 5000),
            
            'payment_status' => $this->faker->randomElement(['pending', 'paid']),
            'status' => $this->faker->randomElement(['pending', 'ongoing', 'inspected', 'closed']),
            'rating' => fake()->numberBetween(1, 5),
            'review_comment' => fake()->paragraph(),
            'rated_at' => now(), 

            // Sample JSON data para sa listahan sa pyesa
            'suggested_parts' => ([
                ['part' => 'Brake Pad', 'price' => 450],
                ['part' => 'Engine Oil', 'price' => 350]
            ]),
            
            // Realistic PH coordinates (Cebu/Mandaue area)
            'latitude' => $this->faker->latitude(10.3, 10.4), 
            'longitude' => $this->faker->longitude(123.8, 124.0),
            'location_name' => $this->faker->streetAddress . ", Cebu City",
            
            'mechanic_notes' => $this->faker->optional()->paragraph(),
            'verification_otp' => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),


        ];
    }
}