<?php

namespace Awsan\AuthTracker\Models;

use Awsan\AuthTracker\Actions\RevokeLogin;
use Awsan\AuthTracker\EloquentQueryBuilder;
use Awsan\AuthTracker\Facades\AuthTracker;
use Awsan\AuthTracker\Traits\Expirable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A tracked login: a session or an API token, bound to a device.
 */
class Login extends Model
{
    use Expirable, HasFactory, Prunable, SoftDeletes;

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
        'revoked_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_rotated_at' => 'datetime',
        'cleared_by_user' => 'boolean',
        'rotations' => 'integer',
        'ip_data' => 'array',
        'risk_score' => 'integer',
        'risk_flags' => 'array',
        'refresh_expires_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
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
        'credential_id',
        'refresh_token_hash',
        'previous_refresh_token_hash',
        'refresh_expires_at',
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

    protected static function newFactory(): \Awsan\AuthTracker\Database\Factories\LoginFactory
    {
        return \Awsan\AuthTracker\Database\Factories\LoginFactory::new();
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

        $this->setTable(config('auth_tracker.table_name', 'logins'));

        if ($connection = config('auth_tracker.connection')) {
            $this->setConnection($connection);
        }
    }

    // ------------------------------------------------------------------
    //  Relations
    // ------------------------------------------------------------------

    /**
     * The authenticated model (user, admin...).
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

    // ------------------------------------------------------------------
    //  Credential
    // ------------------------------------------------------------------

    /**
     * Name of the tracker driver that issued the credential.
     */
    public function driverName(): string
    {
        return $this->driver ?: match (true) {
            (bool) $this->oauth_access_token_id => 'passport',
            (bool) $this->personal_access_token_id => 'sanctum',
            default => 'session',
        };
    }

    /**
     * Identifier of the credential (session id, token id).
     */
    public function credentialId(): ?string
    {
        $id = $this->credential_id
            ?? $this->session_id
            ?? $this->oauth_access_token_id
            ?? $this->personal_access_token_id;

        return $id === null ? null : (string) $id;
    }

    // ------------------------------------------------------------------
    //  State
    // ------------------------------------------------------------------

    public function isRevoked(): bool
    {
        return ! is_null($this->revoked_at) || $this->cleared_by_user || ! is_null($this->logout_at);
    }

    /**
     * Not revoked and not expired.
     */
    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Dynamically add the "is_current" attribute.
     *
     * @return bool
     */
    public function getIsCurrentAttribute()
    {
        if (! app()->bound('request') || ! ($user = request()->user() ?? auth()->user())) {
            return false;
        }

        if (! AuthTracker::isTracked($user)
            || $this->authenticatable_type !== $user->getMorphClass()
            || (string) $this->authenticatable_id !== (string) $user->getAuthIdentifier()) {
            return false;
        }

        return AuthTracker::current($user)?->is($this) ?? false;
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

    // ------------------------------------------------------------------
    //  Actions
    // ------------------------------------------------------------------

    /**
     * Revoke the login: destroy the session / revoke the token and mark
     * the login as revoked. The record is kept for the login history.
     */
    public function revoke(string $reason = RevokeLogin::REASON_USER): bool
    {
        return AuthTracker::revoke($this, $reason);
    }

    /**
     * Alias of revoke().
     */
    public function logout(): bool
    {
        return $this->revoke();
    }

    /**
     * Mark the login as revoked without touching the session / token.
     */
    public function markAsRevoked(string $reason = RevokeLogin::REASON_USER): bool
    {
        return $this->forceFill([
            'revoked_at' => $this->revoked_at ?? now(),
            'revoked_reason' => $reason,
            'remember_token' => null,
            'cleared_by_user' => true,
            'logout_at' => $this->logout_at ?? now(),
        ])->save();
    }

    /**
     * @deprecated Use markAsRevoked().
     */
    public function revokeDelete(): bool
    {
        return $this->markAsRevoked();
    }

    /**
     * Revoked or expired logins older than the retention period are
     * deleted by "model:prune" / "tracker:prune".
     */
    public function prunable(): Builder
    {
        $before = now()->subDays((int) config('auth_tracker.retention.logins_days', 90));

        return static::withExpired()
            ->withTrashed()
            ->where(fn (Builder $query) => $query
                ->where('revoked_at', '<', $before)
                ->orWhere('expires_at', '<', $before)
                ->orWhere('deleted_at', '<', $before));
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
