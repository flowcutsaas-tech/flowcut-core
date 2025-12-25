<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;

class SubscriptionValidationService
{
    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(User $user): bool
    {
        return $user->subscriptions()
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get the active subscription for the user
     */
    public function getActiveSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get pending subscription (payment failed)
     */
    public function getPendingSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->where('status', 'pending')
            ->orWhere('status', 'payment_failed')
            ->latest()
            ->first();
    }

    /**
     * Check if user can purchase a new subscription
     */
    public function canPurchaseNewSubscription(User $user): array
    {
        $activeSubscription = $this->getActiveSubscription($user);
        
        if ($activeSubscription) {
            return [
                'can_purchase' => false,
                'reason' => 'active_subscription',
                'message' => 'You already have an active subscription. Please cancel your current subscription first.',
                'current_subscription' => [
                    'plan_id' => $activeSubscription->plan_id,
                    'status' => $activeSubscription->status,
                    'ends_at' => $activeSubscription->ends_at,
                ]
            ];
        }

        return [
            'can_purchase' => true,
            'reason' => null,
            'message' => 'You can purchase a new subscription'
        ];
    }

    /**
     * Check if user has a pending payment (payment failed)
     */
    public function hasPendingPayment(User $user): array
    {
        $pendingSubscription = $this->getPendingSubscription($user);

        if ($pendingSubscription) {
            return [
                'has_pending' => true,
                'subscription_id' => $pendingSubscription->id,
                'plan_id' => $pendingSubscription->plan_id,
                'price' => $pendingSubscription->price,
                'failed_attempts' => $pendingSubscription->failed_payment_attempts,
                'last_error' => $pendingSubscription->last_payment_error,
                'message' => 'You have a pending purchase. You can complete it now.'
            ];
        }

        return [
            'has_pending' => false,
            'message' => 'No pending purchases'
        ];
    }

    /**
     * Mark subscription as payment failed
     */
    public function markPaymentFailed(Subscription $subscription, string $errorMessage): void
    {
        $subscription->update([
            'status' => 'payment_failed',
            'failed_payment_attempts' => ($subscription->failed_payment_attempts ?? 0) + 1,
            'last_payment_failed_at' => now(),
            'last_payment_error' => $errorMessage,
        ]);

        // Link pending subscription to user
        $subscription->user->update([
            'pending_subscription_id' => $subscription->id
        ]);
    }

    /**
     * Mark subscription as payment successful
     */
    public function markPaymentSuccessful(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'failed_payment_attempts' => 0,
            'last_payment_error' => null,
        ]);

        // Remove pending subscription from user
        $subscription->user->update([
            'pending_subscription_id' => null
        ]);
    }
}
