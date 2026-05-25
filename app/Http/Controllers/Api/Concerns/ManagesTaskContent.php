<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WorkProjectPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait ManagesTaskContent
{
    protected function ensureTaskAccess(Request $request, Task $task): void
    {
        if (!$task->userCanAccess($request->user())) {
            abort(403, 'Forbidden.');
        }
    }

    protected function canManageComment(Request $request, Task $task, TaskComment $comment): bool
    {
        if ($comment->type === 'system') {
            return false;
        }

        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $comment->user_id === (int) $user->id) {
            return true;
        }

        $task->loadMissing('workProject');

        return app(WorkProjectPermissionService::class)
            ->can($user, $task->workProject, 'can_moderate_content');
    }

    protected function canManageAttachment(Request $request, Task $task, TaskAttachment $attachment): bool
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $attachment->user_id === (int) $user->id) {
            return true;
        }

        $task->loadMissing('workProject');

        return app(WorkProjectPermissionService::class)
            ->can($user, $task->workProject, 'can_moderate_content');
    }

    protected function performUpdateComment(Request $request, Task $task, TaskComment $comment)
    {
        $this->ensureTaskAccess($request, $task);

        if ((int) $comment->task_id !== (int) $task->id) {
            abort(404);
        }

        if (!$this->canManageComment($request, $task, $comment)) {
            abort(403, 'Forbidden.');
        }

        $validated = $request->validate(['body' => 'required|string|max:5000']);
        $comment->update(['body' => $validated['body']]);

        return response()->json($comment->fresh('user:id,name,role,avatar'));
    }

    protected function performDeleteComment(Request $request, Task $task, TaskComment $comment)
    {
        $this->ensureTaskAccess($request, $task);

        if ((int) $comment->task_id !== (int) $task->id) {
            abort(404);
        }

        if (!$this->canManageComment($request, $task, $comment)) {
            abort(403, 'Forbidden.');
        }

        $snippet = \Illuminate\Support\Str::limit($comment->body, 120);
        $comment->delete();

        app(NotificationService::class)->notifyTaskStakeholders(
            $task,
            'comment_deleted',
            'حذف تعليق على المهمة',
            "{$request->user()->name} حذف تعليقاً: {$snippet}",
            ['task_id' => $task->id],
            $request->user()->id,
        );

        return response()->json(['message' => 'Comment deleted.']);
    }

    protected function performRenameAttachment(Request $request, Task $task, TaskAttachment $attachment)
    {
        $this->ensureTaskAccess($request, $task);

        if ((int) $attachment->task_id !== (int) $task->id) {
            abort(404);
        }

        if (!$this->canManageAttachment($request, $task, $attachment)) {
            abort(403, 'Forbidden.');
        }

        $validated = $request->validate([
            'original_name' => 'required|string|max:255',
        ]);

        $attachment->update(['original_name' => $validated['original_name']]);

        app(NotificationService::class)->notifyTaskStakeholders(
            $task,
            'attachment_renamed',
            'تعديل اسم مرفق',
            "{$request->user()->name} غيّر اسم الملف إلى «{$validated['original_name']}» على {$task->title}",
            ['task_id' => $task->id],
            $request->user()->id,
        );

        return response()->json($attachment->fresh('user:id,name,avatar'));
    }

    protected function performReplaceAttachment(Request $request, Task $task, TaskAttachment $attachment)
    {
        $this->ensureTaskAccess($request, $task);

        if ((int) $attachment->task_id !== (int) $task->id) {
            abort(404);
        }

        if (!$this->canManageAttachment($request, $task, $attachment)) {
            abort(403, 'Forbidden.');
        }

        $request->validate(['file' => 'required|file|max:10240']);
        $file = $request->file('file');

        if ($attachment->path) {
            Storage::disk('public')->delete($attachment->path);
        }

        $path = $file->store('task-attachments', 'public');
        $attachment->update([
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
            'user_id'       => $request->user()->id,
        ]);

        app(NotificationService::class)->notifyTaskStakeholders(
            $task,
            'attachment_replaced',
            'استبدال مرفق',
            "{$request->user()->name} استبدل ملفاً على {$task->title}",
            ['task_id' => $task->id],
            $request->user()->id,
        );

        return response()->json($attachment->fresh('user:id,name,avatar'));
    }

    protected function performDeleteAttachment(Request $request, Task $task, TaskAttachment $attachment)
    {
        $this->ensureTaskAccess($request, $task);

        if ((int) $attachment->task_id !== (int) $task->id) {
            abort(404);
        }

        if (!$this->canManageAttachment($request, $task, $attachment)) {
            abort(403, 'Forbidden.');
        }

        if ($attachment->path) {
            Storage::disk('public')->delete($attachment->path);
        }

        $fileName = $attachment->original_name;
        $attachment->delete();

        app(NotificationService::class)->notifyTaskStakeholders(
            $task,
            'attachment_deleted',
            'حذف مرفق',
            "{$request->user()->name} حذف «{$fileName}» من {$task->title}",
            ['task_id' => $task->id],
            $request->user()->id,
        );

        return response()->json(['message' => 'Attachment deleted.']);
    }

    protected function notifyCommentUpdated(User $actor, Task $task, string $body): void
    {
        app(NotificationService::class)->notifyTaskStakeholders(
            $task,
            'comment_updated',
            'تعديل تعليق على المهمة',
            "{$actor->name}: {$body}",
            ['task_id' => $task->id],
            $actor->id,
        );
    }
}
