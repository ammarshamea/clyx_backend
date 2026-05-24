<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientTypeController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectCategoryController;
use App\Http\Controllers\Api\ContactInfoController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\StaffDashboardController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CLYX Backend API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'app'    => config('app.name'),
    ]));

    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('/plans', [SubscriptionPlanController::class, 'index']);
    Route::get('/projects', [ProjectController::class, 'landingIndex']);
    Route::get('/projects/{slug}', [ProjectController::class, 'showBySlug'])->where('slug', '[a-z0-9\-]+');
    Route::get('/project-categories', [ProjectCategoryController::class, 'publicIndex']);
    Route::get('/client-types', [ClientTypeController::class, 'landingIndex']);
    Route::get('/contact-info', [SiteSettingsController::class, 'contactInfo']);
    Route::get('/social-links', [SiteSettingsController::class, 'socialLinks']);

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {

        // Notifications (all authenticated roles)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

        // Staff dashboard
        Route::middleware('role:staff')->prefix('staff')->group(function () {
            Route::get('/overview', [StaffDashboardController::class, 'overview']);
            Route::get('/tasks', [StaffDashboardController::class, 'tasks']);
            Route::get('/work-projects', [StaffDashboardController::class, 'workProjects']);
            Route::post('/work-projects/{work_project}/tasks', [StaffDashboardController::class, 'storeTask']);
            Route::get('/tasks/{task}', [StaffDashboardController::class, 'showTask']);
            Route::patch('/tasks/{task}', [StaffDashboardController::class, 'updateTask']);
            Route::delete('/tasks/{task}', [StaffDashboardController::class, 'destroyTask']);
            Route::post('/tasks/{task}/comments', [StaffDashboardController::class, 'addComment']);
            Route::patch('/tasks/{task}/comments/{comment}', [StaffDashboardController::class, 'updateComment']);
            Route::delete('/tasks/{task}/comments/{comment}', [StaffDashboardController::class, 'destroyComment']);
            Route::post('/tasks/{task}/attachments', [StaffDashboardController::class, 'uploadAttachment']);
            Route::patch('/tasks/{task}/attachments/{attachment}', [StaffDashboardController::class, 'renameAttachment']);
            Route::post('/tasks/{task}/attachments/{attachment}/replace', [StaffDashboardController::class, 'replaceAttachment']);
            Route::delete('/tasks/{task}/attachments/{attachment}', [StaffDashboardController::class, 'destroyAttachment']);
        });

        // Super admin: CMS + task management
        Route::middleware('super_admin')->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'overview']);

            Route::apiResource('tenants', TenantController::class);
            Route::get('/tenants/{tenant}/test-connection', [TenantController::class, 'testConnection']);

            Route::apiResource('subscription-plans', SubscriptionPlanController::class);
            Route::apiResource('subscriptions', SubscriptionController::class);

            Route::get('/stats', [StatsController::class, 'all']);
            Route::get('/stats/slug/{slug}', [StatsController::class, 'bySlug']);
            Route::get('/stats/{tenant}', [StatsController::class, 'tenant']);
            Route::get('/stats/{tenant}/test', [StatsController::class, 'testConnection']);

            Route::get('/leads', [ContactController::class, 'index']);
            Route::patch('/leads/{contactLead}', [ContactController::class, 'update']);
            Route::delete('/leads/{contactLead}', [ContactController::class, 'destroy']);

            Route::apiResource('users', UserController::class);

            Route::apiResource('admin/projects', ProjectController::class);
            Route::apiResource('admin/project-categories', ProjectCategoryController::class);
            Route::apiResource('admin/client-types', ClientTypeController::class);
            Route::apiResource('admin/contact-info', ContactInfoController::class)->only(['index', 'show', 'update']);
            Route::apiResource('admin/social-links', SocialLinkController::class);

            // Task management
            Route::get('/tasks/dashboard', [WorkProjectController::class, 'dashboard']);
            Route::get('/tasks', [TaskController::class, 'index']);
            Route::post('/tasks', [TaskController::class, 'store']);
            Route::get('/tasks/{task}', [TaskController::class, 'show']);
            Route::put('/tasks/{task}', [TaskController::class, 'update']);
            Route::patch('/tasks/{task}', [TaskController::class, 'update']);
            Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
            Route::post('/tasks/{task}/assign', [TaskController::class, 'assign']);
            Route::post('/tasks/{task}/approve', [TaskController::class, 'approve']);
            Route::post('/tasks/{task}/request-changes', [TaskController::class, 'requestChanges']);
            Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment']);
            Route::patch('/tasks/{task}/comments/{comment}', [TaskController::class, 'updateComment']);
            Route::delete('/tasks/{task}/comments/{comment}', [TaskController::class, 'destroyComment']);
            Route::post('/tasks/{task}/attachments', [TaskController::class, 'uploadAttachment']);
            Route::patch('/tasks/{task}/attachments/{attachment}', [TaskController::class, 'renameAttachment']);
            Route::post('/tasks/{task}/attachments/{attachment}/replace', [TaskController::class, 'replaceAttachment']);
            Route::delete('/tasks/{task}/attachments/{attachment}', [TaskController::class, 'destroyAttachment']);

            Route::apiResource('work-projects', WorkProjectController::class);
            Route::post('/work-projects/{work_project}/tasks', [TaskController::class, 'store']);
        });
    });
});
