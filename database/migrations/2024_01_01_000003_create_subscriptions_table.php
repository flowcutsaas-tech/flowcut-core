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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // مرحلة 1: اشتراك مرتبط بالمستخدم
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // مرحلة 2: بعد الدفع يتم ربطه بالتينانت
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('plan_id', ['basic', 'professional', 'premium']);
            $table->decimal('price', 10, 2)->nullable();

            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();

            $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('pending');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
