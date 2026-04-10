<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientType;
use App\Models\ContactLead;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\SocialLink;

class DashboardController extends Controller
{
    /**
     * Landing-page CMS statistics only (portfolio, leads, contact — no tenants/subscriptions).
     */
    public function overview()
    {
        return response()->json([
            'landing' => [
                'projects_total'           => Project::count(),
                'project_categories_total' => ProjectCategory::count(),
                'client_types_total'       => ClientType::count(),
                'contact_leads_total'      => ContactLead::count(),
                'contact_leads_new'        => ContactLead::where('status', 'new')->count(),
                'social_links_total'       => SocialLink::count(),
            ],
        ]);
    }
}
