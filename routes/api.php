<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientTypeController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectCategoryController;
use App\Http\Controllers\Api\ContactInfoController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CLYX Backend API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'app'    => config('app.name'),
    ]));

    // ── Public: Contact form from Landing Page ──────────────────────────
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:10,1');

    // Public: Subscription plans (for landing page pricing page)
    Route::get('/plans', [SubscriptionPlanController::class, 'index']);

    // Public: Projects & Client Types (for landing page)
    Route::get('/projects', [ProjectController::class, 'landingIndex']);
    Route::get('/projects/{slug}', [ProjectController::class, 'showBySlug'])->where('slug', '[a-z0-9\-]+');
    Route::get('/project-categories', [ProjectCategoryController::class, 'publicIndex']);
    Route::get('/client-types', [ClientTypeController::class, 'landingIndex']);

    // Public: Contact info & Social links (for landing page)
    Route::get('/contact-info', [SiteSettingsController::class, 'contactInfo']);
    Route::get('/social-links', [SiteSettingsController::class, 'socialLinks']);

    // ── Auth ─────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // ── Protected: Dashboard ─────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Overview stats
        Route::get('/dashboard', [DashboardController::class, 'overview']);

        // Tenants
        Route::apiResource('tenants', TenantController::class);
        Route::get('/tenants/{tenant}/test-connection', [TenantController::class, 'testConnection']);

        // Subscription plans (admin management)
        Route::apiResource('subscription-plans', SubscriptionPlanController::class);

        // Subscriptions
        Route::apiResource('subscriptions', SubscriptionController::class);

        // Tenant statistics (reads from tenant DB)
        Route::get('/stats', [StatsController::class, 'all']);
        Route::get('/stats/{tenant}', [StatsController::class, 'tenant']);
        Route::get('/stats/{tenant}/test', [StatsController::class, 'testConnection']);

        // Contact leads
        Route::get('/leads', [ContactController::class, 'index']);
        Route::patch('/leads/{contactLead}', [ContactController::class, 'update']);
        Route::delete('/leads/{contactLead}', [ContactController::class, 'destroy']);

        // Users (super_admin only in practice — enforce in middleware)
        Route::apiResource('users', UserController::class);

        // Projects (dashboard CRUD)
        Route::apiResource('admin/projects', ProjectController::class);

        Route::apiResource('admin/project-categories', ProjectCategoryController::class);

        // Client Types (dashboard CRUD)
        Route::apiResource('admin/client-types', ClientTypeController::class);

        // Contact & Social settings (apiResource)
        Route::apiResource('admin/contact-info', ContactInfoController::class)->only(['index', 'show', 'update']);
        Route::apiResource('admin/social-links', SocialLinkController::class);
    });
});
