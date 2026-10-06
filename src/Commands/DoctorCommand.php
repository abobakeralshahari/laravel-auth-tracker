<?php

namespace OwaisKit\AuthTracker\Commands;

use OwaisKit\AuthTracker\Facades\AuthTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Check the installation and report what an upgrade would touch.
 */
class DoctorCommand extends Command
{
    protected $signature = 'tracker:doctor';

    protected $description = 'Check the Auth Tracker installation and the state of its data';

    public function handle(): int
    {
        $ok = true;
        $connection = config('auth_tracker.connection');
        $schema = Schema::connection($connection);
        $logins = config('auth_tracker.table_name', 'logins');
        $devices = config('auth_tracker.devices_table', 'devices');

        $this->components->info('Schema');

        foreach ([$logins, $devices, config('auth_tracker.attempts_table', 'auth_attempts')] as $table) {
            $ok = $this->check("Table {$table}", fn () => $schema->hasTable($table) ? 'present' : throw new \RuntimeException('missing, run migrate')) && $ok;
        }

        foreach (['guard', 'driver', 'credential_id', 'revoked_at', 'risk_score', 'refresh_token_hash'] as $column) {
            $ok = $this->check("Column {$logins}.{$column}", fn () => $schema->hasColumn($logins, $column) ? 'present' : throw new \RuntimeException('missing, run migrate')) && $ok;
        }

        $this->components->info('Configuration');

        $sessionDriver = config('session.driver');
        $ok = $this->check('Session driver', fn () => in_array($sessionDriver, ['cookie', 'array'], true)
            ? throw new \RuntimeException("{$sessionDriver}: sessions cannot be revoked server side")
            : $sessionDriver) && $ok;

        foreach ((array) config('auth.guards', []) as $guard => $config) {
            $this->components->twoColumnDetail("Guard {$guard}", ($config['driver'] ?? '?').' → '.AuthTracker::driverNameFor($guard));
        }

        foreach ((array) config('auth.providers', []) as $provider => $config) {
            if ($model = $config['model'] ?? null) {
                $tracked = class_exists($model) && AuthTracker::isTracked(new $model);
                $this->components->twoColumnDetail("Provider {$provider} ({$model})", $tracked ? '<fg=green>tracked</>' : '<fg=yellow>not tracked (missing AuthTracking trait)</>');
            }
        }

        if ($schema->hasTable($logins)) {
            $this->components->info('Data');

            $query = DB::connection($connection)->table($logins);

            $this->components->twoColumnDetail('Logins', (string) (clone $query)->count());
            $this->components->twoColumnDetail('Active logins', (string) (clone $query)->whereNull('revoked_at')->count());

            if ($schema->hasColumn($logins, 'driver')) {
                $legacy = (clone $query)->whereNull('driver')->count();
                $this->components->twoColumnDetail('Logins without driver (pre 2.1)', $legacy ? "<fg=yellow>{$legacy}</>" : '0');
            }

            $prunable = (new (AuthTracker::loginModel()))->prunable()->count();
            $this->components->twoColumnDetail('Prunable logins', (string) $prunable);
        }

        if ($schema->hasTable($devices)) {
            $duplicates = DB::connection($connection)->table($devices)
                ->select('udid')->whereNotNull('udid')->groupBy('udid')->havingRaw('COUNT(*) > 1')->count();

            $this->components->twoColumnDetail('Devices', (string) DB::connection($connection)->table($devices)->count());
            $this->components->twoColumnDetail('Duplicated device identifiers', $duplicates ? "<fg=red>{$duplicates}</>" : '0');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    protected function check(string $label, callable $callback): bool
    {
        try {
            $this->components->twoColumnDetail($label, '<fg=green>'.$callback().'</>');

            return true;
        } catch (Throwable $e) {
            $this->components->twoColumnDetail($label, '<fg=red>'.$e->getMessage().'</>');

            return false;
        }
    }
}
