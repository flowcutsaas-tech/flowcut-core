<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantService
{
    /**
     * Create a new tenant record in the master database.
     */
    public function createTenantRecord(User $user): Tenant
    {
        $uniqueIdentifier = $this->generateUniqueIdentifier();
        $databaseName = 'tenant_' . $uniqueIdentifier;
        
        // Create tenant record
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'unique_identifier' => $uniqueIdentifier,
            'database_name' => $databaseName,
            'booking_api_key' => 'bk_' . Str::random(32),
            'dashboard_api_key' => 'dk_' . Str::random(32),
            'dashboard_url' => "https://app.yourdomain.com/{$uniqueIdentifier}/dashboard",
            'booking_url' => "https://api.yourdomain.com/book/{$uniqueIdentifier}",
            'status' => 'pending', // Status is pending until payment is successful
        ]);

        return $tenant;
    }

    /**
     * Provision the tenant (create DB, run migrations, seed data).
     */
    public function provisionTenant(Tenant $tenant): void
    {
        $databaseName = $tenant->database_name;

        Log::info("Starting tenant provisioning for {$tenant->unique_identifier}");

        // Create tenant database
        $this->createTenantDatabase($databaseName);

        // Run tenant migrations
        $this->runTenantMigrations($databaseName);

        // Seed default data
        $this->seedTenantData($databaseName);

        // Update tenant status
        $tenant->update(['status' => 'active']);

        Log::info("Tenant provisioning completed for {$tenant->unique_identifier}");
    }

    /**
     * Generate unique tenant identifier.
     */
    private function generateUniqueIdentifier(): string
    {
        do {
            $identifier = 'salon_' . rand(10000, 99999);
        } while (Tenant::where('unique_identifier', $identifier)->exists());

        return $identifier;
    }

    /**
     * Create tenant database.
     */
    private function createTenantDatabase(string $databaseName): void
    {
        $charset = config('database.connections.mysql.charset', 'utf8mb4');
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}");
    }

    /**
     * Run migrations for tenant database.
     */
    private function runTenantMigrations(string $databaseName): void
    {
        $tenantConnection = $this->configureTenantConnection($databaseName);

        $migrationPath = database_path('migrations/tenant');
        
        // Use Artisan to run migrations for a specific path and connection
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }

    /**
     * Configure tenant database connection.
     */
    private function configureTenantConnection(string $databaseName): string
    {
        config([
            'database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $databaseName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => config('database.connections.mysql.charset'),
                'collation' => config('database.connections.mysql.collation'),
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ],
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');

        return 'tenant';
    }



    /**
     * Seed default data for tenant.
     */
    // private function seedTenantData(string $databaseName): void
    // {
    //     $this->configureTenantConnection($databaseName);

    //     // Insert default settings
    //     DB::table('settings')->insert([
    //         ['key' => 'timezone', 'value' => 'Africa/Cairo', 'description' => 'Default timezone for the business', 'created_at' => now(), 'updated_at' => now()],
    //         ['key' => 'currency', 'value' => 'USD', 'description' => 'Default currency for the business', 'created_at' => now(), 'updated_at' => now()],
    //         ['key' => 'language', 'value' => 'en', 'description' => 'Default language for the business', 'created_at' => now(), 'updated_at' => now()],
    //     ]);

    //     // Reset to default connection
    //     DB::setDefaultConnection('mysql');
    // }
private function seedTenantData(string $databaseName): void
{
    try {
        // التأكد من الاتصال بقاعدة الـ tenant
        $this->configureTenantConnection($databaseName);

        Log::info('[TENANT SEED] Starting seeding', [
            'database' => $databaseName,
        ]);

        $exitCode = Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'DatabaseSeeder',
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Seeder command failed with exit code ' . $exitCode);
        }

        Log::info('[TENANT SEED] Seeding completed successfully', [
            'database' => $databaseName,
        ]);

    } catch (\Throwable $e) {
        Log::error('[TENANT SEED] Seeding failed', [
            'database' => $databaseName,
            'error' => $e->getMessage(),
        ]);

        throw $e; // مهم جدًا: يفشل الـ Job
    } finally {
        // الرجوع للقاعدة المركزية
        DB::setDefaultConnection('mysql');
    }
}

    /**
     * Switch to tenant database.
     */
    public function switchToTenant(Tenant $tenant): void
    {
        $this->configureTenantConnection($tenant->database_name);
    }

    /**
     * Switch back to master database.
     */
    public function switchToMaster(): void
    {
        DB::setDefaultConnection('mysql');
    }
}
