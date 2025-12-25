<?php

namespace App\Services;

use App\Models\User;
use App\Models\TrustedDevice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrustedDeviceService
{
    /**
     * مدة الثقة بالجهاز بالأيام.
     */
    private const TRUST_DURATION_DAYS = 30;

    /**
     * توليد بصمة الجهاز من معلومات الطلب.
     */
    public function generateDeviceFingerprint(string $userAgent, string $ipAddress): string
    {
        // دمج معلومات الجهاز لإنشاء بصمة فريدة
        $fingerprint = hash('sha256', $userAgent . '|' . $ipAddress);
        return $fingerprint;
    }

    /**
     * استخراج اسم الجهاز من User Agent.
     */
    public function extractDeviceName(string $userAgent): string
    {
        // أمثلة على استخراج اسم الجهاز من User Agent
        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown Browser';
        }

        // استخراج نظام التشغيل
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } else {
            $os = 'Unknown OS';
        }

        return "{$browser} on {$os}";
    }

    /**
     * تسجيل جهاز جديد كموثوق.
     */
    public function registerTrustedDevice(
        User $user,
        string $userAgent,
        string $ipAddress
    ): TrustedDevice {
        $deviceFingerprint = $this->generateDeviceFingerprint($userAgent, $ipAddress);
        $deviceName = $this->extractDeviceName($userAgent);

        // التحقق من وجود الجهاز بالفعل
        $existingDevice = TrustedDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->first();

        if ($existingDevice && !$existingDevice->isExpired()) {
            // تحديث آخر وقت استخدام إذا كان الجهاز موثوقاً بالفعل
            $existingDevice->updateLastUsedAt();
            return $existingDevice;
        }

        // إنشاء جهاز موثوق جديد
        $trustedDevice = TrustedDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => $deviceFingerprint,
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::TRUST_DURATION_DAYS),
        ]);

        Log::info("Trusted device registered for user {$user->id}", [
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
        ]);

        return $trustedDevice;
    }

    /**
     * التحقق من أن الجهاز موثوق.
     */
    public function isTrustedDevice(User $user, string $userAgent, string $ipAddress): bool
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($userAgent, $ipAddress);

        $trustedDevice = TrustedDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('expires_at', '>', now())
            ->first();

        if ($trustedDevice) {
            // تحديث آخر وقت استخدام
            $trustedDevice->updateLastUsedAt();
            return true;
        }

        return false;
    }

    /**
     * الحصول على جميع الأجهزة الموثوقة للمستخدم.
     */
    public function getUserTrustedDevices(User $user)
    {
        // حذف الأجهزة المنتهية الصلاحية أولاً
        TrustedDevice::deleteExpiredDevices($user->id);

        return TrustedDevice::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * حذف جهاز موثوق.
     */
    public function removeTrustedDevice(User $user, int $deviceId): bool
    {
        $device = TrustedDevice::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->first();

        if (!$device) {
            return false;
        }

        $device->delete();

        Log::info("Trusted device removed for user {$user->id}", [
            'device_id' => $deviceId,
        ]);

        return true;
    }

    /**
     * حذف جميع الأجهزة الموثوقة للمستخدم.
     */
    public function removeAllTrustedDevices(User $user): int
    {
        $count = TrustedDevice::where('user_id', $user->id)->delete();

        Log::info("All trusted devices removed for user {$user->id}", [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * حذف الأجهزة المنتهية الصلاحية.
     */
    public function cleanupExpiredDevices(): int
    {
        return TrustedDevice::deleteExpiredDevices();
    }


    
}
