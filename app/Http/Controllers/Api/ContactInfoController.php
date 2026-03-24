<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use Illuminate\Http\Request;

class ContactInfoController extends Controller
{
    public function index()
    {
        return response()->json([ContactInfo::getSingleton()]);
    }

    public function show(ContactInfo $contactInfo)
    {
        return response()->json($contactInfo);
    }

    public function update(Request $request, ContactInfo $contactInfo)
    {
        $validated = $request->validate([
            'email'      => 'sometimes|email|max:255',
            'phone'      => 'sometimes|string|max:50',
            'whatsapp'   => 'nullable|string|max:50',
            'address'    => 'nullable|string|max:500',
            'address_ar' => 'nullable|string|max:500',
        ]);

        $contactInfo->update($validated);
        return response()->json($contactInfo);
    }
}
