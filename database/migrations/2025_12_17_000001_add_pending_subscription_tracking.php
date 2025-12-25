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
            // تتبع محاولات الدفع الفاشلة
            $table->integer('failed_payment_attempts')->default(0)->after('payment_retry_count');
            $table->dateTime('last_payment_attempt_at')->nullable()->after('last_payment_failed_at');
            $table->text('last_payment_error')->nullable()->after('last_payment_attempt_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // تتبع الاشتراك المعلق (في حالة فشل الدفع)
            // $table->unsignedBigInteger('pending_subscription_id')->nullable()->after('tenant_id');
            $table->unsignedBigInteger('pending_subscription_id')->nullable();
            $table->foreign('pending_subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pending_subscription_id']);
            $table->dropColumn('pending_subscription_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['failed_payment_attempts', 'last_payment_attempt_at', 'last_payment_error']);
        });
    }
};
