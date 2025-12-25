<?php

namespace App\Services;

use App\Models\User;
use App\Models\Coupon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data): User
    {
        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'full_name' => $data['full_name'],
                'business_name' => $data['business_name'],
                'business_address' => $data['business_address'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);

            // Fire registered event for email verification
            event(new Registered($user));

            DB::commit();

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Login user and return token.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        $token = $user->createToken('auth_token')->plainTextToken;
Log::info("LOGIN ATTEMPT", [
    'email' => $email,
    'password_valid' => $user && Hash::check($password, $user->password),
    'user_found' => $user ? true : false,
    'hashed_stored' => $user ? $user->password : null,
    'password_given' => $password,
]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
