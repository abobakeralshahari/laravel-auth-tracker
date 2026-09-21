<?php

namespace Alshahari\AuthTracker\Models;

use Alshahari\AuthTracker\Facades\AuthTracker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use SoftDeletes;

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
    ];

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
