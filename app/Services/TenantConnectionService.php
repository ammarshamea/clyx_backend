<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Exception;

class TenantConnectionService
{
    /**
     * Register dynamic DB connection for a tenant at runtime.
     */
    public static function connect(Tenant $tenant): string
    {
        $connectionName = $tenant->getTenantConnectionName();

        Config::set("database.connections.{$connectionName}", [
            'driver'    => $tenant->db_driver,
            'host'      => $tenant->db_host ?? '127.0.0.1',
            'port'      => $tenant->db_port ?? '3306',
            'database'  => $tenant->db_database,
            'username'  => $tenant->db_username ?? '',
            'password'  => $tenant->db_password ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);

        DB::purge($connectionName);

        return $connectionName;
    }

    /**
     * Test if a tenant DB connection works.
     */
    public static function test(Tenant $tenant): bool
    {
        try {
            $conn = self::connect($tenant);
            DB::connection($conn)->getPdo();
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if a table exists in the tenant DB.
     */
    public static function tableExists(string $connectionName, string $table): bool
    {
        try {
            $driver = DB::connection($connectionName)->getDriverName();
            if ($driver === 'sqlite') {
                $result = DB::connection($connectionName)
                    ->select("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
            } else {
                $result = DB::connection($connectionName)
                    ->select("SHOW TABLES LIKE ?", [$table]);
            }
            return count($result) > 0;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Safely count rows in a table (returns 0 if table doesn't exist).
     */
    private static function safeCount(string $conn, string $table, array $where = []): int
    {
        if (!self::tableExists($conn, $table)) return 0;
        try {
            $q = DB::connection($conn)->table($table);
            foreach ($where as $col => $val) {
                $q->where($col, $val);
            }
            return $q->count();
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Safely sum a column (returns 0 if table doesn't exist).
     */
    private static function safeSum(string $conn, string $table, string $col, array $where = []): float
    {
        if (!self::tableExists($conn, $table)) return 0;
        try {
            $q = DB::connection($conn)->table($table);
            foreach ($where as $k => $v) {
                $q->where($k, $v);
            }
            return (float)($q->sum($col) ?? 0);
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Laravel restaurant schema uses `total`; some schemas use `total_amount`.
     */
    private static function ordersAmountColumn(string $conn): string
    {
        if (!self::tableExists($conn, 'orders')) {
            return 'total';
        }
        try {
            if (Schema::connection($conn)->hasColumn('orders', 'total_amount')) {
                return 'total_amount';
            }
        } catch (Exception) {
            // ignore
        }
        return 'total';
    }

    /**
     * Get aggregated stats from a tenant DB.
     * Safely handles missing tables (returns 0 for each).
     */
    public static function getStats(Tenant $tenant): array
    {
        try {
            $conn = self::connect($tenant);
            DB::connection($conn)->getPdo(); // verify connection

            // Detect available tables
            $hasOrders   = self::tableExists($conn, 'orders');
            $hasUsers    = self::tableExists($conn, 'users');
            $hasProducts = self::tableExists($conn, 'products');
            $hasBranches = self::tableExists($conn, 'branches');
            $amountCol     = self::ordersAmountColumn($conn);

            // Orders stats
            $totalOrders   = $hasOrders ? DB::connection($conn)->table('orders')->count() : 0;
            $totalRevenue  = $hasOrders ? (float)(DB::connection($conn)->table('orders')->where('payment_status', 'paid')->sum($amountCol) ?? 0) : 0;
            $todayOrders   = $hasOrders ? DB::connection($conn)->table('orders')->whereDate('created_at', today())->count() : 0;
            $pendingOrders = $hasOrders ? DB::connection($conn)->table('orders')->where('status', 'pending')->count() : 0;

            // Other counts
            $users    = $hasUsers    ? DB::connection($conn)->table('users')->where('role', 'user')->count() : 0;
            $products = $hasProducts ? DB::connection($conn)->table('products')->count() : 0;
            $branches = $hasBranches ? DB::connection($conn)->table('branches')->count() : 0;

            // Revenue last 7 days
            $revenueByDay = [];
            if ($hasOrders) {
                try {
                    $driver = DB::connection($conn)->getDriverName();
                    $dateExpr = $driver === 'sqlite'
                        ? "date(created_at)"
                        : "DATE(created_at)";

                    $revenueByDay = DB::connection($conn)->table('orders')
                        ->where('payment_status', 'paid')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->selectRaw("{$dateExpr} as date, SUM({$amountCol}) as total")
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->toArray();
                } catch (Exception) {
                    $revenueByDay = [];
                }
            }

            // Orders by status
            $ordersByStatus = [];
            if ($hasOrders) {
                try {
                    $ordersByStatus = DB::connection($conn)->table('orders')
                        ->selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')
                        ->get()
                        ->toArray();
                } catch (Exception) {
                    $ordersByStatus = [];
                }
            }

            return [
                'connected'        => true,
                'total_orders'     => $totalOrders,
                'total_revenue'    => round($totalRevenue, 2),
                'today_orders'     => $todayOrders,
                'pending_orders'   => $pendingOrders,
                'total_users'      => $users,
                'total_products'   => $products,
                'total_branches'   => $branches,
                'revenue_by_day'   => $revenueByDay,
                'orders_by_status' => $ordersByStatus,
                'available_tables' => [
                    'orders'   => $hasOrders,
                    'users'    => $hasUsers,
                    'products' => $hasProducts,
                    'branches' => $hasBranches,
                ],
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error'     => $e->getMessage(),
            ];
        }
    }
}
