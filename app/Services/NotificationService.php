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

        if ($sendEmail && $user->email) {
            SendTaskNotificationEmail::dispatch($user->id, $type, $title, $body, $data);
        }

        return $notification;
    }

    public function notifyMany(iterable $users, string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->notify($user, $type, $title, $body, $data);
            }
        }
    }
}
