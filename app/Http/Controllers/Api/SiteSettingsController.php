<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use App\Models\SocialLink;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    // Public: for landing page
    public function contactInfo()
    {
        return response()->json(ContactInfo::getSingleton());
    }

    public function socialLinks()
    {
        return response()->json(
            SocialLink::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    // Protected: dashboard
    public function getContactInfo()
    {
        return response()->json(ContactInfo::getSingleton());
    }

    public function updateContactInfo(Request $request)
    {
        $validated = $request->validate([
            'email'      => 'required|email|max:255',
            'phone'      => 'required|string|max:50',
            'address'    => 'nullable|string|max:500',
            'address_ar' => 'nullable|string|max:500',
        ]);

        $info = ContactInfo::getSingleton();
        $info->update($validated);
        return response()->json($info);
    }

    public function indexSocialLinks()
    {
        return response()->json(SocialLink::orderBy('sort_order')->orderBy('id')->get());
    }

    public function storeSocialLink(Request $request)
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

    public function updateSocialLink(Request $request, SocialLink $socialLink)
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

    public function destroySocialLink(SocialLink $socialLink)
    {
        $socialLink->delete();
        return response()->json(['message' => 'Social link deleted.']);
    }
}
