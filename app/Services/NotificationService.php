<?php

namespace App\Services;

use App\Jobs\SendTaskNotificationEmail;
use App\Models\AppNotification;
use App\Models\User;

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
}
