<?php

namespace OwaisKit\AuthTracker\Events;

use OwaisKit\AuthTracker\Models\Login;
use OwaisKit\AuthTracker\RequestContext;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A new tracked login was recorded.
 */
class SessionStarted
{
    public function __construct(
        public Authenticatable $user,
        public Login $login,
        public RequestContext $context,
    ) {
    }
}
