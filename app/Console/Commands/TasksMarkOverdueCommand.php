<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TasksMarkOverdueCommand extends Command
{
    protected $signature = 'tasks:mark-overdue';

    protected $description = 'Mark past-due tasks as overdue and notify assignees and admins';

    public function handle(NotificationService $notifications): int
    {
        $tasks = Task::where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('is_overdue', false)
            ->with(['assignees', 'workProject'])
            ->get();

        foreach ($tasks as $task) {
            $task->update(['is_overdue' => true]);

            foreach ($task->assignees as $assignee) {
                $notifications->notify(
                    $assignee,
                    'task_overdue',
                    'مهمة متأخرة',
                    "المهمة «{$task->title}» تجاوزت موعد التسليم.",
                    ['task_id' => $task->id],
                );
            }

            foreach (User::where('role', 'super_admin')->where('is_active', true)->get() as $admin) {
                $notifications->notify(
                    $admin,
                    'task_overdue',
                    'مهمة متأخرة',
                    "«{$task->title}» في مشروع {$task->workProject->name}",
                    ['task_id' => $task->id],
                );
            }
        }

        $this->info("Marked {$tasks->count()} task(s) overdue.");

        return self::SUCCESS;
    }
}
