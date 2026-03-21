<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class PartFactory extends Factory
{
    public function definition(): array
    {
        $isOpenForSwap = $this->faker->boolean(30);
        $isUniversal = $this->faker->boolean(40); // 40% chance universal (mirrors, grips, etc.)
        
        // Mga sample data para realistic ang results
        $bikeModels = ['Honda Click 125i', 'Yamaha NMAX V2', 'Suzuki Raider R150 Fi', 'Honda Beat FI', 'Yamaha Aerox v1'];
        $dimOptions = ['14 inches', '17 inches', '10mm', '12mm', '80/90-14', '90/80-14', 'Standard Size'];

        $parts = [
            'Tires' => ['Pilot Street', 'Angel Scooter', 'City Grip', 'Maxing'],
            'Brakes' => ['Brake Pad', 'Brake Shoe', 'Disc Plate'],
            'Drive' => ['Drive Chain', 'Sprocket Set', 'CVT Belt', 'Flyball'],
            'Lights' => ['LED Headlight', 'Signal Light', 'Auxiliary Light'],
            'Body' => ['Fairings Set', 'Side Mirror', 'Seat Cover', 'Top Box']
        ];

        return [
            'seller_id' => Seller::inRandomOrder()->first()->id ?? Seller::factory(),
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
            'brand_id' => Brand::inRandomOrder()->first()->id ?? Brand::factory(),
            
            'part_name' => $this->faker->randomElement($this->faker->randomElement($parts)),
            'slug' => fn (array $attributes) => Str::slug($attributes['part_name']) . '_'  . $this->faker->unique()->numberBetween(1000, 9999),
            'part_number' => strtoupper($this->faker->bothify('??-####-###')),
            
            'type' => $this->faker->randomElement(['original', 'replacement', 'aftermarket']),
            'condition' => $this->faker->randomElement(['new', 'used']),
            'status' => $this->faker->randomElement(['available','available','available','available','sold','reserved']),
            'main_image' => 'parts/placeholder/part-' . $this->faker->numberBetween(1, 5) . 'jpg',
             
            'price' => $this->faker->numberBetween(150, 8500),
            'is_negotiable' => $this->faker->boolean(70),
            'stock_quantity' => $this->faker->numberBetween(1, 50),
            
            // --- KANI IMONG GI-REQUEST NGA MGA BAG-O ---
            // Kon universal, i-null nato ang oem_compatibility para limpyo
            'is_universal' => $isUniversal,
            'oem_compatibility' => $isUniversal ? null : implode(', ', $this->faker->randomElements($bikeModels, rand(1, 3))),
            'dimensions' => $this->faker->randomElement($dimOptions),
            
            'is_open_for_swap' => $isOpenForSwap,
            'swap_preferences' => $isOpenForSwap ? $this->faker->randomElement([
                'Swap to Stock + Cash',
                'Straight swap',
                'Open for swap to any compatible parts'
            ]) : null,
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->city(),
        ];
    }
}