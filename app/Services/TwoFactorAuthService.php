<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret for the user.
     */
    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        
        // Store the secret temporarily (not confirmed yet)
        $user->update([
            'two_factor_secret' => $secret,
        ]);

        return $secret;
    }

    /**
     * Get QR code URL for 2FA setup.
     */
    public function getQRCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }

    /**
     * Verify the 2FA code and confirm 2FA setup.
     */
    public function confirmSetup(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        // Verify the code
        if (!$this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            return false;
        }

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();

        // Confirm 2FA
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_backup_codes' => $backupCodes,
        ]);

        return true;
    }

    /**
     * Verify a 2FA code during login.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return false;
        }

        // Check if it's a backup code
        if ($this->verifyBackupCode($user, $code)) {
            return true;
        }

        // Check TOTP code
        return $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    /**
     * Verify and use a backup code.
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        if (!$user->two_factor_backup_codes) {
            return false;
        }

        $backupCodes = $user->two_factor_backup_codes;

        if (in_array($code, $backupCodes)) {
            // Remove the used backup code
            $backupCodes = array_filter($backupCodes, fn($c) => $c !== $code);
            $user->update(['two_factor_backup_codes' => array_values($backupCodes)]);
            return true;
        }

        return false;
    }

    /**
     * Generate backup codes.
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(8);
        }
        return $codes;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Regenerate backup codes.
     */
    public function regenerateBackupCodes(User $user): array
    {
        $backupCodes = $this->generateBackupCodes();
        $user->update(['two_factor_backup_codes' => $backupCodes]);
        return $backupCodes;
    }
}
