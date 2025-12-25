<?php

namespace App\Http\Controllers;

use App\Models\TrustedDevice;
use App\Services\TrustedDeviceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TrustedDeviceController extends Controller
{
    protected $trustedDeviceService;

    public function __construct(TrustedDeviceService $trustedDeviceService)
    {
        $this->trustedDeviceService = $trustedDeviceService;
        $this->middleware('auth:api');
    }

    /**
     * الحصول على جميع الأجهزة الموثوقة للمستخدم الحالي.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $devices = $this->trustedDeviceService->getUserTrustedDevices($request->user());

            return response()->json([
                'success' => true,
                'data' => $devices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trusted devices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف جهاز موثوق محدد.
     */
    public function destroy(Request $request, int $deviceId): JsonResponse
    {
        try {
            $removed = $this->trustedDeviceService->removeTrustedDevice(
                $request->user(),
                $deviceId
            );

            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Trusted device removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove trusted device',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف جميع الأجهزة الموثوقة.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        try {
            $count = $this->trustedDeviceService->removeAllTrustedDevices($request->user());

            return response()->json([
                'success' => true,
                'message' => "Removed {$count} trusted device(s)",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove trusted devices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
