<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactLead;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // Public: receive contact form from Landing Page
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $validated['ip_address'] = $request->ip();

        $lead = ContactLead::create($validated);

        return response()->json([
            'message' => 'Your message has been received. We\'ll be in touch soon.',
            'id'      => $lead->id,
        ], 201);
    }

    // Admin: list all leads
    public function index(Request $request)
    {
        $query = ContactLead::latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    // Admin: update lead status
    public function update(Request $request, ContactLead $contactLead)
    {
        $request->validate(['status' => 'required|in:new,read,replied,archived']);
        $contactLead->update(['status' => $request->status]);
        return response()->json($contactLead);
    }

    public function destroy(ContactLead $contactLead)
    {
        $contactLead->delete();
        return response()->json(['message' => 'Lead deleted.']);
    }
}
