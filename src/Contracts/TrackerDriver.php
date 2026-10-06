<?php

namespace OwaisKit\AuthTracker\Contracts;

use OwaisKit\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A tracker driver knows how a given authentication mechanism identifies
 * and invalidates its credential (a session id, a token id...).
 *
 * Register your own with AuthTracker::extend('jwt', fn ($app) => new JwtDriver).
 */
interface TrackerDriver
{
    /**
     * Driver name stored on the login (session, sanctum, passport...).
     */
    public function name(): string;

    /**
     * Legacy logins column holding the credential id, if any
     * (session_id, oauth_access_token_id, personal_access_token_id).
     */
    public function legacyColumn(): ?string;

    /**
     * Identifier of the credential used by the current request for
     * the given user, or null when the user was not authenticated
     * through this driver.
     */
    public function currentCredentialId(Authenticatable $user): ?string;

    /**
     * Invalidate the underlying session / token of the login.
     */
    public function revoke(Login $login): void;
}
