<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    public function index()
    {
        return response()->json(SocialLink::orderBy('sort_order')->orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform'   => 'required|string|in:instagram,twitter,linkedin,youtube,facebook,tiktok',
            'url'        => 'required|url|max:500',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        $link = SocialLink::create($validated);
        return response()->json($link, 201);
    }

    public function show(SocialLink $socialLink)
    {
        return response()->json($socialLink);
    }

    public function update(Request $request, SocialLink $socialLink)
    {
        $validated = $request->validate([
            'platform'   => 'sometimes|string|in:instagram,twitter,linkedin,youtube,facebook,tiktok',
            'url'        => 'sometimes|url|max:500',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        $socialLink->update($validated);
        return response()->json($socialLink);
    }

    public function destroy(SocialLink $socialLink)
    {
        $socialLink->delete();
        return response()->json(['message' => 'Social link deleted.']);
    }
}
