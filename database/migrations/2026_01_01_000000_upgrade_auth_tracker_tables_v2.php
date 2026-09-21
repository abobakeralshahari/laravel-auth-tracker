<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auth Tracker v2.0 upgrade.
 *
 * - Adds the device columns that the package writes but never created.
 * - Widens the device identifier (server generated identifiers are 64 chars).
 * - Deduplicates devices sharing the same identifier and makes it unique.
 * - Adds the indexes used on every authenticated request.
 * - Renames logins.device to logins.device_name: the column was shadowing
 *   the device() relation.
 *
 * Safe to run on a fresh install and on an existing v1 database.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->upgradeDevices();
        $this->upgradeLogins();
    }

    protected function upgradeDevices(): void
    {
        $table = config('auth_tracker.devices_table', 'devices');

        Schema::connection($this->connection())->table($table, function (Blueprint $blueprint) use ($table) {
            $this->addColumn($table, 'type', fn () => $blueprint->string('type', 20)->nullable()->after('udid'));
            $this->addColumn($table, 'browser', fn () => $blueprint->string('browser', 60)->nullable()->after('model'));
            $this->addColumn($table, 'app_type', fn () => $blueprint->string('app_type', 30)->nullable()->index()->after('app_version'));
            $this->addColumn($table, 'tenant_id', fn () => $blueprint->string('tenant_id', 60)->nullable()->index()->after('app_type'));
            $this->addColumn($table, 'last_seen_at', fn () => $blueprint->timestamp('last_seen_at')->nullable()->index()->after('user_agent'));
            $this->addColumn($table, 'metadata', fn () => $blueprint->json('metadata')->nullable()->after('last_seen_at'));

            $blueprint->string('udid', 100)->nullable()->change();
        });

        $this->deduplicateDevices($table);

        Schema::connection($this->connection())->table($table, function (Blueprint $blueprint) use ($table) {
            if ($this->hasIndex($table, "{$table}_udid_index")) {
                $blueprint->dropIndex("{$table}_udid_index");
            }

            if (! $this->hasIndex($table, "{$table}_udid_unique")) {
                $blueprint->unique('udid');
            }
        });
    }

    protected function upgradeLogins(): void
    {
        $table = config('auth_tracker.table_name', 'logins');

        if (Schema::connection($this->connection())->hasColumn($table, 'device')
            && ! Schema::connection($this->connection())->hasColumn($table, 'device_name')) {
            Schema::connection($this->connection())->table($table, function (Blueprint $blueprint) {
                $blueprint->renameColumn('device', 'device_name');
            });
        }

        Schema::connection($this->connection())->table($table, function (Blueprint $blueprint) use ($table) {
            foreach (['session_id', 'oauth_access_token_id', 'personal_access_token_id', 'expires_at', 'logout_at', 'remember_token'] as $column) {
                if (! $this->hasIndex($table, "{$table}_{$column}_index")) {
                    $blueprint->index($column);
                }
            }

            if (! $this->hasIndex($table, "{$table}_active_index")) {
                $blueprint->index(['authenticatable_type', 'authenticatable_id', 'cleared_by_user', 'logout_at'], "{$table}_active_index");
            }
        });
    }

    /**
     * Keep the oldest device of each duplicated identifier, move the logins
     * of the duplicates to it and remove them.
     */
    protected function deduplicateDevices(string $table): void
    {
        $connection = DB::connection($this->connection());
        $logins = config('auth_tracker.table_name', 'logins');

        $duplicated = $connection->table($table)
            ->select('udid')
            ->whereNotNull('udid')
            ->groupBy('udid')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('udid');

        foreach ($duplicated as $udid) {
            $ids = $connection->table($table)->where('udid', $udid)->orderBy('id')->pluck('id');
            $keep = $ids->shift();

            $connection->table($logins)->whereIn('device_id', $ids)->update(['device_id' => $keep]);
            $connection->table($table)->whereIn('id', $ids)->delete();
        }
    }

    protected function addColumn(string $table, string $column, Closure $definition): void
    {
        if (! Schema::connection($this->connection())->hasColumn($table, $column)) {
            $definition();
        }
    }

    protected function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::connection($this->connection())->getIndexListing($table);

        return in_array($index, $indexes, true);
    }

    protected function connection(): ?string
    {
        return config('auth_tracker.connection');
    }

    public function down(): void
    {
        $devices = config('auth_tracker.devices_table', 'devices');
        $logins = config('auth_tracker.table_name', 'logins');

        Schema::connection($this->connection())->table($devices, function (Blueprint $blueprint) use ($devices) {
            $blueprint->dropUnique("{$devices}_udid_unique");
            $blueprint->index('udid');
            $blueprint->dropIndex("{$devices}_app_type_index");
            $blueprint->dropIndex("{$devices}_tenant_id_index");
            $blueprint->dropIndex("{$devices}_last_seen_at_index");
            $blueprint->dropColumn(['type', 'browser', 'app_type', 'tenant_id', 'last_seen_at', 'metadata']);
        });

        Schema::connection($this->connection())->table($logins, function (Blueprint $blueprint) use ($logins) {
            $blueprint->renameColumn('device_name', 'device');
            $blueprint->dropIndex("{$logins}_active_index");

            foreach (['session_id', 'oauth_access_token_id', 'personal_access_token_id', 'expires_at', 'logout_at', 'remember_token'] as $column) {
                $blueprint->dropIndex("{$logins}_{$column}_index");
            }
        });
    }
};
