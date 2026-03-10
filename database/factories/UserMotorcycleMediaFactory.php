<?php

namespace Database\Factories;

use App\Models\UserMotorcycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserMotorcycleMedia>
 */
class UserMotorcycleMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_motorcycle_id' => UserMotorcycle::factory(), // Auto-create og motor record
            'file_path' => 'user_motorcycles/verification/fake_image.jpg',
            'file_type' => 'image',
            'collection' => 'verification',
        ];
    }
}
