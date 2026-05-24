<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

class TaskWorkflowService
{
    public function applyStaffUpdate(Task $task, User $actor, array $data): Task
    {
        $updates = [];

        if (isset($data['progress'])) {
            $progress = max(0, min(100, (int) $data['progress']));
            $updates['progress'] = $progress;
            if ($progress === 100 && ($data['status'] ?? $task->status) !== 'done') {
                $updates['status'] = 'review';
            }
        }

        if (isset($data['status']) && in_array($data['status'], ['todo', 'in_progress', 'blocked', 'review'], true)) {
            if ($data['status'] !== 'done') {
                $updates['status'] = $data['status'];
            }
        }

        if (!empty($updates)) {
            $updates['updated_by'] = $actor->id;
            $task->update($updates);
            $task->workProject->recalculateProgress();
        }

        return $task->fresh(['workProject', 'assignees', 'comments.user', 'attachments.user']);
    }

    public function approve(Task $task, User $admin): Task
    {
        $task->update([
            'status'      => 'done',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'updated_by'  => $admin->id,
        ]);
        $task->workProject->recalculateProgress();

        return $task->fresh();
    }

    public function requestChanges(Task $task, User $admin, string $commentBody): Task
    {
        $task->update([
            'status'      => 'in_progress',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by'  => $admin->id,
        ]);

        $task->comments()->create([
            'user_id' => $admin->id,
            'body'    => $commentBody,
            'type'    => 'comment',
        ]);

        return $task->fresh();
    }
}
