<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    protected $authService;
    protected $trustedDeviceService;
    protected $twoFactorService;

    public function __construct(
        AuthService $authService,
        TrustedDeviceService $trustedDeviceService,
        TwoFactorAuthService $twoFactorService
    ) {
        $this->authService = $authService;
        $this->trustedDeviceService = $trustedDeviceService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Register a new user.
     */
    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'business_address' => 'required|string|max:500',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $this->authService->register($request->all());
        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }

    /**
     * LOGIN FLOW
     *
     * 1) تحقق من الإيميل والباسورد
     * 2) تحقق هل الجهاز موثوق → bypass 2FA
     * 3) إذا 2FA مفعلة + الجهاز غير موثوق → requires_two_factor
     * 4) إذا لا يوجد 2FA → دخول طبيعي
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Step 1 — Validate credentials only
            $result = $this->authService->login($request->email, $request->password);
            $user = $result['user'];

            // Step 2 — Check if 2FA is enabled
            if (!$user->isTwoFactorEnabled()) {
                return response()->json([
                    'success' => true,
                    'requires_two_factor' => false,
                    'user' => $user,
                    'token' => $result['token'],
                ]);
            }

            // Step 3 — Check trusted device
            $userAgent = $request->header('User-Agent', '');
            $ipAddress = $request->ip();

            if ($this->trustedDeviceService->isTrustedDevice($user, $userAgent, $ipAddress)) {
                // Trusted device → bypass 2FA
                return response()->json([
                    'success' => true,
                    'requires_two_factor' => false,
                    'user' => $user,
                    'token' => $result['token'],
                ]);
            }

            // Step 4 — 2FA enabled + NOT trusted device → require code
            return response()->json([
                'success' => true,
                'requires_two_factor' => true,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
    }

    /**
     * Return authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'business_name' => $user->business_name,
                'business_address' => $user->business_address,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'tenant_id' => $user->tenant?->id,
            ],
        ]);
    }

    /**
     * Check if device is trusted.
     */
    public function checkTrustedDevice(Request $request): JsonResponse
    {
        $user = $request->user();

        $isTrusted = $this->trustedDeviceService->isTrustedDevice(
            $user,
            $request->header('User-Agent', ''),
            $request->ip()
        );

        return response()->json(['success' => true, 'is_trusted' => $isTrusted]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    /**
     * Verify 2FA code and create session.
     */
    public function verifyTwoFactorLogin(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'code' => 'required|string',
            'trust_device' => 'boolean',
        ]);

        $user = User::findOrFail($request->user_id);

        // تحقق من كود 2FA
        if (!$this->twoFactorService->verifyCode($user, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }

        // تسجيل الجهاز إذا اختار المستخدم "Trust this device"
        if ($request->boolean('trust_device')) {
            $userAgent = $request->header('User-Agent', '');
            $ip = $request->ip();

            $this->trustedDeviceService->registerTrustedDevice($user, $userAgent, $ip);
        }

        // إنشاء توكن جديد بعد نجاح 2FA
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->full_name = $request->full_name;
        $user->phone = $request->phone;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
