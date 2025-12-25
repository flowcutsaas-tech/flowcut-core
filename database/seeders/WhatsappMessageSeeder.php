<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsappMessageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('whatsapp_messages')->insert([
            [
                'booking_id'      => 1,
                'barber_id'       => null,
                'phone_number'    => '+32466341798',
                'message_type'    => 'outgoing',
                'message_content' => 'Booking created with code: 6KV4J4',
                'status'          => 'pending',
                'response_json'   => null,
                'created_at'      => '2025-11-29 13:39:49',
                'updated_at'      => '2025-11-29 13:39:49',
            ],
            [
                'booking_id'      => null,
                'barber_id'       => null,
                'phone_number'    => '+32466341798',
                'message_type'    => 'incoming',
                'message_content' => "Hello! I would like to book an appointment:\n\nBooking Code: 6KV4J4\nBarber: Asaad\nService: Beard Trim and Haircut\nDate: Saturday, November 29, 2025\nTime: 11:00\nName: xc\nPhone: +32466341798",
                'status'          => 'delivered',
                'response_json'   => json_encode([
                    'wamid' => 'wamid.HBgLMzI0NjYzNDE3OTgVAgASGBQzRjkyOTVERjA3Q0Y3NTdEMzc4RgA='
                ]),
                'created_at'      => '2025-11-29 13:40:11',
                'updated_at'      => '2025-11-29 13:40:11',
            ],
            [
                'booking_id'      => null,
                'barber_id'       => null,
                'phone_number'    => '+32466341798',
                'message_type'    => 'outgoing',
                'message_content' => "✅ Booking Confirmed!\n\nCode: 6KV4J4\nName: xc\nBarber: Asaad\nService: Beard Trim and Haircut\nDate & Time: Saturday, November 29, 2025 at 11:00",
                'status'          => 'sent',
                'response_json'   => null,
                'created_at'      => '2025-11-29 13:40:12',
                'updated_at'      => '2025-11-29 13:40:12',
            ],
        ]);
    }
}
