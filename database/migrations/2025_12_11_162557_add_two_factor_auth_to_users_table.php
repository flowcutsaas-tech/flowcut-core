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
        Schema::table('users', function (Blueprint $table) {
            // Two-Factor Authentication fields
            $table->boolean('two_factor_enabled')->default(false)->comment('Whether 2FA is enabled for this user');
            $table->string('two_factor_secret')->nullable()->comment('TOTP secret for 2FA');
            $table->string('two_factor_backup_codes')->nullable()->comment('Backup codes for 2FA (JSON array)');
            $table->dateTime('two_factor_confirmed_at')->nullable()->comment('When 2FA was confirmed');
            
            // Password reset fields
            $table->string('password_reset_token')->nullable()->unique()->comment('Token for password reset');
            $table->dateTime('password_reset_expires_at')->nullable()->comment('When the password reset token expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_backup_codes',
                'two_factor_confirmed_at',
                'password_reset_token',
                'password_reset_expires_at',
            ]);
        });
    }
};
