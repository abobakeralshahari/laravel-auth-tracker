<?php

namespace OwaisKit\AuthTracker\Models;

use OwaisKit\AuthTracker\Facades\AuthTracker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, Prunable, SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
        'trusted_at' => 'datetime',
        'trusted_until' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    protected static function newFactory(): \OwaisKit\AuthTracker\Database\Factories\DeviceFactory
    {
        return \OwaisKit\AuthTracker\Database\Factories\DeviceFactory::new();
    }

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('auth_tracker.devices_table', 'devices'));

        if ($connection = config('auth_tracker.connection')) {
            $this->setConnection($connection);
        }
    }

    /**
     * The last authenticatable that used this device.
     */
    public function deviceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * All the logins made from this device.
     */
    public function logins(): HasMany
    {
        return $this->hasMany(AuthTracker::loginModel(), 'device_id');
    }

    /**
     * The most recent login made from this device.
     */
    public function login(): HasOne
    {
        return $this->hasOne(AuthTracker::loginModel(), 'device_id')->latestOfMany();
    }

    /**
     * Devices unseen for the retention period, without any login left,
     * are deleted by "model:prune" / "tracker:prune".
     */
    public function prunable(): Builder
    {
        return static::withTrashed()
            ->where('last_seen_at', '<', now()->subDays((int) config('auth_tracker.retention.devices_days', 180)))
            ->whereNull('blocked_at')
            ->whereDoesntHave('logins', fn (Builder $query) => $query->withExpired()->withTrashed());
    }

    /**
     * Explicitly trusted by its user (e.g. skip the second factor), and
     * the trust has not expired.
     */
    public function isTrusted(): bool
    {
        return ! is_null($this->trusted_at)
            && (is_null($this->trusted_until) || $this->trusted_until->isFuture());
    }

    public function isBlocked(): bool
    {
        return ! is_null($this->blocked_at);
    }

    /**
     * Update the attributes without overwriting existing values with nulls.
     *
     * Requests coming from a web browser do not send the device headers,
     * so a plain update would erase data (like the FCM token) sent earlier
     * by the mobile application.
     *
     * @param  array  $attributes
     * @return bool
     */
    public function mergeAttributes(array $attributes): bool
    {
        $attributes = array_filter($attributes, fn ($value) => $value !== null && $value !== '');

        return $this->forceFill($attributes)->save();
    }
}
