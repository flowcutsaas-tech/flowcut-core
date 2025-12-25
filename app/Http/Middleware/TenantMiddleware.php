<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    protected $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        if ($tenant->status !== 'active') {
            return response()->json(['message' => 'Tenant subscription is not active. Please complete payment.'], 403);
        }

        // Switch to the tenant's database
        $this->tenantService->switchToTenant($tenant);

        // Store tenant object in request for easy access
        $request->attributes->add(['tenant' => $tenant]);

        $response = $next($request);

        // Switch back to the master database after the request
        $this->tenantService->switchToMaster();

        return $response;
    }
}
