<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkProject;

class WorkProjectPermissionService
{
    public const PERMISSION_KEYS = [
        'can_create_tasks',
        'can_edit_task_details',
        'can_assign_tasks',
        'can_delete_tasks',
        'can_moderate_content',
    ];

    public function allTrue(): array
    {
        return array_fill_keys(self::PERMISSION_KEYS, true);
    }

    public function defaults(): array
    {
        return array_fill_keys(self::PERMISSION_KEYS, false);
    }

    public function memberPermissions(User $user, WorkProject $project): array
    {
        if ($user->isSuperAdmin()) {
            return $this->allTrue();
        }

        $member = $project->members()->where('users.id', $user->id)->first();
        if (!$member) {
            return $this->defaults();
        }

        return $this->extractFromPivot($member->pivot);
    }

    public function can(User $user, WorkProject $project, string $ability): bool
    {
        if (!in_array($ability, self::PERMISSION_KEYS, true)) {
            return false;
        }

        return ($this->memberPermissions($user, $project)[$ability] ?? false) === true;
    }

    public function isMember(User $user, WorkProject $project): bool
    {
        return $user->isSuperAdmin()
            || $project->members()->where('users.id', $user->id)->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>  $membersPayload
     */
    public function syncMembers(WorkProject $project, array $membersPayload): void
    {
        $sync = [];
        foreach ($membersPayload as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId < 1) {
                continue;
            }
            $sync[$userId] = $this->normalizePivotRow($row);
        }
        $project->members()->sync($sync);
    }

    /**
     * @param  array<int>  $memberIds
     */
    public function syncMemberIds(WorkProject $project, array $memberIds): void
    {
        $sync = [];
        foreach ($memberIds as $userId) {
            $sync[(int) $userId] = $this->defaults();
        }
        $project->members()->sync($sync);
    }

    public function formatMember(User $member): array
    {
        return [
            'id'          => $member->id,
            'name'        => $member->name,
            'email'       => $member->email,
            'role'        => $member->role ?? null,
            'permissions' => $this->extractFromPivot($member->pivot),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Relations\Pivot  $pivot
     */
    public function extractFromPivot($pivot): array
    {
        $perms = $this->defaults();
        foreach (self::PERMISSION_KEYS as $key) {
            $perms[$key] = $this->toBool($pivot->{$key} ?? false);
        }

        return $perms;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function normalizePivotRow(array $row): array
    {
        $out = $this->defaults();
        foreach (self::PERMISSION_KEYS as $key) {
            $out[$key] = array_key_exists($key, $row)
                ? $this->toBool($row[$key])
                : false;
        }

        return $out;
    }

    protected function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
