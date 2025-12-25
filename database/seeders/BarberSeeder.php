<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BarberSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('barbers')->insert([
            [
                'name' => 'Asaad',
                'email' => 'asaad@laclasse.nl',
                'phone' => '+31612345001',
                'photo_url' => 'https://i.pravatar.cc/300?img=12',
                'role' => 'barber',
                'password' => Hash::make('password123'),
                'token' => null,
                'telegram_chat_id' => "111",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Farhad',
                'email' => 'farhad@laclasse.nl',
                'phone' => '+31612345002',
                'photo_url' => 'https://i.pravatar.cc/300?img=13',
                'role' => 'barber',
                'password' => Hash::make('password123'),
                'token' => 'BARBER_3_52eb1c9ad73acbeffdd25885ab32eb50',
                'telegram_chat_id' => "222",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mohammed',
                'email' => 'mohammed@laclasse.nl',
                'phone' => '+31612345003',
                'photo_url' => 'https://i.pravatar.cc/300?img=14',
                'role' => 'admin',
                'password' => Hash::make('password123'),
                'token' => null,
                'telegram_chat_id' => "333",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
