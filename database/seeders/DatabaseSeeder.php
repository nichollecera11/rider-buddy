<?php

namespace Database\Seeders;

use App\Models\Mechanic;
use App\Models\Motorcycle;
use App\Models\Seller;
use App\Models\Part;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
        ]);

        // database/seeders/DatabaseSeeder.php

        User::factory()->create([
            'name' => 'Super_Admin',
            'email' => 'admin@riderbuddy.com', // Gamita ni para login
            'password' => bcrypt('password'), // Ang password
            'role' => 'admin',
            'is_active' => true,
            'is_banned' => false,
        ]);

        // 1. Paghimo og Sellers
        Seller::factory(20)->create()->each(function ($seller) {

            // 2. Kada seller, paghimo og motorcycles
            Motorcycle::factory(rand(5, 10))->create([ // Randomize nato para dili uniform tanan
                'seller_id' => $seller->id,
                'brand_id' => Brand::inRandomOrder()->first()->id, // Siguroha nga random ang brand
            ]);

            // 3. Kada seller, paghimo og parts
            Part::factory(rand(10, 20))->create([
                'seller_id' => $seller->id,
                'brand_id' => Brand::inRandomOrder()->first()->id,
                'category_id' => Category::inRandomOrder()->first()->id, // Importante ni para sa pisa
            ]);
        });

        // 4. Mechanics
        Mechanic::factory(50)->create();

        // 5. Special Rescue/Vulcanizing
        Mechanic::factory(10)->create([
            'specialization' => 'Vulcanizing',
            'is_24_7' => true,
            'offers_towing' => true,
        ]);

        //Seller Review
        $sellers = Seller::all();
        foreach ($sellers as $seller) {
            Review::factory(2)->create([
                'reviewable_id' => $seller->id,
                'reviewable_type' => Seller::class
            ]);
        }

        //Mechanics Review
        $mechanics = Mechanic::all();
        foreach ($mechanics as $mechanic) {
            Review::factory(2)->create([
                'reviewable_id' => $mechanic->id,
                'reviewable_type' => Mechanic::class
            ]);

        }

        // ... sa tumoy sa DatabaseSeeder.php

        Review::factory(100)->create()->each(function ($review) {
            // Kada review, tagaan nato og 1-3 ka random images sa images table
            $review->images()->createMany([
                ['path' => 'images/reviews/sample1.jpg', 'is_primary' => true],
                ['path' => 'images/reviews/sample2.jpg', 'is_primary' => false],
            ]);
        });// Padaghanon nato gamay para bibo


    }
}