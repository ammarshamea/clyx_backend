<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesTaskContent;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkProject;
use App\Services\NotificationService;
use App\Services\TaskWorkflowService;
use App\Services\WorkProjectPermissionService;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    use ManagesTaskContent;

    public function __construct(
        protected NotificationService $notifications,
        protected TaskWorkflowService $workflow,
        protected WorkProjectPermissionService $permissions,
    ) {}

    protected function staffTaskQuery(Request $request)
    {
        $userId = $request->user()->id;

        return Task::with(['workProject:id,name', 'assignees:id,name'])
            ->whereHas('workProject')
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
            'tasks_total'          => (clone $base)->count(),
            'tasks_in_progress'    => (clone $base)->where('status', 'in_progress')->count(),
            'tasks_review'         => (clone $base)->where('status', 'review')->count(),
            'tasks_overdue'        => (clone $base)->where('is_overdue', true)->where('status', '!=', 'done')->count(),
            'tasks_due_today'      => (clone $base)->whereDate('due_date', $today)->where('status', '!=', 'done')->count(),
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
        $user = $request->user();
        $userId = $user->id;

        $projects = WorkProject::with(['members:id,name,email,role'])
            ->withCount('tasks')
            ->where(function ($q) use ($userId) {
                $q->whereHas('members', fn ($m) => $m->where('users.id', $userId))
                    ->orWhereHas('tasks.assignees', fn ($a) => $a->where('users.id', $userId));
            })
            ->latest()
            ->paginate(20);

        $projects->getCollection()->transform(function ($project) use ($user) {
            $project->setAttribute('my_permissions', $this->permissions->memberPermissions($user, $project));

            return $project;
        });

        return response()->json($projects);
    }

    public function storeTask(Request $request, WorkProject $workProject)
    {
        if (!$this->permissions->isMember($request->user(), $workProject)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$this->permissions->can($request->user(), $workProject, 'can_create_tasks')) {
            return response()->json(['message' => 'You do not have permission to create tasks in this project.'], 403);
        }

        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'nullable|in:low,medium,high,critical',
            'due_date'    => 'nullable|date',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
        ];

        $validated = $request->validate($rules);

        if (!empty($validated['assignee_ids'])
            && !$this->permissions->can($request->user(), $workProject, 'can_assign_tasks')) {
            return response()->json(['message' => 'You do not have permission to assign tasks.'], 403);
        }

        $task = Task::create([
            'work_project_id' => $workProject->id,
            'title'           => $validated['title'],
            'description'     => $validated['description'] ?? null,
            'status'          => 'todo',
            'priority'        => $validated['priority'] ?? 'medium',
            'progress'        => 0,
            'due_date'        => $validated['due_date'] ?? null,
            'created_by'      => $request->user()->id,
            'updated_by'      => $request->user()->id,
        ]);

        if (!empty($validated['assignee_ids'])) {
            $sync = [];
            foreach ($validated['assignee_ids'] as $uid) {
                $sync[$uid] = ['assigned_by' => $request->user()->id];
            }
            $task->assignees()->sync($sync);

            foreach (User::whereIn('id', $validated['assignee_ids'])->get() as $assignee) {
                $this->notifications->notify(
                    $assignee,
                    'task_assigned',
                    'تم تكليفك بمهمة جديدة',
                    "المهمة: {$task->title} — المشروع: {$workProject->name}",
                    ['task_id' => $task->id, 'work_project_id' => $workProject->id],
                );
            }
        }

        $workProject->recalculateProgress();

        foreach (User::where('role', 'super_admin')->where('is_active', true)->get() as $admin) {
            $this->notifications->notify(
                $admin,
                'task_created',
                'مهمة جديدة من موظف',
                "{$request->user()->name} أنشأ «{$task->title}» في {$workProject->name}",
                ['task_id' => $task->id],
            );
        }

        return response()->json($task->load(['workProject', 'assignees']), 201);
    }

    public function showTask(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $task->load([
            'workProject.members:id,name,email,role',
            'assignees:id,name,email',
            'comments.user:id,name,role',
            'attachments.user:id,name',
        ]);

        if ($task->workProject && $task->workProject->relationLoaded('members')) {
            $task->workProject->setAttribute(
                'members',
                $task->workProject->members->map(fn ($m) => $this->permissions->formatMember($m))->values()
            );
        }

        $task->setAttribute(
            'my_permissions',
            $this->permissions->memberPermissions($request->user(), $task->workProject)
        );

        return response()->json($task);
    }

    public function updateTask(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $task->loadMissing('workProject');
        $canEditDetails = $this->permissions->can($request->user(), $task->workProject, 'can_edit_task_details');

        $rules = [
            'progress' => 'sometimes|integer|min:0|max:100',
            'status'   => 'sometimes|in:todo,in_progress,blocked,review',
        ];

        if ($canEditDetails) {
            $rules['title'] = 'sometimes|string|max:255';
            $rules['description'] = 'nullable|string';
            $rules['priority'] = 'sometimes|in:low,medium,high,critical';
            $rules['due_date'] = 'nullable|date';
        }

        $validated = $request->validate($rules);

        $before = $task->progress;
        $workflowData = collect($validated)->only(['progress', 'status'])->filter()->all();

        if (!empty($workflowData)) {
            $task = $this->workflow->applyStaffUpdate($task, $request->user(), $workflowData);
        }

        if ($canEditDetails) {
            $detailFields = collect($validated)->only(['title', 'description', 'priority', 'due_date'])->filter()->all();
            if (!empty($detailFields)) {
                $task->update([...$detailFields, 'updated_by' => $request->user()->id]);
            }
        }

        $admins = User::where('role', 'super_admin')->where('is_active', true)->get();
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

        return response()->json($task->fresh(['workProject', 'assignees']));
    }

    public function destroyTask(Request $request, Task $task)
    {
        if (!$task->userCanAccess($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $task->loadMissing('workProject');
        if (!$this->permissions->can($request->user(), $task->workProject, 'can_delete_tasks')) {
            return response()->json(['message' => 'You do not have permission to delete tasks.'], 403);
        }

        $project = $task->workProject;
        $task->delete();
        $project->recalculateProgress();

        return response()->json(['message' => 'Task deleted.']);
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

        foreach (User::where('role', 'super_admin')->where('is_active', true)->get() as $admin) {
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

    public function updateComment(Request $request, Task $task, TaskComment $comment)
    {
        $response = $this->performUpdateComment($request, $task, $comment);
        $this->notifyCommentUpdated($request->user(), $task, $comment->fresh()->body);

        return $response;
    }

    public function destroyComment(Request $request, Task $task, TaskComment $comment)
    {
        return $this->performDeleteComment($request, $task, $comment);
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

        foreach (User::where('role', 'super_admin')->where('is_active', true)->get() as $admin) {
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

    public function renameAttachment(Request $request, Task $task, TaskAttachment $attachment)
    {
        return $this->performRenameAttachment($request, $task, $attachment);
    }

    public function replaceAttachment(Request $request, Task $task, TaskAttachment $attachment)
    {
        return $this->performReplaceAttachment($request, $task, $attachment);
    }

    public function destroyAttachment(Request $request, Task $task, TaskAttachment $attachment)
    {
        return $this->performDeleteAttachment($request, $task, $attachment);
    }
}
