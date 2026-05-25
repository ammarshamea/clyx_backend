<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesTaskContent;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkProject;
use App\Services\NotificationService;
use App\Services\TaskWorkflowService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ManagesTaskContent;

    public function __construct(
        protected NotificationService $notifications,
        protected TaskWorkflowService $workflow,
    ) {}

    public function index(Request $request)
    {
        $query = Task::with(['workProject:id,name', 'assignees:id,name,email', 'creator:id,name'])
            ->whereHas('workProject')
            ->latest();

        if ($request->filled('work_project_id')) {
            $query->where('work_project_id', $request->work_project_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('assignee_id')) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $request->assignee_id));
        }
        if ($request->boolean('overdue')) {
            $query->where('is_overdue', true)->where('status', '!=', 'done');
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request, WorkProject $workProject = null)
    {
        $validated = $request->validate([
            'work_project_id' => $workProject ? 'nullable' : 'required|exists:work_projects,id',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:todo,in_progress,blocked,review,done',
            'priority'        => 'nullable|in:low,medium,high,critical',
            'progress'        => 'nullable|integer|min:0|max:100',
            'due_date'        => 'nullable|date',
            'assignee_ids'    => 'nullable|array',
            'assignee_ids.*'  => 'exists:users,id',
        ]);

        $projectId = $workProject?->id ?? $validated['work_project_id'];

        $task = Task::create([
            'work_project_id' => $projectId,
            'title'           => $validated['title'],
            'description'     => $validated['description'] ?? null,
            'status'          => $validated['status'] ?? 'todo',
            'priority'        => $validated['priority'] ?? 'medium',
            'progress'        => $validated['progress'] ?? 0,
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
                    "المهمة: {$task->title} — المشروع: {$task->workProject->name}",
                    ['task_id' => $task->id, 'work_project_id' => $projectId],
                );
            }
        }

        $task->workProject->recalculateProgress();

        return response()->json($task->load(['workProject', 'assignees']), 201);
    }

    public function show(Task $task)
    {
        $task->load([
            'workProject.members',
            'assignees:id,name,email,role',
            'creator:id,name',
            'comments.user:id,name,role',
            'comments.replyTo',
            'comments.replyTo.user:id,name,role',
            'attachments.user:id,name',
        ]);

        $perms = app(\App\Services\WorkProjectPermissionService::class);
        if ($task->workProject && $task->workProject->relationLoaded('members')) {
            $task->workProject->setAttribute(
                'members',
                $task->workProject->members->map(fn ($m) => $perms->formatMember($m))->values()
            );
        }

        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'sometimes|in:todo,in_progress,blocked,review,done',
            'priority'    => 'sometimes|in:low,medium,high,critical',
            'progress'    => 'sometimes|integer|min:0|max:100',
            'due_date'    => 'nullable|date',
            'assignee_ids'=> 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        $task->update(collect($validated)->except('assignee_ids')->toArray());
        $task->update(['updated_by' => $request->user()->id]);

        if (array_key_exists('assignee_ids', $validated)) {
            $sync = [];
            foreach ($validated['assignee_ids'] ?? [] as $uid) {
                $sync[$uid] = ['assigned_by' => $request->user()->id];
            }
            $task->assignees()->sync($sync);
        }

        $task->workProject->recalculateProgress();

        return response()->json($task->fresh(['workProject', 'assignees']));
    }

    public function destroy(Task $task)
    {
        $project = $task->workProject;
        $task->delete();
        $project->recalculateProgress();

        return response()->json(['message' => 'Task deleted.']);
    }

    public function assign(Request $request, Task $task)
    {
        $validated = $request->validate([
            'assignee_ids'   => 'required|array|min:1',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        $sync = [];
        foreach ($validated['assignee_ids'] as $uid) {
            $sync[$uid] = ['assigned_by' => $request->user()->id];
        }
        $task->assignees()->sync($sync);

        foreach (User::whereIn('id', $validated['assignee_ids'])->get() as $assignee) {
            $this->notifications->notify(
                $assignee,
                'task_assigned',
                'تم تكليفك بمهمة',
                "المهمة: {$task->title}",
                ['task_id' => $task->id],
            );
        }

        return response()->json($task->fresh(['assignees']));
    }

    public function approve(Request $request, Task $task)
    {
        $task = $this->workflow->approve($task, $request->user());

        foreach ($task->assignees as $assignee) {
            $this->notifications->notify(
                $assignee,
                'task_approved',
                'تم اعتماد المهمة',
                "المهمة: {$task->title} أصبحت مكتملة.",
                ['task_id' => $task->id],
            );
        }

        return response()->json($task);
    }

    public function requestChanges(Request $request, Task $task)
    {
        $validated = $request->validate(['comment' => 'required|string|max:5000']);

        $task = $this->workflow->requestChanges($task, $request->user(), $validated['comment']);

        foreach ($task->assignees as $assignee) {
            $this->notifications->notify(
                $assignee,
                'task_changes_requested',
                'طلب تعديل على المهمة',
                $validated['comment'],
                ['task_id' => $task->id],
            );
        }

        return response()->json($task->load('comments.user'));
    }

    public function addComment(Request $request, Task $task)
    {
        $validated = $request->validate([
            'body'        => 'required|string|max:5000',
            'reply_to_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('task_comments', 'id')->where('task_id', $task->id),
            ],
        ]);

        $comment = $task->comments()->create([
            'user_id'     => $request->user()->id,
            'body'        => $validated['body'],
            'type'        => 'comment',
            'reply_to_id' => $validated['reply_to_id'] ?? null,
        ]);

        $recipients = $request->user()->isSuperAdmin()
            ? $task->assignees
            : User::where('role', 'super_admin')->where('is_active', true)->get();

        foreach ($recipients as $recipient) {
            if ($recipient->id === $request->user()->id) {
                continue;
            }
            $this->notifications->notify(
                $recipient,
                'comment_added',
                'تعليق جديد على المهمة',
                "{$request->user()->name}: {$validated['body']}",
                ['task_id' => $task->id],
            );
        }

        return response()->json($comment->load(['user:id,name,role', 'replyTo.user:id,name,role']), 201);
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments', 'public');

        $attachment = $task->attachments()->create([
            'user_id'       => $request->user()->id,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        $admins = User::where('role', 'super_admin')->where('is_active', true)->get();
        foreach ($admins as $admin) {
            if ($admin->id !== $request->user()->id) {
                $this->notifications->notify(
                    $admin,
                    'attachment_uploaded',
                    'مرفق جديد على مهمة',
                    "{$request->user()->name} رفع ملفاً على {$task->title}",
                    ['task_id' => $task->id],
                );
            }
        }

        return response()->json($attachment->load('user:id,name'), 201);
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
