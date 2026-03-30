<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantConnectionService;

class StatsController extends Controller
{
    // Stats for a specific tenant
    public function tenant(Tenant $tenant)
    {
        $stats = TenantConnectionService::getStats($tenant);
        return response()->json([
            'tenant' => [
                'id'      => $tenant->id,
                'name'    => $tenant->name,
                'name_ar' => $tenant->name_ar,
                'slug'    => $tenant->slug,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Stats for a tenant resolved by slug (e.g. /resturant/{slug} in the dashboard).
     */
    public function bySlug(string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        return $this->tenant($tenant);
    }

    // Stats for all tenants (aggregated)
    public function all()
    {
        $tenants = Tenant::where('status', 'active')->get();
        $results = [];

        foreach ($tenants as $tenant) {
            $stats = TenantConnectionService::getStats($tenant);
            $results[] = [
                'tenant' => [
                    'id'      => $tenant->id,
                    'name'    => $tenant->name,
                    'name_ar' => $tenant->name_ar,
                    'slug'    => $tenant->slug,
                    'status'  => $tenant->status,
                ],
                'stats' => $stats,
            ];
        }

        return response()->json($results);
    }

    // Test DB connection for a tenant
    public function testConnection(Tenant $tenant)
    {
        $connected = TenantConnectionService::test($tenant);
        return response()->json(['connected' => $connected, 'tenant' => $tenant->name]);
    }
}
