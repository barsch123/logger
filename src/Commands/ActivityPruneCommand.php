<?php

namespace Gottvergessen\Activity\Commands;

use Illuminate\Console\Command;
use Gottvergessen\Activity\Models\Activity;

class ActivityPruneCommand extends Command
{
    protected $signature = 'activity:prune {--days=90 : Number of days to keep}';

    protected $description = 'Prune old activity logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days <= 0) {
            $this->error('Days must be a positive number');
            return self::FAILURE;
        }

        $cutoffDate = now()->subDays($days);

        $this->info("Pruning activity logs older than {$days} days (before {$cutoffDate->toDateTimeString()})...");

        $count = Activity::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No activity logs to prune.');
            return self::SUCCESS;
        }

        if ($this->confirm("This will delete {$count} activity log(s). Continue?", true)) {
            $deleted = Activity::where('created_at', '<', $cutoffDate)->delete();
            $this->info("Pruned {$deleted} activity log(s).");
            return self::SUCCESS;
        }

        $this->info('Pruning cancelled.');
        return self::SUCCESS;
    }
}
