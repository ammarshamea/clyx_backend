<?php

namespace App\Services;

use App\Models\User;

class TaskNotificationMailBuilder
{
    public const EVENT_LABELS = [
        'task_assigned'         => 'تكليف بمهمة جديدة',
        'task_updated'          => 'تحديث على مهمة',
        'task_created'          => 'مهمة جديدة في المشروع',
        'comment_added'         => 'تعليق جديد',
        'comment_updated'       => 'تعديل تعليق',
        'attachment_uploaded'   => 'مرفق جديد',
        'task_review_requested' => 'مهمة بانتظار المراجعة',
        'task_approved'         => 'تم اعتماد المهمة',
        'task_changes_requested'=> 'طلب تعديل على المهمة',
        'task_due_soon'         => 'موعد تسليم قريب',
        'task_overdue'          => 'مهمة متأخرة',
    ];

    public function eventLabel(string $type): string
    {
        return self::EVENT_LABELS[$type] ?? 'إشعار من نظام المهام';
    }

    public function taskUrlForUser(User $user, ?array $data): ?string
    {
        $taskId = $data['task_id'] ?? null;
        if (!$taskId) {
            return null;
        }

        $base = config('clyx.frontend_url', 'https://clyx.agency');
        $path = $user->isStaff()
            ? "/#/dashboard/staff/tasks/{$taskId}"
            : "/#/dashboard/tasks/{$taskId}";

        return $base . $path;
    }
}
