<?php

namespace OwaisKit\AuthTracker\Commands;

use OwaisKit\AuthTracker\Facades\AuthTracker;
use OwaisKit\AuthTracker\Models\AuthAttempt;
use Illuminate\Console\Command;

/**
 * Delete old revoked / expired logins, failed attempts and orphan devices.
 *
 * Schedule it daily: Schedule::command('tracker:prune')->daily();
 */
class PruneCommand extends Command
{
    protected $signature = 'tracker:prune
                            {--pretend : Report what would be deleted without deleting}';

    protected $description = 'Prune old logins, failed attempts and orphan devices according to the retention settings';

    public function handle(): int
    {
        $models = [
            AuthTracker::loginModel(),
            AuthAttempt::class,
            AuthTracker::deviceModel(), // last: devices without logins left
        ];

        foreach ($models as $model) {
            $count = (new $model)->prunable()->count();

            if (! $this->option('pretend')) {
                $count = (new $model)->pruneAll();
            }

            $this->components->twoColumnDetail(class_basename($model), $this->option('pretend') ? "{$count} prunable" : "{$count} pruned");
        }

        return self::SUCCESS;
    }
}
