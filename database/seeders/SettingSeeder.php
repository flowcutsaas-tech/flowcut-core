<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('settings')->insert([
            [
                'key' => 'booking_advance_days',
                'value' => '14',
                'description' => 'Number of days customers can book in advance',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'booking_min_notice_hours',
                'value' => '2',
                'description' => 'Minimum hours notice required before appointment',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'temp_lock_duration_minutes',
                'value' => '3',
                'description' => 'Minutes for temporary booking lock',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'reminder_hours_before',
                'value' => '3',
                'description' => 'Hours before appointment to send reminder',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'admin_phone',
                'value' => '+31612345000',
                'description' => 'Admin phone for notifications',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'whatsapp_business_phone',
                'value' => '+31612345000',
                'description' => 'WhatsApp Business main number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'whatsapp_access_token',
                'value' => '',
                'description' => 'WhatsApp Cloud API access token',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'whatsapp_phone_number_id',
                'value' => '',
                'description' => 'WhatsApp phone number ID',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'whatsapp_verify_token',
                'value' => 'laclasse_webhook_verify_token',
                'description' => 'Webhook verification token',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'time_slot_interval_minutes',
                'value' => '15',
                'description' => 'Time slot interval in minutes',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
