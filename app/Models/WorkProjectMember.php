<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WorkProjectMember extends Pivot
{
    protected $table = 'work_project_members';

    protected $fillable = [
        'work_project_id',
        'user_id',
        'can_create_tasks',
        'can_edit_task_details',
        'can_assign_tasks',
        'can_delete_tasks',
        'can_moderate_content',
    ];

    protected function casts(): array
    {
        return [
            'can_create_tasks'      => 'boolean',
            'can_edit_task_details' => 'boolean',
            'can_assign_tasks'      => 'boolean',
            'can_delete_tasks'      => 'boolean',
            'can_moderate_content'  => 'boolean',
        ];
    }
}
