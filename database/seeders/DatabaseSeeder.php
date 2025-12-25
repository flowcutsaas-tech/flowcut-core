<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BarberSeeder::class,
            ServiceSeeder::class,
            BarberScheduleSeeder::class,
            SettingSeeder::class,
            AnnualHolidaySeeder::class,     // NEW
            BarberBreakSeeder::class,       // NEW
            BarberTimeOffSeeder::class,     // NEW
            BookingSeeder::class,           // NEW
            WhatsappMessageSeeder::class,   // NEW
        ]);
    }
}
