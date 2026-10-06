<?php

namespace Awsan\AuthTracker\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:install
                            {--force : Overwrite the published configuration file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Auth Tracker configuration and migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Publishing configuration...');

        $this->callSilently('vendor:publish', array_filter([
            '--tag' => 'auth-tracker-config',
            '--force' => $this->option('force'),
        ]));

        $this->components->info('Publishing migrations...');

        $this->callSilently('vendor:publish', ['--tag' => 'auth-tracker-migrations']);

        $this->newLine();
        $this->components->info('Auth Tracker installed.');

        $this->components->bulletList([
            'Run "php artisan migrate".',
            'Add the Awsan\AuthTracker\Traits\AuthTracking trait to your authenticatable models.',
            'Use the "eloquent-tracked" user provider driver in config/auth.php to track remembered sessions.',
        ]);

        return self::SUCCESS;
    }
}
