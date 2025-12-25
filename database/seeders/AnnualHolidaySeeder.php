<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnualHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('annual_holidays')->insert([
            [
                'start_date' => '2025-01-01',
                'end_date'   => '2025-01-01',
                'reason'     => 'New Year Holiday',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'start_date' => '2025-05-01',
                'end_date'   => '2025-05-01',
                'reason'     => 'Labour Day',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'start_date' => '2025-12-24',
                'end_date'   => '2025-12-26',
                'reason'     => 'Christmas Holiday',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'start_date' => '2025-07-20',
                'end_date'   => '2025-07-21',
                'reason'     => 'Eid Holiday',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
