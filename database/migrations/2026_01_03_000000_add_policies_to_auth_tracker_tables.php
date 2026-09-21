<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auth Tracker v2.2: trusted / blocked devices, risk assessment, Sanctum
 * refresh tokens and failed login attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection($this->connection());
        $logins = config('auth_tracker.table_name', 'logins');
        $devices = config('auth_tracker.devices_table', 'devices');
        $attempts = config('auth_tracker.attempts_table', 'auth_attempts');

        $schema->table($devices, function (Blueprint $table) use ($devices, $schema) {
            $add = fn (string $column, Closure $definition) => $schema->hasColumn($devices, $column) ?: $definition();

            $add('trusted_at', fn () => $table->timestamp('trusted_at')->nullable()->after('tenant_id'));
            $add('trusted_until', fn () => $table->timestamp('trusted_until')->nullable()->after('trusted_at'));
            $add('blocked_at', fn () => $table->timestamp('blocked_at')->nullable()->index()->after('trusted_until'));
        });

        $schema->table($logins, function (Blueprint $table) use ($logins, $schema) {
            $add = fn (string $column, Closure $definition) => $schema->hasColumn($logins, $column) ?: $definition();

            $add('risk_score', fn () => $table->unsignedTinyInteger('risk_score')->default(0)->after('last_rotated_at'));
            $add('risk_flags', fn () => $table->json('risk_flags')->nullable()->after('risk_score'));
            $add('refresh_token_hash', fn () => $table->char('refresh_token_hash', 64)->nullable()->unique()->after('risk_flags'));
            $add('previous_refresh_token_hash', fn () => $table->char('previous_refresh_token_hash', 64)->nullable()->index()->after('refresh_token_hash'));
            $add('refresh_expires_at', fn () => $table->timestamp('refresh_expires_at')->nullable()->after('previous_refresh_token_hash'));
            $add('latitude', fn () => $table->decimal('latitude', 10, 7)->nullable()->after('country'));
            $add('longitude', fn () => $table->decimal('longitude', 10, 7)->nullable()->after('latitude'));
        });

        if (! $schema->hasTable($attempts)) {
            $schema->create($attempts, function (Blueprint $table) {
                $table->id();
                $table->string('identifier', 191)->nullable()->index();
                $table->string('guard', 40)->nullable();
                $table->string('reason', 40)->index();
                $table->unsignedBigInteger('device_id')->nullable()->index();
                $table->string('ip', 45)->nullable()->index();
                $table->string('country', 100)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('attempted_at')->index();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        $schema->dropIfExists(config('auth_tracker.attempts_table', 'auth_attempts'));

        $schema->table(config('auth_tracker.table_name', 'logins'), function (Blueprint $table) {
            $table->dropUnique(['refresh_token_hash']);
            $table->dropIndex(['previous_refresh_token_hash']);
            $table->dropColumn(['risk_score', 'risk_flags', 'refresh_token_hash', 'previous_refresh_token_hash', 'refresh_expires_at', 'latitude', 'longitude']);
        });

        $schema->table(config('auth_tracker.devices_table', 'devices'), function (Blueprint $table) {
            $table->dropIndex(['blocked_at']);
            $table->dropColumn(['trusted_at', 'trusted_until', 'blocked_at']);
        });
    }

    protected function connection(): ?string
    {
        return config('auth_tracker.connection');
    }
};
