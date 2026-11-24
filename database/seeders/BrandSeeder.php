<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // Japanese
            ['name' => 'Honda', 'slug' => 'honda'],
            ['name' => 'Yamaha', 'slug' => 'yamaha'],
            ['name' => 'Suzuki', 'slug' => 'suzuki'],
            ['name' => 'Kawasaki', 'slug' => 'kawasaki'],

            // European
            ['name' => 'Aprilia', 'slug' => 'aprilia'],
            ['name' => 'Benelli', 'slug' => 'benelli'],
            ['name' => 'Beta', 'slug' => 'beta'],
            ['name' => 'Bimota', 'slug' => 'bimota'],
            ['name' => 'Brixton', 'slug' => 'brixton'],
            ['name' => 'Ducati', 'slug' => 'ducati'],
            ['name' => 'Fantic', 'slug' => 'fantic'],
            ['name' => 'GasGas', 'slug' => 'gasgas'],
            ['name' => 'Husqvarna', 'slug' => 'husqvarna'],
            ['name' => 'KTM', 'slug' => 'ktm'],
            ['name' => 'Lambretta', 'slug' => 'lambretta'],
            ['name' => 'Moto Guzzi', 'slug' => 'moto-guzzi'],
            ['name' => 'Moto Morini', 'slug' => 'moto-morini'],
            ['name' => 'MV Agusta', 'slug' => 'mv-agusta'],
            ['name' => 'Norton', 'slug' => 'norton'],
            ['name' => 'Peugeot Motocycles', 'slug' => 'peugeot-motocycles'],
            ['name' => 'Piaggio', 'slug' => 'piaggio'],
            ['name' => 'Rieju', 'slug' => 'rieju'],
            ['name' => 'Royal Enfield', 'slug' => 'royal-enfield'],
            ['name' => 'SWM', 'slug' => 'swm'],
            ['name' => 'Triumph', 'slug' => 'triumph'],
            ['name' => 'Vespa', 'slug' => 'vespa'],

            // American and Canadian
            ['name' => 'Harley-Davidson', 'slug' => 'harley-davidson'],
            ['name' => 'Indian Motorcycle', 'slug' => 'indian-motorcycle'],
            ['name' => 'Buell', 'slug' => 'buell'],
            ['name' => 'Zero Motorcycles', 'slug' => 'zero-motorcycles'],
            ['name' => 'LiveWire', 'slug' => 'livewire'],
            ['name' => 'Can-Am', 'slug' => 'can-am'],
            ['name' => 'Ryvid', 'slug' => 'ryvid'],

            // Indian
            ['name' => 'Bajaj', 'slug' => 'bajaj'],
            ['name' => 'Hero', 'slug' => 'hero'],
            ['name' => 'TVS', 'slug' => 'tvs'],
            ['name' => 'Ather', 'slug' => 'ather'],
            ['name' => 'Ola Electric', 'slug' => 'ola-electric'],
            ['name' => 'Revolt Motors', 'slug' => 'revolt-motors'],
            ['name' => 'Ultraviolette', 'slug' => 'ultraviolette'],
            ['name' => 'Tork Motors', 'slug' => 'tork-motors'],
            ['name' => 'Matter', 'slug' => 'matter'],

            // Chinese
            ['name' => 'CFMoto', 'slug' => 'cfmoto'],
            ['name' => 'Benda', 'slug' => 'benda'],
            ['name' => 'Zontes', 'slug' => 'zontes'],
            ['name' => 'Voge', 'slug' => 'voge'],
            ['name' => 'QJMotor', 'slug' => 'qjmotor'],
            ['name' => 'Lifan', 'slug' => 'lifan'],
            ['name' => 'Loncin', 'slug' => 'loncin'],
            ['name' => 'Zongshen', 'slug' => 'zongshen'],
            ['name' => 'Kove', 'slug' => 'kove'],
            ['name' => 'Kayo', 'slug' => 'kayo'],
            ['name' => 'Keeway', 'slug' => 'keeway'],
            ['name' => 'Znen', 'slug' => 'znen'],

            // Taiwanese
            ['name' => 'Kymco', 'slug' => 'kymco'],
            ['name' => 'SYM', 'slug' => 'sym'],
            ['name' => 'PGO', 'slug' => 'pgo'],
            ['name' => 'Gogoro', 'slug' => 'gogoro'],

            // Electric motorcycles and scooters
            ['name' => 'NIU', 'slug' => 'niu'],
            ['name' => 'Yadea', 'slug' => 'yadea'],
            ['name' => 'Vmoto', 'slug' => 'vmoto'],
            ['name' => 'Super Soco', 'slug' => 'super-soco'],
            ['name' => 'Horwin', 'slug' => 'horwin'],
            ['name' => 'Surron', 'slug' => 'surron'],
            ['name' => 'Tromox', 'slug' => 'tromox'],
            ['name' => 'Silence', 'slug' => 'silence'],
            ['name' => 'Energica', 'slug' => 'energica'],
            ['name' => 'Maeving', 'slug' => 'maeving'],
            ['name' => 'Damon', 'slug' => 'damon'],

            // Southeast Asian and Philippine market
            ['name' => 'Rusi', 'slug' => 'rusi'],
            ['name' => 'Nwow', 'slug' => 'nwow'],
            ['name' => 'Motorstar', 'slug' => 'motorstar'],
            ['name' => 'Skygo', 'slug' => 'skygo'],
            ['name' => 'Racal', 'slug' => 'racal'],
            ['name' => 'GPX', 'slug' => 'gpx'],
            ['name' => 'Viar', 'slug' => 'viar'],
            ['name' => 'Gesits', 'slug' => 'gesits'],

            // Other international brands
            ['name' => 'BSA', 'slug' => 'bsa'],
            ['name' => 'Jawa', 'slug' => 'jawa'],
            ['name' => 'AJS', 'slug' => 'ajs'],
            ['name' => 'Ariel', 'slug' => 'ariel'],
            ['name' => 'Mash', 'slug' => 'mash'],
            ['name' => 'Italjet', 'slug' => 'italjet'],
            ['name' => 'Felo', 'slug' => 'felo'],
            ['name' => 'Electric Motion', 'slug' => 'electric-motion'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'deleted_at' => null,
                ]
            );
        }
    }
}
