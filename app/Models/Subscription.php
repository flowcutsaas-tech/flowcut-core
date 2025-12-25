<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
 protected $connection = 'mysql'; // ← هذا السطر فقط
    protected $fillable = [
        'user_id',
        'tenant_id',
        'plan_id',
        'price',
        'status',
        'starts_at',
        'ends_at',
        'stripe_subscription_id',
        'grace_period_until',
        'payment_retry_count',
        'suspension_reason',
        'suspended_at',
        'last_payment_failed_at',
        'failed_payment_attempts',
        'last_payment_error',
        'last_payment_attempt_at',
        'auto_renew',
        'cancel_at_period_end',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'grace_period_until' => 'datetime',
        'suspended_at' => 'datetime',
        'last_payment_failed_at' => 'datetime',
        'last_payment_attempt_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Status Check Methods
     */

    /**
     * Check if subscription is in grace period.
     * User can still access dashboard but needs to complete payment.
     */
    public function isInGracePeriod(): bool
    {
        return $this->status === 'grace_period' 
            && $this->grace_period_until 
            && now() < $this->grace_period_until;
    }

    /**
     * Check if subscription is suspended.
     * User cannot access dashboard until payment is made.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if subscription is pending payment.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Status Information Methods
     */

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'pending' => 'Pending Payment',
            'grace_period' => 'Grace Period - Payment Required',
            'suspended' => 'Suspended - Payment Required',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Get days remaining in grace period.
     * Returns null if not in grace period.
     */
    public function getDaysRemainingInGracePeriod(): ?int
    {
        if (!$this->isInGracePeriod()) {
            return null;
        }

        return $this->grace_period_until->diffInDays(now());
    }

    /**
     * Get last payment error message.
     */
    public function getLastPaymentError(): ?string
    {
        return $this->last_payment_error;
    }

    /**
     * Get number of failed payment attempts.
     */
    public function getFailedPaymentAttempts(): int
    {
        return $this->failed_payment_attempts ?? 0;
    }

    /**
     * Check if subscription can be accessed by user.
     * Returns false if suspended.
     */
    public function canBeAccessedByUser(): bool
    {
        return !$this->isSuspended();
    }

    /**
     * Check if user can retry payment.
     * Returns true if in grace period or pending.
     */
    public function canRetryPayment(): bool
    {
        return $this->isInGracePeriod() || $this->isPending();
    }

    /**
     * Get subscription expiration date.
     */
    public function getExpirationDate(): ?\DateTime
    {
        return $this->ends_at;
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->ends_at && now() > $this->ends_at;
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->ends_at);
    }
}
