<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientTypeController extends Controller
{
    // Public: for landing page
    public function landingIndex()
    {
        return response()->json(
            ClientType::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    // Protected: dashboard CRUD (apiResource)
    public function index(Request $request)
    {
        return response()->json(
            ClientType::orderBy('sort_order')->orderBy('id')->paginate($request->get('per_page', 15))
        );
    }

    public function show(ClientType $clientType)
    {
        return response()->json($clientType);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label_en'   => 'required|string|max:255',
            'label_ar'   => 'nullable|string|max:255',
            'image'      => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        $validated['image'] = $request->file('image')->store('client-types', 'public');
        $clientType = ClientType::create($validated);
        return response()->json($clientType, 201);
    }

    public function update(Request $request, ClientType $clientType)
    {
        $validated = $request->validate([
            'label_en'   => 'sometimes|string|max:255',
            'label_ar'   => 'nullable|string|max:255',
            'image'      => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $path = $clientType->getRawOriginal('image');
            if ($path && !str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
            $validated['image'] = $request->file('image')->store('client-types', 'public');
        }

        $clientType->update($validated);
        return response()->json($clientType);
    }

    public function destroy(ClientType $clientType)
    {
        $path = $clientType->getRawOriginal('image');
        if ($path && !str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
        $clientType->delete();
        return response()->json(['message' => 'Client type deleted.']);
    }
}
