<?php

namespace Database\Factories;
use App\Models\Seller;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part>
 */
class PartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Ma-link ni sa seller table (singular)
            'seller_id' => Seller::factory(),

            // Magkuha og random category ug brand gikan sa table
            'category_id' => Category::inRandomOrder()->first()->id ?? 1,
            'brand_id' => Brand::inRandomOrder()->first()->id ?? 1,

            'part_name' => fake()->randomElement([
                'RCB Brake Lever',
                'TDR Spark Plug',
                'Mitas Tires',
                'Racing Boy Rim',
                'CVT Set',
                'Side Mirror',
                'Shock Absorber'
            ]),

            'condition' => fake()->randomElement(['new', 'used']),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 200, 15000), // Presyo gikan 200 hangtod 15k
            'stock_quantity' => fake()->numberBetween(1, 20),

            // Pananglitan sa compatibility
            'compatibility' => fake()->randomElement([
                'Mio Sporty, Mio Soul',
                'NMAX v1, NMAX v2',
                'Honda Click 125i/150i',
                'Raider R150 Fi'
            ]),
        ];
    }
}
