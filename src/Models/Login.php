<?php

namespace Alshahari\AuthTracker\Models;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\EloquentQueryBuilder;
use Alshahari\AuthTracker\Traits\Expirable;
use Alshahari\AuthTracker\Traits\ManagesLogins;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Login extends Model
{
    use Expirable, ManagesLogins, SoftDeletes;

    const EXPIRES_AT = 'expires_at';

    /**
     * Session key holding the id of the login bound to the current session.
     */
    const SESSION_KEY = 'auth_tracker.login_id';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'logout_at' => 'datetime',
        'cleared_by_user' => 'boolean',
        'ip_data' => 'array',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'authenticatable_type',
        'authenticatable_id',
        'session_id',
        'remember_token',
        'oauth_access_token_id',
        'personal_access_token_id',
        'expires_at',
        'deleted_at',
        'device_id',
        'updated_at',
        'device_type',
        'ip_data',
        'platform',
        'browser',
        'cleared_by_user',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_current'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('auth_tracker.table_name', 'logins'));

        if ($connection = config('auth_tracker.connection')) {
            $this->setConnection($connection);
        }
    }

    /**
     * Relation between Login and an authenticatable model.
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The device used for this login.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(AuthTracker::deviceModel(), 'device_id');
    }

    /**
     * Add the "location" attribute to get the IP address geolocation.
     *
     * @return string|null
     */
    public function getLocationAttribute()
    {
        $location = array_filter([$this->city, $this->region, $this->country]);

        return $location ? implode(', ', $location) : null;
    }

    /**
     * Dynamically add the "is_current" attribute.
     *
     * @return bool
     */
    public function getIsCurrentAttribute()
    {
        $request = app()->bound('request') ? request() : null;

        if (! $request) {
            return false;
        }

        // Session
        if ($this->session_id && $request->hasSession()) {
            $session = $request->session();

            if ($session->has(self::SESSION_KEY)) {
                return (int) $session->get(self::SESSION_KEY) === (int) $this->getKey();
            }

            return $this->session_id === $session->getId();
        }

        $user = $request->user();

        if (! $user || ! method_exists($user, 'isAuthenticatedByPassport')) {
            return false;
        }

        // Passport
        if ($this->oauth_access_token_id && $user->isAuthenticatedByPassport()) {
            return $this->oauth_access_token_id === $user->currentPassportTokenId();
        }

        // Sanctum
        if ($this->personal_access_token_id && $user->isAuthenticatedBySanctum()) {
            return (int) $this->personal_access_token_id === (int) $user->currentAccessToken()->id;
        }

        return false;
    }

    /**
     * Determine if this login is still active (not revoked by the user).
     */
    public function isActive(): bool
    {
        return ! $this->cleared_by_user && is_null($this->logout_at) && ! $this->isExpired();
    }

    /**
     * Revoke the login: destroy the session / revoke the token and mark
     * the login as cleared. The record is kept for the login history.
     *
     * @return bool
     */
    public function revoke()
    {
        if ($this->session_id) {
            $this->destroySession($this->session_id);
        } elseif ($this->oauth_access_token_id) {
            $this->revokePassportTokens($this->oauth_access_token_id);
        } elseif ($this->personal_access_token_id) {
            $this->revokeSanctumTokens($this->personal_access_token_id);
        }

        return $this->markAsRevoked();
    }

    /**
     * Alias of revoke().
     *
     * @return bool
     */
    public function logout()
    {
        return $this->revoke();
    }

    /**
     * Mark the login as revoked without touching the session / token.
     *
     * @return bool
     */
    public function markAsRevoked()
    {
        return $this->forceFill([
            'cleared_by_user' => true,
            'logout_at' => now(),
            'remember_token' => null,
        ])->save();
    }

    /**
     * @deprecated Use markAsRevoked().
     */
    public function revokeDelete()
    {
        return $this->markAsRevoked();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentQueryBuilder($query);
    }
}
