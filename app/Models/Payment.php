<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'coupon_id',
        'amount',
        'discount',
        'total',
        'payment_method',
        'transaction_id',
        'status',
    ];

    /**
     * Get the subscription that owns the payment.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the coupon used in the payment.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
