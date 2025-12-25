<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use App\Services\TrustedDeviceService;

use App\Mail\TwoFactorEnabledNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TwoFactorAuthController extends Controller
{
    // protected $twoFactorService;

    // public function __construct(TwoFactorAuthService $twoFactorService)
    // {
    //     $this->twoFactorService = $twoFactorService;
    // }
    protected $twoFactorService;
protected $trustedDeviceService;

public function __construct(
    TwoFactorAuthService $twoFactorService,
    TrustedDeviceService $trustedDeviceService
) {
    $this->twoFactorService = $twoFactorService;
    $this->trustedDeviceService = $trustedDeviceService;
}


    /**
     * Generate a new 2FA secret and return QR code URL.
     */
    public function generateSecret(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Generate secret
            $secret = $this->twoFactorService->generateSecret($user);
            
            // Get QR code URL
            $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user, $secret);

            return response()->json([
                'success' => true,
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
                'message' => 'Scan this QR code with your authenticator app',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate 2FA secret: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate 2FA secret',
            ], 500);
        }
    }

    /**
     * Confirm 2FA setup with verification code.
     */
    public function confirmSetup(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        try {
            $user = Auth::user();

            // Verify and confirm
            if (!$this->twoFactorService->confirmSetup($user, $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code',
                ], 422);
            }

            // Get backup codes
            $backupCodes = $user->two_factor_backup_codes;

            // Send confirmation email with backup codes
            Mail::to($user->email)->send(new TwoFactorEnabledNotification($user, $backupCodes));

            Log::info("2FA enabled for user {$user->email}");

            return response()->json([
                'success' => true,
                'message' => '2FA has been enabled successfully',
                'backup_codes' => $backupCodes,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to confirm 2FA setup: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm 2FA setup',
            ], 500);
        }
    }

    /**
     * Verify 2FA code during login.
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $user = Auth::user();

            if (!$this->twoFactorService->verifyCode($user, $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid 2FA code',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => '2FA code verified',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify 2FA code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify 2FA code',
            ], 500);
        }
    }

    /**
     * Get 2FA status.
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'two_factor_enabled' => $user->isTwoFactorEnabled(),
            'backup_codes_remaining' => $user->two_factor_backup_codes ? count($user->two_factor_backup_codes) : 0,
        ]);
    }

    /**
     * Disable 2FA.
     */
    // public function disable(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'password' => 'required|string',
    //     ]);

    //     try {
    //         $user = Auth::user();

    //         // Verify password
    //         if (!Hash::check($request->password, $user->password)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Invalid password',
    //             ], 422);
    //         }

    //         // Disable 2FA
    //         $this->twoFactorService->disable($user);

    //         Log::info("2FA disabled for user {$user->email}");

    //         return response()->json([
    //             'success' => true,
    //             'message' => '2FA has been disabled',
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Failed to disable 2FA: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to disable 2FA',
    //         ], 500);
    //     }
    // }
public function disable(Request $request): JsonResponse
{
    $request->validate([
        'password' => 'required|string',
    ]);

    try {
        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
            ], 422);
        }

        // Disable 2FA
        $this->twoFactorService->disable($user);

        // Remove all trusted devices
        $this->trustedDeviceService->removeAllTrustedDevices($user);

        Log::info("2FA disabled and trusted devices cleared for user {$user->email}");

        return response()->json([
            'success' => true,
            'message' => '2FA disabled and trusted devices removed',
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to disable 2FA: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to disable 2FA',
        ], 500);
    }
}


    /**
     * Regenerate backup codes.
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->isTwoFactorEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => '2FA is not enabled',
                ], 422);
            }

            // Regenerate backup codes
            $backupCodes = $this->twoFactorService->regenerateBackupCodes($user);

            Log::info("Backup codes regenerated for user {$user->email}");

            return response()->json([
                'success' => true,
                'message' => 'Backup codes have been regenerated',
                'backup_codes' => $backupCodes,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate backup codes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate backup codes',
            ], 500);
        }
    }
public function verifyLogin(Request $request): JsonResponse
{
    $request->validate([
        'code' => 'required|string',
        'user_id' => 'required|integer',
    ]);

    // الحصول على المستخدم
    $user = \App\Models\User::find($request->user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid user',
        ], 404);
    }

    // Verify code (TOTP or backup)
    if (!$this->twoFactorService->verifyCode($user, $request->code)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid verification code',
        ], 422);
    }

    // Create token after 2FA success
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $user,
    ]);
}


}
