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
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_fingerprint')->unique(); // بصمة الجهاز الفريدة
            $table->string('device_name')->nullable(); // اسم الجهاز (مثل: Chrome on Windows)
            $table->string('ip_address')->nullable(); // عنوان IP
            $table->string('user_agent')->nullable(); // معلومات المتصفح
            $table->timestamp('last_used_at')->nullable(); // آخر استخدام
            $table->timestamp('expires_at'); // تاريخ انتهاء الثقة (30 يوم)
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
