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
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}