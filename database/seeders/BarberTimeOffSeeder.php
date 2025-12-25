<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarberTimeOffSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('barber_time_off')->insert([
            [
                'barber_id'  => 1,
                'start_date' => '2025-11-25',
                'end_date'   => '2025-11-25',
                'reason'     => 'Doctor Appointment',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barber_id'  => 1,
                'start_date' => '2025-12-02',
                'end_date'   => '2025-12-02',
                'reason'     => 'Personal Day Off',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barber_id'  => 2,
                'start_date' => '2025-12-10',
                'end_date'   => '2025-12-10',
                'reason'     => 'Family Event',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barber_id'  => 3,
                'start_date' => '2025-12-15',
                'end_date'   => '2025-12-17',
                'reason'     => '3-Day Vacation',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'barber_id'  => 2,
                'start_date' => '2025-12-05',
                'end_date'   => '2025-12-07',
                'reason'     => 'xzczx1122',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
