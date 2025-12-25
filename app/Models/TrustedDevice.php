<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_name',
        'ip_address',
        'user_agent',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * التحقق من انتهاء صلاحية الجهاز الموثوق.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * تحديث آخر وقت استخدام.
     */
    public function updateLastUsedAt(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * الحصول على أجهزة المستخدم الموثوقة والصالحة فقط.
     */
    public static function validDevices($userId)
    {
        return self::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->get();
    }

    /**
     * حذف الأجهزة المنتهية الصلاحية.
     */
    public static function deleteExpiredDevices($userId = null)
    {
        $query = self::where('expires_at', '<=', now());
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->delete();
    }
}
