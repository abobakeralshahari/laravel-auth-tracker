<?php

namespace Awsan\AuthTracker\Events;

use Awsan\AuthTracker\Models\Login;
use Awsan\AuthTracker\RequestContext;
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
