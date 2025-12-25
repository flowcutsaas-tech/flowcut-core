<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TenantProfileController;
use App\Http\Controllers\TrustedDeviceController;
use App\Http\Controllers\TwoFactorAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('/signup', [AuthController::class, 'signup']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/2fa/verify-login', [\App\Http\Controllers\TwoFactorAuthController::class, 'verifyLogin']);

        // Email Verification Routes
        Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\VerificationController::class, 'verify'])
            ->middleware(['signed'])
            ->name('verification.verify');

        Route::post('/email/resend', [\App\Http\Controllers\VerificationController::class, 'resend'])
            ->middleware(['auth:sanctum', 'throttle:6,1'])
            ->name('verification.send');

        Route::post('/2fa/verify-login', [AuthController::class, 'verifyTwoFactorLogin']);

    });

    // Password Reset Routes (Public)
    Route::prefix('password')->group(function () {
        Route::post('/forgot', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,15');
        Route::post('/verify-token', [PasswordResetController::class, 'verifyToken']);
        Route::post('/reset', [PasswordResetController::class, 'resetPassword']);
    });

    // Checkout routes
    Route::get('/plans', [CheckoutController::class, 'getPlans']);
    Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon']);
    Route::post('/checkout/session', [CheckoutController::class, 'createCheckoutSession'])->middleware(['auth:sanctum', 'verified']);

    // Stripe Webhook (Public, no auth)
    Route::post('/webhook/stripe', [StripeWebhookController::class, 'handleWebhook']);

    // Protected routes
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/check-trusted-device', [AuthController::class, 'checkTrustedDevice']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
        });

        // Two-Factor Authentication Routes
        Route::prefix('2fa')->group(function () {
            Route::post('/generate', [TwoFactorAuthController::class, 'generateSecret']);
            Route::post('/confirm', [TwoFactorAuthController::class, 'confirmSetup']);
            Route::post('/verify', [TwoFactorAuthController::class, 'verifyCode']);
            Route::get('/status', [TwoFactorAuthController::class, 'getStatus']);
            Route::post('/disable', [TwoFactorAuthController::class, 'disable']);
            Route::post('/backup-codes/regenerate', [TwoFactorAuthController::class, 'regenerateBackupCodes']);

            // Trusted Devices Routes
            Route::get('/trusted-devices', [TrustedDeviceController::class, 'index']);
            Route::delete('/trusted-devices/{deviceId}', [TrustedDeviceController::class, 'destroy']);
            Route::delete('/trusted-devices', [TrustedDeviceController::class, 'destroyAll']);
        });

        // Tenant Profile Routes
        Route::prefix('tenant/profile')->group(function () {
            Route::get('/', [TenantProfileController::class, 'show']);
            Route::put('/', [TenantProfileController::class, 'update']);
            Route::get('/completion-status', [TenantProfileController::class, 'getCompletionStatus']);
        });
        Route::post('/tenant/profile', [TenantProfileController::class, 'update']);

        Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'show']);
        Route::get('/subscriptions/history', [\App\Http\Controllers\SubscriptionController::class, 'history']);
        Route::get('/api-keys', [\App\Http\Controllers\SubscriptionController::class, 'apiKeys']);
        Route::get('/invoices', [\App\Http\Controllers\SubscriptionController::class, 'getInvoices']);
        // 🔒 Routes تحتاج Tenant فعلي
        Route::middleware('tenant')->group(function () {

            Route::post('/subscription/portal', [\App\Http\Controllers\SubscriptionController::class, 'createPortalSession']);

            Route::post('/api-keys/regenerate', [\App\Http\Controllers\SubscriptionController::class, 'regenerateApiKeys']);

            Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index']);
            Route::put('settings', [\App\Http\Controllers\SettingController::class, 'update']);
        });
        Route::post('/subscription/cancel-auto-renew', [\App\Http\Controllers\SubscriptionController::class, 'cancelAutoRenew']);
        Route::post('/subscription/enable-auto-renew', [\App\Http\Controllers\SubscriptionController::class, 'enableAutoRenew']);

        Route::post('/subscription/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel']);

        Route::get(
  '/invoices/subscription/{stripeSubscriptionId}',
  [\App\Http\Controllers\SubscriptionController::class, 'invoicesBySubscription']
);


    });
});
