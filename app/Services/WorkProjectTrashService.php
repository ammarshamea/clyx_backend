<?php

namespace App\Services;

use App\Models\WorkProject;

class WorkProjectTrashService
{
    /** Permanently remove work projects that exceeded the trash retention period. */
    public function purgeExpired(): int
    {
        $cutoff = now()->subDays(WorkProject::TRASH_RETENTION_DAYS);

        $projects = WorkProject::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->get();

        foreach ($projects as $project) {
            $project->forceDelete();
        }

        return $projects->count();
    }
}
