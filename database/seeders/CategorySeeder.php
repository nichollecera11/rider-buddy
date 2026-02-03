<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Engine Parts', 'slug' => 'engine-parts'],
            ['name' => 'Electrical', 'slug' => 'electrical'],
            ['name' => 'Body Works', 'slug' => 'body-works'],
            ['name' => 'Tires & Rims', 'slug' => 'tires-rims'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}