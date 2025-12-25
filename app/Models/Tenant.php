<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'unique_identifier',
        'database_name',
        'booking_api_key',
        'dashboard_api_key',
        'dashboard_url',
        'booking_url',
        'status',
        'stripe_customer_id',
        'business_name',
        'business_address',
        'business_city',
        'business_state',
        'business_postal_code',
        'business_country',
        'business_phone',
        'business_email',
        'business_description',
        'business_logo_url',
        'profile_completed',
        'profile_completed_at',
        'profile_completion_steps',
    ];

    protected $casts = [
        'profile_completed' => 'boolean',
        'profile_completed_at' => 'datetime',
        'profile_completion_steps' => 'array',
    ];

    /**
     * Get the user that owns the tenant.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription for the tenant.
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }
// الاشتراك الذي يجب عرضه في الداش
public function currentSubscription()
{
    return $this->hasOne(Subscription::class)
        ->orderByRaw("
            CASE status
                WHEN 'active' THEN 1
                WHEN 'grace_period' THEN 2
                WHEN 'pending' THEN 3
                WHEN 'cancelled' THEN 4
                ELSE 5
            END
        ")
        ->orderByDesc('starts_at');
}

    /**
     * Check if profile is fully completed.
     */
    public function isProfileComplete(): bool
    {
        return $this->profile_completed === true;
    }

    /**
     * Get profile completion percentage.
     */
    public function getProfileCompletionPercentage(): int
    {
        $steps = $this->profile_completion_steps ?? [];
        $totalSteps = 5;
        $completedSteps = count(array_filter($steps, function($v) { return $v === true; }));
        
        return $totalSteps > 0 ? (int)(($completedSteps / $totalSteps) * 100) : 0;
    }

    /**
     * Mark a profile step as completed.
     */
    public function markProfileStepComplete(string $step): void
    {
        $steps = $this->profile_completion_steps ?? [];
        $steps[$step] = true;
        
        $completedCount = count(array_filter($steps, function($v) { return $v === true; }));
        
        $this->update([
            'profile_completion_steps' => $steps,
            'profile_completed' => $completedCount >= 5,
            'profile_completed_at' => $completedCount >= 5 ? now() : null,
        ]);
    }
}
