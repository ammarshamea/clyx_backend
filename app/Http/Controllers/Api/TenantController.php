<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('activeSubscription.plan')
            ->withCount('subscriptions')
            ->latest()
            ->paginate(15);
        return response()->json($tenants);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('subscriptions.plan');
        return response()->json($tenant);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'name_ar'     => 'nullable|string|max:255',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string',
            'db_driver'   => 'required|in:mysql,sqlite,pgsql,mariadb',
            'db_host'     => 'nullable|string',
            'db_port'     => 'nullable|string',
            'db_database' => 'required|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'status'      => 'in:active,inactive,suspended',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure slug uniqueness
        $base = $validated['slug'];
        $i = 1;
        while (Tenant::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $base . '-' . $i++;
        }

        $tenant = Tenant::create($validated);

        return response()->json($tenant, 201);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'name_ar'     => 'nullable|string|max:255',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string',
            'db_driver'   => 'sometimes|in:mysql,sqlite,pgsql,mariadb',
            'db_host'     => 'nullable|string',
            'db_port'     => 'nullable|string',
            'db_database' => 'sometimes|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'status'      => 'in:active,inactive,suspended',
        ]);

        $tenant->update($validated);

        return response()->json($tenant);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return response()->json(['message' => 'Tenant deleted.']);
    }

    public function testConnection(Tenant $tenant)
    {
        $connected = TenantConnectionService::test($tenant);
        return response()->json(['connected' => $connected]);
    }
}
