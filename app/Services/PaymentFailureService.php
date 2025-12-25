<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class PaymentFailureService
{
    /**
     * Handle payment failure for a subscription.
     * Implements grace period logic and retries.
     */
    public function handlePaymentFailure(Subscription $subscription, array $invoiceData): void
    {
        $failureReason = $invoiceData['last_payment_error']['message'] ?? 'Unknown error';

        // Increment failed payment attempts
        $subscription->increment('failed_payment_attempts');

        // Store the error message
        $subscription->update([
            'last_payment_error' => $failureReason,
            'last_payment_attempt_at' => now(),
        ]);

        // Get max retry attempts (configurable, default 3)
        $maxRetries = config('services.stripe.max_payment_retries', 3);

        if ($subscription->failed_payment_attempts >= $maxRetries) {
            // Suspend subscription after max retries
            $this->suspendSubscription($subscription);
        } else {
            // Put in grace period (user can still access dashboard and retry)
            $this->enableGracePeriod($subscription);
        }

        Log::warning(
            "Payment failure handled for subscription {$subscription->id}",
            [
                'attempts' => $subscription->failed_payment_attempts,
                'reason' => $failureReason,
                'status' => $subscription->status,
            ]
        );
    }

    /**
     * Enable grace period for subscription.
     * User can still access dashboard and retry payment.
     */
    public function enableGracePeriod(Subscription $subscription): void
    {
        $gracePeriodDays = config('services.stripe.grace_period_days', 7);

        $subscription->update([
            'status' => 'grace_period',
            'grace_period_until' => now()->addDays($gracePeriodDays),
        ]);

        Log::info(
            "Grace period enabled for subscription {$subscription->id}",
            ['until' => $subscription->grace_period_until]
        );
    }

    /**
     * Suspend subscription after max retries.
     * User cannot access dashboard until payment is made.
     */
    public function suspendSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        Log::warning("Subscription {$subscription->id} suspended due to payment failures");
    }

    /**
     * Reactivate subscription after successful payment.
     * Called when payment succeeds after grace period.
     */
    public function reactivateSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'failed_payment_attempts' => 0,
            'last_payment_error' => null,
            'grace_period_until' => null,
            'suspended_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        Log::info("Subscription {$subscription->id} reactivated after successful payment");
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isInGracePeriod(Subscription $subscription): bool
    {
        return $subscription->status === 'grace_period' 
            && $subscription->grace_period_until 
            && $subscription->grace_period_until->isFuture();
    }
}
