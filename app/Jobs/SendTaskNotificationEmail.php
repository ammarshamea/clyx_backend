<?php

namespace App\Jobs;

use App\Mail\TaskNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendTaskNotificationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public ?string $body = null,
        public ?array $data = null,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user?->email) {
            return;
        }

        Mail::to($user->email)->send(new TaskNotificationMail(
            $this->title,
            $this->body ?? '',
            $this->data ?? [],
        ));
    }
}
