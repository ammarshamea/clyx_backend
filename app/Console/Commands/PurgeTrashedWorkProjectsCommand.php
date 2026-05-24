<?php

namespace App\Console\Commands;

use App\Models\WorkProject;
use Illuminate\Console\Command;

class PurgeTrashedWorkProjectsCommand extends Command
{
    protected $signature = 'work-projects:purge-trashed';

    protected $description = 'Permanently delete work projects that have been in trash longer than the retention period';

    public function handle(): int
    {
        $cutoff = now()->subDays(WorkProject::TRASH_RETENTION_DAYS);

        $projects = WorkProject::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->get();

        foreach ($projects as $project) {
            $project->forceDelete();
        }

        $this->info("Permanently deleted {$projects->count()} work project(s).");

        return self::SUCCESS;
    }
}
