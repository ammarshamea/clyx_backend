<?php

namespace App\Console\Commands;

use App\Services\WorkProjectTrashService;
use Illuminate\Console\Command;

class PurgeTrashedWorkProjectsCommand extends Command
{
    protected $signature = 'work-projects:purge-trashed';

    protected $description = 'Permanently delete work projects that have been in trash longer than the retention period';

    public function handle(WorkProjectTrashService $trash): int
    {
        $count = $trash->purgeExpired();

        $this->info("Permanently deleted {$count} work project(s).");

        return self::SUCCESS;
    }
}
