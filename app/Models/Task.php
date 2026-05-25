<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public const STATUSES = ['todo', 'in_progress', 'blocked', 'review', 'done'];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    protected $fillable = [
        'work_project_id', 'title', 'description', 'status', 'priority', 'progress',
        'due_date', 'is_overdue', 'created_by', 'approved_by', 'approved_at', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date'    => 'date',
            'is_overdue'  => 'boolean',
            'progress'    => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function workProject(): BelongsTo
    {
        return $this->belongsTo(WorkProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assignees()->where('users.id', $user->id)->exists();
    }

    public function userCanAccess(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (!$user->isStaff()) {
            return false;
        }

        return $this->isAssignedTo($user)
            || $this->workProject->members()->where('users.id', $user->id)->exists();
    }
}
