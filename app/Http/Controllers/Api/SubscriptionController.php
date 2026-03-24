<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['tenant', 'plan'])
            ->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'              => 'required|exists:tenants,id',
            'subscription_plan_id'   => 'required|exists:subscription_plans,id',
            'billing_cycle'          => 'required|in:monthly,yearly',
            'amount_paid'            => 'required|numeric|min:0',
            'currency'               => 'string|max:10',
            'starts_at'              => 'required|date',
            'ends_at'                => 'required|date|after:starts_at',
            'payment_reference'      => 'nullable|string',
        ]);

        $validated['status'] = 'active';

        $subscription = Subscription::create($validated);
        return response()->json($subscription->load(['tenant', 'plan']), 201);
    }

    public function show(Subscription $subscription)
    {
        return response()->json($subscription->load(['tenant', 'plan']));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status'           => 'sometimes|in:active,expired,cancelled,pending',
            'ends_at'          => 'sometimes|date',
            'amount_paid'      => 'sometimes|numeric|min:0',
            'payment_reference'=> 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'cancelled') {
            $validated['cancelled_at'] = now();
        }

        $subscription->update($validated);
        return response()->json($subscription->load(['tenant', 'plan']));
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return response()->json(['message' => 'Subscription deleted.']);
    }
}
