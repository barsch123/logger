<?php

namespace Gottvergessen\Activity\Commands;

use Illuminate\Console\Command;

class ActivityInstallCommand extends Command
{
    protected $signature = 'activity:install {--force : Overwrite existing files}';

    protected $description = 'Install and configure the Activity package';

    public function handle(): int
    {
        $this->components->info('Welcome to Activity ✨');

        $this->newLine();

        if ($this->confirm('Publish configuration file?', true)) {
            $this->publish('activity-config');
        }

        if ($this->confirm('Publish database migrations?', true)) {
            $this->publish('activity-migrations');
        }

        $this->newLine();
        $this->components->success('Activity installed successfully');

        $this->line('Next steps:');
        $this->line('  • Review config/activity.php');
        $this->line('  • Run php artisan migrate');

        return self::SUCCESS;
    }

    protected function publish(string $tag): void
    {
        $this->components->task(
            "Publishing {$tag}",
            fn() => $this->callSilent('vendor:publish', [
                '--tag' => $tag,
                '--force' => $this->option('force'),
            ]) === self::SUCCESS
        );
    }
}
