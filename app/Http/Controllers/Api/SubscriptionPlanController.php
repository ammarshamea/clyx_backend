<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        return response()->json(SubscriptionPlan::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'name_ar'               => 'nullable|string|max:255',
            'description'           => 'nullable|string',
            'description_ar'        => 'nullable|string',
            'price_monthly'         => 'required|numeric|min:0',
            'price_yearly'          => 'required|numeric|min:0',
            'currency'              => 'string|max:10',
            'max_branches'          => 'nullable|integer|min:0',
            'max_products'          => 'nullable|integer|min:0',
            'max_orders_per_month'  => 'nullable|integer|min:0',
            'features'              => 'nullable|array',
            'is_active'             => 'boolean',
            'sort_order'            => 'integer',
        ]);

        $plan = SubscriptionPlan::create($validated);
        return response()->json($plan, 201);
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return response()->json($subscriptionPlan->load('subscriptions'));
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validate([
            'name'                  => 'sometimes|string|max:255',
            'name_ar'               => 'nullable|string|max:255',
            'description'           => 'nullable|string',
            'description_ar'        => 'nullable|string',
            'price_monthly'         => 'sometimes|numeric|min:0',
            'price_yearly'          => 'sometimes|numeric|min:0',
            'max_branches'          => 'nullable|integer|min:0',
            'max_products'          => 'nullable|integer|min:0',
            'max_orders_per_month'  => 'nullable|integer|min:0',
            'features'              => 'nullable|array',
            'is_active'             => 'boolean',
            'sort_order'            => 'integer',
        ]);

        $subscriptionPlan->update($validated);
        return response()->json($subscriptionPlan);
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();
        return response()->json(['message' => 'Plan deleted.']);
    }
}
