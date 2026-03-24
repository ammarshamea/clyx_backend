<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantConnectionService;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Register the first restaurant tenant
        // Change db_driver/db_host/db_database/db_username/db_password to match your resturant DB
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'resturant-1'],
            [
                'name'        => 'Restaurant 1',
                'name_ar'     => 'المطعم الأول',
                'slug'        => 'resturant-1',
                'email'       => 'admin@restaurant1.com',
                // ── MySQL config ──────────────────────────────────────────
                'db_driver'   => 'mysql',
                'db_host'     => '127.0.0.1',
                'db_port'     => '3306',
                'db_database' => 'resturant_db',   // ← اسم قاعدة بيانات المطعم
                'db_username' => 'root',
                'db_password' => '',
                // ─────────────────────────────────────────────────────────
                'status'      => 'active',
            ]
        );

        // Reload to ensure encrypted fields are decrypted fresh
        $tenant = $tenant->fresh();
        $this->command->info("Tenant created: {$tenant->name} (slug: {$tenant->slug})");

        // Test connection
        if ($tenant->slug) {
            $connected = TenantConnectionService::test($tenant);
            $this->command->info("DB connection: " . ($connected ? '✓ Connected' : '✗ Failed — check db_database name'));
        }

        // Assign a Growth subscription
        $plan = SubscriptionPlan::where('name', 'Growth')->first();
        if ($plan) {
            Subscription::firstOrCreate(
                ['tenant_id' => $tenant->id, 'status' => 'active'],
                [
                    'subscription_plan_id' => $plan->id,
                    'billing_cycle'        => 'monthly',
                    'amount_paid'          => $plan->price_monthly,
                    'currency'             => $plan->currency,
                    'status'               => 'active',
                    'starts_at'            => now(),
                    'ends_at'              => now()->addMonth(),
                ]
            );
            $this->command->info("Subscription created: {$plan->name} plan");
        }
    }
}
