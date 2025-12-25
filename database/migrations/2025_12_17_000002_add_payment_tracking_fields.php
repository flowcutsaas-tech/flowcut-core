
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
            // Add payment tracking fields if they don't exist
            if (!Schema::hasColumn('subscriptions', 'failed_payment_attempts')) {
                $table->integer('failed_payment_attempts')->default(0)->after('status');
            }

            if (!Schema::hasColumn('subscriptions', 'last_payment_error')) {
                $table->text('last_payment_error')->nullable()->after('failed_payment_attempts');
            }

            if (!Schema::hasColumn('subscriptions', 'last_payment_attempt_at')) {
                $table->timestamp('last_payment_attempt_at')->nullable()->after('last_payment_error');
            }

            if (!Schema::hasColumn('subscriptions', 'grace_period_until')) {
                $table->timestamp('grace_period_until')->nullable()->after('last_payment_attempt_at');
            }

            if (!Schema::hasColumn('subscriptions', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('grace_period_until');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'failed_payment_attempts',
                'last_payment_error',
                'last_payment_attempt_at',
                'grace_period_until',
                'suspended_at',
            ]);
        });
    }
};
