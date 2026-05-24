<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TasksSendDueRemindersCommand extends Command
{
    protected $signature = 'tasks:send-due-reminders';

    protected $description = 'Notify assignees about tasks due within 24 hours';

    public function handle(NotificationService $notifications): int
    {
        $tasks = Task::where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDay()->toDateString()])
            ->with('assignees')
            ->get();

        foreach ($tasks as $task) {
            foreach ($task->assignees as $assignee) {
                $notifications->notify(
                    $assignee,
                    'task_due_soon',
                    'موعد تسليم قريب',
                    "المهمة «{$task->title}» مستحقة خلال 24 ساعة.",
                    ['task_id' => $task->id],
                );
            }
        }

        $this->info("Sent reminders for {$tasks->count()} task(s).");

        return self::SUCCESS;
    }
}
