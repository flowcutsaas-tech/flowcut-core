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
        Schema::table('tenants', function (Blueprint $table) {
            // Business/Salon Information
            $table->string('business_name')->nullable()->comment('Name of the barbershop/salon');
            $table->string('business_address')->nullable()->comment('Physical address of the business');
            $table->string('business_city')->nullable()->comment('City of the business');
            $table->string('business_state')->nullable()->comment('State/Province of the business');
            $table->string('business_postal_code')->nullable()->comment('Postal code of the business');
            $table->string('business_country')->nullable()->comment('Country of the business');
            $table->string('business_phone')->nullable()->comment('Phone number of the business');
            $table->string('business_email')->nullable()->comment('Email of the business');
            $table->text('business_description')->nullable()->comment('Description of the business');
            $table->string('business_logo_url')->nullable()->comment('URL to the business logo');
            
            // Profile Completion Status
            $table->boolean('profile_completed')->default(false)->comment('Whether the profile is fully completed');
            $table->dateTime('profile_completed_at')->nullable()->comment('When the profile was completed');
            $table->json('profile_completion_steps')->nullable()->comment('Tracking which steps have been completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
