<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('services')->insert([
            [
                'name' => 'Haircut',
                'description' => 'Classic men haircut with styling',
                'duration_minutes' => 30,
                'price_amount' => 2500,  // same as old system
                'price_currency' => 'EUR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beard Trim',
                'description' => 'Professional beard trimming and shaping',
                'duration_minutes' => 15,
                'price_amount' => 1700,
                'price_currency' => 'EUR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beard Trim and Haircut',
                'description' => 'Complete haircut and beard grooming service',
                'duration_minutes' => 45,
                'price_amount' => 3800,
                'price_currency' => 'EUR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kids Haircut (up to 11 years)',
                'description' => 'Haircut for children aged 11 and under',
                'duration_minutes' => 15,
                'price_amount' => 1700,
                'price_currency' => 'EUR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wash and Haircut',
                'description' => 'Hair wash followed by haircut and styling',
                'duration_minutes' => 30,
                'price_amount' => 2500,
                'price_currency' => 'EUR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
