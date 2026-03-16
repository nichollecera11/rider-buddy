<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Seller;
use App\Models\Mechanic;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Consultation;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        // 1. Pagpili og random "Reviewable" model (Seller o Mechanic)
        // Puyde ra nimo dugangan og Part::class puhon kon ganahan ka
        $reviewables = [
            Seller::class,
            Mechanic::class,
        ];

        $reviewableType = $this->faker->randomElement($reviewables);
        
        // 2. Pagkuha og existing ID gikan sa maong model
        $reviewableId = $reviewableType::inRandomOrder()->first()->id ?? $reviewableType::factory();

        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'consultation_id' => Consultation::inRandomOrder()->first()->id ?? Consultation::factory(),
            'reviewable_id' => $reviewableId,
            'reviewable_type' => $reviewableType,
            
            'rating' => $this->faker->numberBetween(1, 5),
            'headline' => $this->faker->randomElement([
                'Highly recommended!', 
                'Average service', 
                'Sulit kaayo!', 
                'Dili ko satisfied', 
                'Maayo kaayo mo-trabaho'
            ]),
            'comment' => $this->faker->paragraph(),
            'reply_comment' => $this->faker->boolean(40) ? $this->faker->sentence() : null, // 40% chance naay reply
            
        ];
    }
}