<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('bookings')->insert([
            [
                'booking_code'     => '6KV4J4',
                'barber_id'        => 1,
                'service_id'       => 3,
                'customer_name'    => 'xc',
                'customer_phone'   => '+32466341798',
                'appointment_date' => '2025-11-29 11:00:00',
                'status'           => 'confirmed',
                'duration_minutes' => 30,    // كان جزء من service
                'source'           => 'online',
                'reminder_sent'    => false,
                'notes'            => null,
                'created_at'       => '2025-11-29 13:39:49',
                'updated_at'       => '2025-11-29 13:40:11',
            ],
            [
                'booking_code'     => '2TT4F2',
                'barber_id'        => 2,
                'service_id'       => 3,
                'customer_name'    => 'xczxc',
                'customer_phone'   => '+32466341798',
                'appointment_date' => '2025-11-29 16:30:00',
                'status'           => 'confirmed',
                'duration_minutes' => 30,
                'source'           => 'online',
                'reminder_sent'    => false,
                'notes'            => null,
                'created_at'       => '2025-11-29 13:51:12',
                'updated_at'       => '2025-11-29 13:51:21',
            ],
            [
                'booking_code'     => 'KURTX6',
                'barber_id'        => 3,
                'service_id'       => 3,
                'customer_name'    => 'xzc44',
                'customer_phone'   => '+32466341798',
                'appointment_date' => '2025-11-30 13:30:00',
                'status'           => 'confirmed',
                'duration_minutes' => 30,
                'source'           => 'online',
                'reminder_sent'    => false,
                'notes'            => null,
                'created_at'       => '2025-11-29 13:51:51',
                'updated_at'       => '2025-11-29 13:51:58',
            ],
        ]);
    }
}
