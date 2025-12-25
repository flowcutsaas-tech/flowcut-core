<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Coupon;
use App\Models\Payment;

class SubscriptionService
{
    private $plans = [
        'basic' => ['price' => 29.00, 'name' => 'Basic'],
        'professional' => ['price' => 79.00, 'name' => 'Professional'],
        'premium' => ['price' => 149.00, 'name' => 'Premium'],
    ];

    /**
     * Create a new subscription record with 'pending' status.
     */
    public function createPendingSubscription(\App\Models\User $user, string $planId): Subscription
    {
        if (!isset($this->plans[$planId])) {
            throw new \Exception('Invalid plan selected');
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'price' => $this->plans[$planId]['price'],
            'status' => 'pending',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        return $subscription;
    }



    /**
     * Get plan details.
     */
    public function getPlanDetails(string $planId): ?array
    {
        return $this->plans[$planId] ?? null;
    }

    /**
     * Get all plans.
     */
    public function getAllPlans(): array
    {
        return $this->plans;
    }
}
