<?php

namespace Database\Seeders;

use App\Models\Mechanic;
use App\Models\Motorcycle;
use App\Models\Seller;
use App\Models\Part;
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

    // 2. Paghimo og 10 ka Sellers
   Seller::factory(20)->create()->each(function ($seller) {
        
        // 3. Kada seller, paghimo og 3 ka motorcycles
        Motorcycle::factory(5)->create([
            'seller_id' => $seller->id,
        ]);

        // 4. Kada seller, paghimo sab og 5 ka parts
        Part::factory(10)->create([
            'seller_id' => $seller->id,
        ]);
    });

     Mechanic::factory(50)->create();

    }
}