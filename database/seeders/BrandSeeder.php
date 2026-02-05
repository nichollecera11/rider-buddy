<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Honda', 'slug' => 'honda'],
            ['name' => 'Yamaha', 'slug' => 'yamaha'],
            ['name' => 'Suzuki', 'slug' => 'suzuki'],
            ['name' => 'Kawasaki', 'slug' => 'kawasaki'],
            ['name' => 'Rusi', 'slug' => 'rusi'],
            ['name' => 'Nwow', 'slug' => 'nwow'],
            ['name' => 'CFmoto', 'slug' => 'cfmoto'],
            ['name' => 'Bajaj', 'slug' => 'bajaj'],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}