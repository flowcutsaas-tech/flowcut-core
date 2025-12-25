<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriptionId;
    protected $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $subscriptionId, string $sessionId)
    {
        $this->subscriptionId = $subscriptionId;
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(TenantService $tenantService): void
    {
        $subscription = Subscription::find($this->subscriptionId);
        if (!$subscription) {
            Log::error("CreateTenantJob failed: Subscription ID {$this->subscriptionId} not found.");
            return;
        }

        $user = $subscription->user;
        $planId = $subscription->plan_id;

        Log::info("Attempting to create tenant for user: {$user->email} with plan: {$planId}");

        try {
            // 1. Create Tenant Record
            $tenant = $tenantService->createTenantRecord($user);
            
            // Link the newly created tenant to the subscription
            $subscription->tenant_id = $tenant->id;
            $subscription->save();

            // 2. Create Tenant Database and Run Migrations
            $tenantService->provisionTenant($tenant);

            // 3. Activate Subscription for FREE_COUPON_SUBSCRIPTION case
            if ($this->sessionId === 'FREE_COUPON_SUBSCRIPTION') {
                $subscription->update([
                    'stripe_subscription_id' => $this->sessionId,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]);
            }

            Log::info("Tenant created successfully for user: {$user->email}. Tenant ID: {$tenant->id}");

        } catch (\Exception $e) {
            Log::error("Failed to create tenant for user: {$user->email}. Error: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
