<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Grace period fields for handling failed payments
            $table->dateTime('grace_period_until')->nullable()->comment('Date until which subscription is in grace period after payment failure');
            $table->integer('payment_retry_count')->default(0)->comment('Number of payment retry attempts');
            $table->string('suspension_reason')->nullable()->comment('Reason for subscription suspension');
            $table->dateTime('suspended_at')->nullable()->comment('Date when subscription was suspended');
            $table->dateTime('last_payment_failed_at')->nullable()->comment('Date of last failed payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'grace_period_until',
                'payment_retry_count',
                'suspension_reason',
                'suspended_at',
                'last_payment_failed_at',
            ]);
        });
    }
};
