<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TrustedDeviceService;

class CheckTrustedDevice
{
    protected $trustedDeviceService;

    public function __construct(TrustedDeviceService $trustedDeviceService)
    {
        $this->trustedDeviceService = $trustedDeviceService;
    }

    /**
     * التحقق من أن الجهاز موثوق قبل طلب 2FA.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // إذا لم يكن هناك مستخدم موثق بالفعل، اسمح بالمرور
        if (!$user) {
            return $next($request);
        }

        // التحقق من أن الجهاز موثوق
        $userAgent = $request->header('User-Agent', '');
        $ipAddress = $request->ip();

        $isTrusted = $this->trustedDeviceService->isTrustedDevice($user, $userAgent, $ipAddress);

        // إضافة معلومة إلى الطلب لاستخدامها لاحقاً
        $request->attributes->set('is_trusted_device', $isTrusted);

        return $next($request);
    }
}
