<?php

namespace App\Services;

use App\Models\User;
use App\Mail\PasswordResetNotification;
use App\Mail\PasswordResetConfirmationNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PasswordResetService
{
    /**
     * Token expiration time in hours.
     */
    private const TOKEN_EXPIRATION_HOURS = 24;

    /**
     * Generate a password reset token and send email.
     */
    public function sendResetLink(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Don't reveal if email exists for security
            Log::warning("Password reset requested for non-existent email: {$email}");
            return true;
        }

        // Generate unique token
        $token = Str::random(64);
        $expiresAt = now()->addHours(self::TOKEN_EXPIRATION_HOURS);

        // Store token in database
        $user->update([
            'password_reset_token' => $token,
            'password_reset_expires_at' => $expiresAt,
        ]);

        // Send email
        try {
            Mail::to($user->email)->send(new PasswordResetNotification($user, $token));
            Log::info("Password reset email sent to {$user->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Verify the reset token and reset the password.
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = User::where('password_reset_token', $token)->first();

        if (!$user) {
            Log::warning("Invalid password reset token");
            return false;
        }

        // Check if token is expired
        if (!$user->isPasswordResetTokenValid()) {
            Log::warning("Password reset token expired for user {$user->email}");
            return false;
        }

        // Update password and clear token
        $user->update([
            'password' => bcrypt($newPassword),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        Log::info("Password reset successfully for user {$user->email}");

        // Send confirmation email
        try {
            Mail::to($user->email)->send(new PasswordResetConfirmationNotification($user));
        } catch (\Exception $e) {
            Log::error("Failed to send password reset confirmation: {$e->getMessage()}");
        }

        return true;
    }

    /**
     * Check if a token is valid.
     */
    public function isTokenValid(string $token): bool
    {
        $user = User::where('password_reset_token', $token)->first();

        if (!$user) {
            return false;
        }

        return $user->isPasswordResetTokenValid();
    }

    /**
     * Get user by reset token.
     */
    public function getUserByToken(string $token): ?User
    {
        $user = User::where('password_reset_token', $token)->first();

        if ($user && $user->isPasswordResetTokenValid()) {
            return $user;
        }

        return null;
    }
}
