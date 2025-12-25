<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Send password reset link to email.
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $this->passwordResetService->sendResetLink($request->email);

            // Always return success for security (don't reveal if email exists)
            return response()->json([
                'success' => true,
                'message' => 'If an account exists with this email, a password reset link has been sent.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send password reset link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link',
            ], 500);
        }
    }

    /**
     * Verify reset token.
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $isValid = $this->passwordResetService->isTokenValid($request->token);

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify reset token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify token',
            ], 500);
        }
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $success = $this->passwordResetService->resetPassword($request->token, $request->password);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
            ], 500);
        }
    }
}
