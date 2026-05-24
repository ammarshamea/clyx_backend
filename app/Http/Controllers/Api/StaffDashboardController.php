<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Task;
use App\Models\WorkProject;
use App\Services\NotificationService;
use App\Services\TaskWorkflowService;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
        protected TaskWorkflowService $workflow,
    ) {}

    protected function staffTaskQuery(Request $request)
    {
        $userId = $request->user()->id;

        return Task::with(['workProject:id,name', 'assignees:id,name'])
            ->where(function ($q) use ($userId) {
                $q->whereHas('assignees', fn ($a) => $a->where('users.id', $userId))
                    ->orWhereHas('workProject.members', fn ($m) => $m->where('users.id', $userId));
            });
    }

    public function overview(Request $request)
    {
        $base = $this->staffTaskQuery($request);
        $today = now()->toDateString();

        return response()->json([
            'tasks_total'       => (clone $base)->count(),
            'tasks_in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'tasks_review'      => (clone $base)->where('status', 'review')->count(),
            'tasks_overdue'     => (clone $base)->where('is_overdue', true)->where('status', '!=', 'done')->count(),
            'tasks_due_today'   => (clone $base)->whereDate('due_date', $today)->where('status', '!=', 'done')->count(),
            'recent_notifications' => AppNotification::where('user_id', $request->user()->id)
                ->latest()->limit(5)->get(),
        ]);
    }

    public function tasks(Request $request)
    {
        $query = $this->staffTaskQuery($request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->boolean('overdue')) {
            $query->where('is_overdue', true)->where('status', '!=', 'done');
        }
        if ($request->query('filter') === 'today') {
            $query->whereDate('due_date', now()->toDateString());
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function workProjects(Request $request)
    {
        $userId = $request->user()->id;

        $projects = WorkProject::with(['members:id,name'])
            ->withCount('tasks')
            ->where(function ($q) use ($userId) {
                $q->whereHas('members', fn ($m) => $m->where('users.id', $userId))
                    ->orWhereHas('tasks.assignees', fn ($a) => $a->where('users.id', $userId));
            })
            ->latest()
            ->paginate(20);

        return response()->json($projects);
    }

    public function showTask(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $task->load([
            'workProject',
            'assignees:id,name,email',
            'comments.user:id,name,role',
            'attachments.user:id,name',
        ]);

        return response()->json($task);
    }

    public function updateTask(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'progress' => 'sometimes|integer|min:0|max:100',
            'status'   => 'sometimes|in:todo,in_progress,blocked,review',
        ]);

        $before = $task->progress;
        $task = $this->workflow->applyStaffUpdate($task, $request->user(), $validated);

        $admins = \App\Models\User::where('role', 'super_admin')->where('is_active', true)->get();
        foreach ($admins as $admin) {
            $this->notifications->notify(
                $admin,
                'task_updated',
                'تحديث على مهمة',
                "{$request->user()->name} حدّث «{$task->title}» إلى {$task->progress}%",
                ['task_id' => $task->id],
            );
        }

        if (($validated['progress'] ?? $before) >= 100 || $task->status === 'review') {
            foreach ($admins as $admin) {
                $this->notifications->notify(
                    $admin,
                    'task_review_requested',
                    'مهمة بانتظار المراجعة',
                    "{$task->title} جاهزة للمراجعة.",
                    ['task_id' => $task->id],
                );
            }
        }

        return response()->json($task);
    }

    public function addComment(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate(['body' => 'required|string|max:5000']);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
            'type'    => 'comment',
        ]);

        foreach (\App\Models\User::where('role', 'super_admin')->where('is_active', true)->get() as $admin) {
            $this->notifications->notify(
                $admin,
                'comment_added',
                'تعليق من موظف',
                "{$request->user()->name}: {$validated['body']}",
                ['task_id' => $task->id],
            );
        }

        return response()->json($comment->load('user:id,name,role'), 201);
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate(['file' => 'required|file|max:10240']);
        $file = $request->file('file');
        $path = $file->store('task-attachments', 'public');

        $attachment = $task->attachments()->create([
            'user_id'       => $request->user()->id,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        foreach (\App\Models\User::where('role', 'super_admin')->where('is_active', true)->get() as $admin) {
            if ($admin->id !== $request->user()->id) {
                $this->notifications->notify(
                    $admin,
                    'attachment_uploaded',
                    'مرفق جديد',
                    "{$request->user()->name} رفع ملفاً على {$task->title}",
                    ['task_id' => $task->id],
                );
            }
        }

        return response()->json($attachment->load('user:id,name'), 201);
    }
}
