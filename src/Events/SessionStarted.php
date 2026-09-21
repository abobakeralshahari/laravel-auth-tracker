<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\RequestContext;
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
