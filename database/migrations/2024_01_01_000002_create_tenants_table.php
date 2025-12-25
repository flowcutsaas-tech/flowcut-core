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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('unique_identifier')->unique();
            $table->string('database_name');
            $table->string('booking_api_key')->unique();
            $table->string('dashboard_api_key')->unique();
            $table->string('dashboard_url');
            $table->string('booking_url');
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
