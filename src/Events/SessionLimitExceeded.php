<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

/**
 * The user exceeded its allowed number of concurrent sessions.
 *
 * With on_exceed = "ask" nothing is revoked: the listener decides
 * (e.g. asks the user which session to close).
 */
class SessionLimitExceeded
{
    /**
     * @param  Collection<int, Login>  $excess  The oldest logins beyond the limit.
     */
    public function __construct(
        public Authenticatable $user,
        public Login $login,
        public Collection $excess,
        public int $limit,
        public string $action,
    ) {
    }
}
