<?php

namespace OwaisKit\AuthTracker\Traits;

use OwaisKit\AuthTracker\Actions\RevokeLogin;
use OwaisKit\AuthTracker\Facades\AuthTracker;
use OwaisKit\AuthTracker\Models\Login;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Add to your authenticatable models to track their logins.
 *
 * Every method delegates to the SessionManager (see the AuthTracker facade).
 */
trait AuthTracking
{
    /**
     * Get all of the user's logins.
     */
    public function logins(): MorphMany
    {
        return $this->morphMany(AuthTracker::loginModel(), 'authenticatable');
    }

    /**
     * Get the login of the current request.
     */
    public function currentLogin(): ?Login
    {
        return AuthTracker::current($this);
    }

    /**
     * Active logins (not revoked, not expired) with their device.
     */
    public function activeSessions(?string $guard = null): Collection
    {
        return AuthTracker::active($this, $guard);
    }

    /**
     * Revoked or expired logins.
     */
    public function sessionHistory(int $days = 90): Collection
    {
        return AuthTracker::history($this, $days);
    }

    /**
     * Devices used by the user.
     */
    public function devices(): Collection
    {
        return AuthTracker::devices($this);
    }

    /**
     * Revoke a login by its id, or the current one.
     *
     * @param  int|string|null  $loginId
     */
    public function logout($loginId = null): bool
    {
        $login = $loginId ? AuthTracker::find($this, $loginId) : $this->currentLogin();

        return $login ? AuthTracker::revoke($login) : false;
    }

    /**
     * Revoke every login except the current one.
     */
    public function logoutOthers(): bool
    {
        return AuthTracker::revokeOthers($this) > 0;
    }

    /**
     * Revoke every login, including the current one.
     */
    public function logoutAll(): bool
    {
        return AuthTracker::revokeAll($this, RevokeLogin::REASON_USER_ALL) > 0;
    }

    /**
     * Determine if the current request authenticated this user via a session.
     */
    public function isAuthenticatedBySession(): bool
    {
        return $this->currentLogin()?->driverName() === 'session';
    }

    /**
     * Determine if the current request authenticated this user via Passport.
     */
    public function isAuthenticatedByPassport(): bool
    {
        return $this->currentLogin()?->driverName() === 'passport';
    }

    /**
     * Determine if the current request authenticated this user via Sanctum.
     */
    public function isAuthenticatedBySanctum(): bool
    {
        return $this->currentLogin()?->driverName() === 'sanctum';
    }

    // ------------------------------------------------------------------
    //  1.x API
    // ------------------------------------------------------------------

    /**
     * @deprecated Use activeSessions().
     */
    public function activeLogin(): Collection
    {
        return $this->activeSessions();
    }

    /**
     * @deprecated Use sessionHistory().
     */
    public function historyLogin(): Collection
    {
        return $this->sessionHistory(365 * 10);
    }

    /**
     * Delete one revoked login from the history.
     */
    public function clearHistoryId($id): int
    {
        return $this->logins()->revoked()->whereKey($id)->delete();
    }

    /**
     * Delete all revoked logins from the history.
     */
    public function clearHistory(): int
    {
        return $this->logins()->revoked()->delete();
    }
}
