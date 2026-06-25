<?php

namespace Database\Seeders;

use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $cars = [
            [
                'brand' => 'Toyota',
                'model' => 'Avanza',
                'plate_number' => 'B 1234 ABC',
                'year' => 2022,
                'color' => 'Putih',
                'price_per_day' => 250000,
                'status' => 'available'
            ],
            [
                'brand' => 'Honda',
                'model' => 'CR-V',
                'plate_number' => 'D 5678 EFG',
                'year' => 2023,
                'color' => 'Hitam',
                'price_per_day' => 550000,
                'status' => 'available'
            ],
            [
                'brand' => 'Suzuki',
                'model' => 'Ertiga',
                'plate_number' => 'L 9012 HIJ',
                'year' => 2021,
                'color' => 'Silver',
                'price_per_day' => 300000,
                'status' => 'available'
            ],
            [
                'brand' => 'Mitsubishi',
                'model' => 'Xpander',
                'plate_number' => 'N 3456 KLM',
                'year' => 2023,
                'color' => 'Hitam',
                'price_per_day' => 400000,
                'status' => 'maintenance'
            ]
        ];
        
        foreach ($cars as $car) {
            Car::create($car);
        }
    }
}