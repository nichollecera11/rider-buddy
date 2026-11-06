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
            ['name' => 'CFMoto', 'slug' => 'cfmoto'],
            ['name' => 'Bajaj', 'slug' => 'bajaj'],
        ];

        foreach ($brands as $brand) {
            Brand::withTrashed()->updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'deleted_at' => null,
                ]
            );
        }
    }
}
