<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LTOCompliance;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LTOComplianceMedia>
 */
class LTOComplianceMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'l_t_o_compliance_id' => LTOCompliance::factory(),
            'file_path' => 'lto_docs/seed_sample.jpg',
            'document_type' => $this->faker->randomElement(['OR', 'CR', 'ID']),
        ];
    }
}
