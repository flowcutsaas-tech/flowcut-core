<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantService;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
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
        $apiKey = $request->header('X-API-Key');
        $tenantIdentifier = $request->route('tenant');

        if (!$apiKey || !$tenantIdentifier) {
            return response()->json(['message' => 'API Key or Tenant Identifier missing.'], 401);
        }

        $tenant = Tenant::where('unique_identifier', $tenantIdentifier)
                        ->where('booking_api_key', $apiKey)
                        ->first();

        if (!$tenant) {
            return response()->json(['message' => 'Invalid API Key or Tenant Identifier.'], 403);
        }

        if ($tenant->status !== 'active') {
            return response()->json(['message' => 'Tenant subscription is not active.'], 403);
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
