<?php

namespace App\Services;

use App\Jobs\SendTaskNotificationEmail;
use App\Models\AppNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notify(User $user, string $type, string $title, ?string $body = null, ?array $data = null, bool $sendEmail = true): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        if ($sendEmail && $user->email && $user->is_active) {
            $job = new SendTaskNotificationEmail($user->id, $type, $title, $body, $data);

            if (config('queue.default') === 'sync') {
                dispatch_sync($job);
            } else {
                dispatch($job);
            }
        }

        return $notification;
    }

    public function notifyMany(iterable $users, string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        foreach ($users as $user) {
            if ($user instanceof User && $user->is_active) {
                $this->notify($user, $type, $title, $body, $data);
            }
        }
    }

    /** Notify all active super admins (managers). */
    public function notifyAdmins(string $type, string $title, ?string $body = null, ?array $data = null, ?int $exceptUserId = null): void
    {
        $query = User::where('role', 'super_admin')->where('is_active', true);
        if ($exceptUserId) {
            $query->where('id', '!=', $exceptUserId);
        }
        $this->notifyMany($query->get(), $type, $title, $body, $data);
    }

    /** Task assignees + project staff members + active super admins (deduplicated). */
    public function taskStakeholders(Task $task, ?int $exceptUserId = null): Collection
    {
        $task->loadMissing(['assignees', 'workProject.members']);

        $projectStaff = collect($task->workProject?->members ?? [])
            ->filter(fn (User $user) => $user->isStaff());

        return collect($task->assignees)
            ->merge($projectStaff)
            ->merge(User::where('role', 'super_admin')->where('is_active', true)->get())
            ->unique('id')
            ->filter(fn (User $user) => $user->is_active && (!$exceptUserId || (int) $user->id !== $exceptUserId))
            ->values();
    }

    /** Notify everyone involved in a task (assignees + admins), except the actor. */
    public function notifyTaskStakeholders(
        Task $task,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?int $exceptUserId = null,
    ): void {
        $this->notifyMany(
            $this->taskStakeholders($task, $exceptUserId),
            $type,
            $title,
            $body,
            $data ?? ['task_id' => $task->id],
        );
    }

    /** Notify task assignees only (staff on the task), except the actor. */
    public function notifyTaskAssignees(
        Task $task,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?int $exceptUserId = null,
    ): void {
        $task->loadMissing('assignees');

        $assignees = $task->assignees->filter(
            fn (User $user) => $user->is_active && (!$exceptUserId || (int) $user->id !== $exceptUserId),
        );

        $this->notifyMany(
            $assignees,
            $type,
            $title,
            $body,
            $data ?? ['task_id' => $task->id],
        );
    }
}
