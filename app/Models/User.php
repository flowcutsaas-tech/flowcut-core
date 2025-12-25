<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'business_name',
        'business_address',
        'email',
        'phone',
        'password',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_backup_codes',
        'two_factor_confirmed_at',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_backup_codes',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'two_factor_backup_codes' => 'array',
        'password' => 'hashed',
    ];

    /**
     * A user can have many subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * A user eventually has one tenant (after payment).
     */
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * Check if 2FA is enabled and confirmed.
     */
    public function isTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Check if password reset token is valid.
     */
    public function isPasswordResetTokenValid(): bool
    {
        return $this->password_reset_token && $this->password_reset_expires_at && now() < $this->password_reset_expires_at;
    }
}
