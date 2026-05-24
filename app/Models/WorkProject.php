<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkProject extends Model
{
    protected $fillable = [
        'name', 'description', 'status', 'starts_at', 'due_at', 'progress', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'due_at'    => 'date',
            'progress'  => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_project_members')
            ->withPivot([
                'can_create_tasks',
                'can_edit_task_details',
                'can_assign_tasks',
                'can_delete_tasks',
                'can_moderate_content',
            ])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function recalculateProgress(): void
    {
        $avg = (int) round($this->tasks()->avg('progress') ?? 0);
        $this->update(['progress' => $avg]);
    }
}
