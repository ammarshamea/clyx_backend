<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactLead;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\TenantConnectionService;

class DashboardController extends Controller
{
    public function overview()
    {
        $totalTenants  = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $totalLeads    = ContactLead::count();
        $newLeads      = ContactLead::where('status', 'new')->count();

        $activeSubscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '>', now())
            ->count();

        $expiringSubscriptions = Subscription::where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();

        $monthlyRevenue = Subscription::where('status', 'active')
            ->whereYear('starts_at', now()->year)
            ->whereMonth('starts_at', now()->month)
            ->sum('amount_paid');

        $totalRevenue = Subscription::where('status', 'active')->sum('amount_paid');

        // Aggregate stats from all tenants
        $tenants = Tenant::where('status', 'active')->get();
        $aggregatedStats = [
            'total_orders'   => 0,
            'total_revenue'  => 0,
            'total_users'    => 0,
            'total_products' => 0,
        ];

        foreach ($tenants as $tenant) {
            $stats = TenantConnectionService::getStats($tenant);
            if ($stats['connected']) {
                $aggregatedStats['total_orders']   += $stats['total_orders'] ?? 0;
                $aggregatedStats['total_revenue']  += $stats['total_revenue'] ?? 0;
                $aggregatedStats['total_users']    += $stats['total_users'] ?? 0;
                $aggregatedStats['total_products'] += $stats['total_products'] ?? 0;
            }
        }

        // Revenue trend (subscriptions by month)
        $revenueTrend = Subscription::where('status', 'active')
            ->where('starts_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(starts_at, '%Y-%m') as month, SUM(amount_paid) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'clyx' => [
                'total_tenants'         => $totalTenants,
                'active_tenants'        => $activeTenants,
                'total_leads'           => $totalLeads,
                'new_leads'             => $newLeads,
                'active_subscriptions'  => $activeSubscriptions,
                'expiring_soon'         => $expiringSubscriptions,
                'monthly_revenue'       => round($monthlyRevenue, 2),
                'total_revenue'         => round($totalRevenue, 2),
                'revenue_trend'         => $revenueTrend,
            ],
            'tenants' => $aggregatedStats,
        ]);
    }
}
