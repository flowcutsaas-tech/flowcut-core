<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarberScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Get all barbers from DB
        $barbers = DB::table('barbers')->pluck('id');

        foreach ($barbers as $barberId) {

            // Create schedule for 7 days (0 to 6)
            for ($day = 0; $day <= 6; $day++) {

                DB::table('barber_schedules')->insert([
                    'barber_id'    => $barberId,
                    'day_of_week'  => $day,
                    'start_time'   => '10:00:00',
                    'end_time'     => '18:00:00',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
