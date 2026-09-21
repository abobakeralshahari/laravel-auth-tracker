<?php

namespace Alshahari\AuthTracker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * A failed authentication attempt.
 */
class AuthAttempt extends Model
{
    use Prunable;

    public const REASON_INVALID_CREDENTIALS = 'invalid_credentials';

    public const REASON_LOCKOUT = 'lockout';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('auth_tracker.attempts_table', 'auth_attempts'));

        if ($connection = config('auth_tracker.connection')) {
            $this->setConnection($connection);
        }
    }

    public function scopeForIdentifier(Builder $query, string $identifier): Builder
    {
        return $query->where('identifier', $identifier);
    }

    public function scopeSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('attempted_at', '>=', $since);
    }

    public function prunable(): Builder
    {
        return static::query()->where('attempted_at', '<', now()->subDays((int) config('auth_tracker.attempts.retention_days', 90)));
    }
}
