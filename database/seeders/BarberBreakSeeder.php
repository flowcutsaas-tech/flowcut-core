<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarberBreakSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $data = [
            // Barber 1
            [1, 0, '13:00', '14:00', 'Lunch break', 1, 3],
            [1, 1, '13:00', '14:00', 'Lunch break', 0, 3],
            [1, 2, '13:00', '14:00', 'Lunch break', 1, 3],
            [1, 3, '13:00', '14:00', 'Lunch break', 1, 0],
            [1, 4, '13:00', '14:00', 'Lunch break', 1, 0],
            [1, 5, '13:00', '14:00', 'Lunch break', 1, 0],
            [1, 6, '13:00', '14:00', 'Lunch break', 1, 0],

            // Barber 2
            [2, 0, '13:00', '14:00', 'Lunch break', 1, 0],
            [2, 1, '13:00', '14:00', 'Lunch break', 0, 0],
            [2, 2, '13:00', '14:00', 'Lunch break', 1, 0],
            [2, 3, '13:00', '14:00', 'Lunch break', 1, 0],
            [2, 4, '13:00', '14:00', 'Lunch break', 1, 0],
            [2, 5, '13:00', '14:00', 'Lunch break', 1, 0],
            [2, 6, '13:00', '14:00', 'Lunch break', 1, 0],

            // Barber 3
            [3, 0, '13:00', '14:00', 'Lunch break', 1, 0],
            [3, 1, '13:00', '14:00', 'Lunch break', 0, 0],
            [3, 2, '13:00', '14:00', 'Lunch break', 1, 0],
            [3, 3, '13:00', '14:00', 'Lunch break', 1, 0],
            [3, 4, '13:00', '14:00', 'Lunch break', 1, 0],
            [3, 5, '13:00', '14:00', 'Lunch break', 1, 0],
            [3, 6, '13:00', '14:00', 'Lunch break', 1, 0],
        ];

        foreach ($data as $item) {
            DB::table('barber_breaks')->insert([
                'barber_id'        => $item[0],
                'day_of_week'      => $item[1],
                'start_time'       => $item[2],
                'end_time'         => $item[3],
                'reason'           => $item[4],
                'is_active'        => $item[5],
                'updated_by_admin' => $item[6],
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}
