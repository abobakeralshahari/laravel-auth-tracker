<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auth Tracker v2.1.
 *
 * Logins get an explicit guard / driver / credential, a proper revocation
 * state and activity tracking. Legacy columns (session_id, oauth_access_token_id,
 * personal_access_token_id, logout_at, cleared_by_user) are kept and still
 * written for backward compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        $logins = config('auth_tracker.table_name', 'logins');
        $devices = config('auth_tracker.devices_table', 'devices');
        $schema = Schema::connection($this->connection());

        $schema->table($logins, function (Blueprint $table) use ($logins, $schema) {
            $add = fn (string $column, Closure $definition) => $schema->hasColumn($logins, $column) ?: $definition();

            $add('guard', fn () => $table->string('guard', 40)->nullable()->index()->after('authenticatable_id'));
            $add('driver', fn () => $table->string('driver', 30)->nullable()->after('guard'));
            $add('credential_id', fn () => $table->string('credential_id', 191)->nullable()->after('driver'));
            $add('revoked_at', fn () => $table->timestamp('revoked_at')->nullable()->index()->after('expires_at'));
            $add('revoked_reason', fn () => $table->string('revoked_reason', 40)->nullable()->after('revoked_at'));
            $add('last_activity_at', fn () => $table->timestamp('last_activity_at')->nullable()->index()->after('revoked_reason'));
            $add('rotations', fn () => $table->unsignedInteger('rotations')->default(0)->after('last_activity_at'));
            $add('last_rotated_at', fn () => $table->timestamp('last_rotated_at')->nullable()->after('rotations'));

            if (! in_array("{$logins}_credential_index", $schema->getIndexListing($logins), true)) {
                $table->index(['driver', 'credential_id'], "{$logins}_credential_index");
            }
        });

        $schema->table($devices, function (Blueprint $table) use ($devices, $schema) {
            if (! $schema->hasColumn($devices, 'name')) {
                $table->string('name', 100)->nullable()->after('udid');
            }
        });

        $this->backfill($logins);
    }

    /**
     * Derive the new columns from the legacy ones.
     */
    protected function backfill(string $logins): void
    {
        $query = DB::connection($this->connection())->table($logins);

        foreach ([
            'passport' => 'oauth_access_token_id',
            'sanctum' => 'personal_access_token_id',
            'session' => 'session_id',
        ] as $driver => $column) {
            (clone $query)->whereNull('driver')->whereNotNull($column)
                ->update(['driver' => $driver, 'credential_id' => DB::raw($column)]);
        }

        (clone $query)->whereNull('revoked_at')
            ->where(fn ($q) => $q->where('cleared_by_user', true)->orWhereNotNull('logout_at'))
            ->update(['revoked_at' => DB::raw('COALESCE(logout_at, updated_at)'), 'revoked_reason' => 'user']);

        (clone $query)->whereNull('last_activity_at')
            ->update(['last_activity_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    protected function connection(): ?string
    {
        return config('auth_tracker.connection');
    }

    public function down(): void
    {
        $logins = config('auth_tracker.table_name', 'logins');
        $devices = config('auth_tracker.devices_table', 'devices');

        Schema::connection($this->connection())->table($logins, function (Blueprint $table) use ($logins) {
            $table->dropIndex("{$logins}_credential_index");
            $table->dropIndex("{$logins}_guard_index");
            $table->dropIndex("{$logins}_revoked_at_index");
            $table->dropIndex("{$logins}_last_activity_at_index");
            $table->dropColumn(['guard', 'driver', 'credential_id', 'revoked_at', 'revoked_reason', 'last_activity_at', 'rotations', 'last_rotated_at']);
        });

        Schema::connection($this->connection())->table($devices, function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
