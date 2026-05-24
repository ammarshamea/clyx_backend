<?php

namespace App\Jobs;

use App\Mail\TaskNotificationMail;
use App\Models\User;
use App\Services\TaskNotificationMailBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTaskNotificationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public ?string $body = null,
        public ?array $data = null,
    ) {}

    public function handle(TaskNotificationMailBuilder $builder): void
    {
        $user = User::find($this->userId);
        if (!$user?->email || !$user->is_active) {
            return;
        }

        if (config('mail.default') === 'log') {
            Log::info('Task email skipped (MAIL_MAILER=log)', [
                'to'   => $user->email,
                'type' => $this->type,
            ]);

            return;
        }

        Mail::to($user->email)->send(new TaskNotificationMail(
            mailSubject: $this->title,
            mailBody: $this->body ?? '',
            eventLabel: $builder->eventLabel($this->type),
            recipientName: $user->name,
            taskLink: $builder->taskUrlForUser($user, $this->data),
            extra: $this->data ?? [],
        ));
    }

    public function failed(?Throwable $e): void
    {
        Log::error('Task notification email failed', [
            'user_id' => $this->userId,
            'type'    => $this->type,
            'error'   => $e?->getMessage(),
        ]);
    }
}
